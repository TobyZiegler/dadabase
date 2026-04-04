#!/usr/bin/env node
/**
 * fetch-jokes.js
 * Fetches dad jokes from r/dadjokes via Reddit's public JSON API
 * and outputs a CSV ready for Dad-a-Base bulk upload.
 *
 * Usage:  node fetch-jokes.js
 * Output: reddit-jokes.csv (in the same directory)
 *
 * CSV columns: setup, punchline, submitted_by
 * (matches Dad-a-Base bulk_upload.php expected format)
 */

const https = require('https');
const fs = require('fs');
const path = require('path');

// ── Configuration ────────────────────────────────────────────────────────────

const CONFIG = {
  subreddit:      'dadjokes',
  sortBy:         'top',          // top | hot | new | rising
  timeframe:      'all',        // hour | day | week | month | year | all
  postsPerPage:   100,            // Reddit max per request
  maxPages:       20,              // 5 pages × 100 = up to 500 candidates
  attribution:    'r/dadjokes',   // prefix for submitted_by field

  // Filtering thresholds
  minUpvoteRatio: 0.60,           // skip if less than 60% upvoted
  minScore:       5,              // skip very low-scoring posts
  maxSetupLen:    300,            // characters
  maxPunchlineLen: 500,           // characters
  minPunchlineLen: 5,             // avoid single-word or empty punchlines

  outputFile: 'reddit-jokes.csv',
};

// ── Dross patterns — if title or body matches, skip the post ─────────────────

const SKIP_PATTERNS = [
  /^(weekly|daily|monthly)\b/i,      // weekly thread etc.
  /\bmod\s+(post|announcement)\b/i,
  /\bsubreddit\b/i,
  /\bsub\s+rules?\b/i,
  /\bthanks?\s+for\b/i,              // "thanks for 10k subscribers"
  /\bcontest\b/i,
  /\bsubmission\s+thread\b/i,
  /\[meta\]/i,
  /\bremoved\b/i,
  /\[deleted\]/i,
];

// ── Helpers ──────────────────────────────────────────────────────────────────

function get(url) {
  return new Promise((resolve, reject) => {
    const options = {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept': 'application/json, text/html, */*',
        'Accept-Language': 'en-US,en;q=0.9',
        'Cache-Control': 'no-cache',
      },
    };
    https.get(url, options, (res) => {
      if (res.statusCode === 429) {
        reject(new Error('Rate limited by Reddit. Wait a minute and try again.'));
        return;
      }
      if (res.statusCode !== 200) {
        reject(new Error(`HTTP ${res.statusCode} from Reddit`));
        return;
      }
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try { resolve(JSON.parse(data)); }
        catch (e) { reject(new Error('Failed to parse Reddit JSON response')); }
      });
    }).on('error', reject);
  });
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function isJunk(title, body) {
  const combined = (title + ' ' + body).toLowerCase();
  return SKIP_PATTERNS.some(p => p.test(combined));
}

function cleanText(str) {
  return (str || '')
    .replace(/\r\n/g, ' ')
    .replace(/\r/g, ' ')
    .replace(/\n/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function csvEscape(str) {
  // Wrap in quotes; escape internal quotes by doubling them
  return '"' + String(str).replace(/"/g, '""') + '"';
}

function looksLikeUrl(str) {
  return /^https?:\/\//i.test(str.trim()) || /^www\./i.test(str.trim());
}

// ── Main ─────────────────────────────────────────────────────────────────────

async function fetchPage(after = null) {
  let url = `https://www.reddit.com/r/${CONFIG.subreddit}/${CONFIG.sortBy}.json`
          + `?limit=${CONFIG.postsPerPage}&t=${CONFIG.timeframe}`;
  if (after) url += `&after=${after}`;

  const data = await get(url);
  return data?.data ?? null;
}

async function main() {
  console.log(`\n🎣  Fetching jokes from r/${CONFIG.subreddit} (${CONFIG.sortBy}/${CONFIG.timeframe})...\n`);

  const jokes = [];
  const seen  = new Set();  // deduplicate by normalized setup
  let after   = null;
  let page    = 0;

  let totalFetched  = 0;
  let skippedJunk   = 0;
  let skippedFormat = 0;
  let skippedDupe   = 0;

  while (page < CONFIG.maxPages) {
    page++;
    process.stdout.write(`  Page ${page}/${CONFIG.maxPages} ... `);

    let pageData;
    try {
      pageData = await fetchPage(after);
    } catch (err) {
      console.error(`\n  ⚠️  Error on page ${page}: ${err.message}`);
      break;
    }

    if (!pageData || !pageData.children || pageData.children.length === 0) {
      console.log('no more posts.');
      break;
    }

    const posts = pageData.children;
    totalFetched += posts.length;
    let pageKept = 0;

    for (const { data: post } of posts) {
      const setup     = cleanText(post.title);
      const punchline = cleanText(post.selftext);
      const author    = post.author || 'unknown';
      const score     = post.score ?? 0;
      const ratio     = post.upvote_ratio ?? 1;

      // ── Filter: score / ratio ──
      if (score < CONFIG.minScore || ratio < CONFIG.minUpvoteRatio) {
        skippedFormat++;
        continue;
      }

      // ── Filter: no punchline or punchline is a URL ──
      if (!punchline || punchline.length < CONFIG.minPunchlineLen || looksLikeUrl(punchline)) {
        skippedFormat++;
        continue;
      }

      // ── Filter: punchline is "[removed]" or "[deleted]" ──
      if (/^\[(removed|deleted)\]$/i.test(punchline)) {
        skippedFormat++;
        continue;
      }

      // ── Filter: too long ──
      if (setup.length > CONFIG.maxSetupLen || punchline.length > CONFIG.maxPunchlineLen) {
        skippedFormat++;
        continue;
      }

      // ── Filter: junk/meta posts ──
      if (isJunk(setup, punchline)) {
        skippedJunk++;
        continue;
      }

      // ── Filter: duplicate setup ──
      const key = setup.toLowerCase().replace(/\W/g, '');
      if (seen.has(key)) {
        skippedDupe++;
        continue;
      }
      seen.add(key);

      jokes.push({ setup, punchline, submitted_by: `u/${author} via ${CONFIG.attribution}` });
      pageKept++;
    }

    console.log(`kept ${pageKept} of ${posts.length}`);

    after = pageData.after;
    if (!after) { console.log('  (Reddit has no more pages)'); break; }

    // Be polite to Reddit's API between pages
    if (page < CONFIG.maxPages) await sleep(1200);
  }

  // ── Write CSV ──────────────────────────────────────────────────────────────

  if (jokes.length === 0) {
    console.log('\n⚠️  No jokes passed the filters. Nothing written.\n');
    return;
  }

  const header = 'setup,punchline,submitted_by';
  const rows   = jokes.map(j =>
    [csvEscape(j.setup), csvEscape(j.punchline), csvEscape(j.submitted_by)].join(',')
  );
  const csv = [header, ...rows].join('\n');

  const outPath = path.join(process.cwd(), CONFIG.outputFile);
  fs.writeFileSync(outPath, csv, 'utf8');

  // ── Summary ────────────────────────────────────────────────────────────────

  console.log('\n─────────────────────────────────────────');
  console.log(`✅  Done!`);
  console.log(`   Posts fetched:       ${totalFetched}`);
  console.log(`   Skipped (junk/meta): ${skippedJunk}`);
  console.log(`   Skipped (format):    ${skippedFormat}`);
  console.log(`   Skipped (duplicate): ${skippedDupe}`);
  console.log(`   Jokes kept:          ${jokes.length}`);
  console.log(`   Output file:         ${outPath}`);
  console.log('─────────────────────────────────────────\n');
  console.log('Ready to upload via Dad-a-Base bulk_upload.php');
  console.log('Tip: upload as "pending" so you can review before approving.\n');
}

main().catch(err => {
  console.error('\n❌  Fatal error:', err.message);
  process.exit(1);
});