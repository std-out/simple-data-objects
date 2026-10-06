---
title: Performance
aside: false
pageClass: bm-page
breadcrumb: false
---

<section class="bm">
  <div class="bm-inner">
    <div class="bm-kicker">
      <span class="bm-kicker-label">Performance</span>
      <span class="bm-kicker-rule"></span>
    </div>
    <h1 class="bm-title">50× faster hydration and 60× faster serialization than the field-leading PHP data-object library — without changing how you write DTOs.</h1>
    <p class="bm-method">Benchmarked against the most popular full-featured data-object library in the PHP/Laravel ecosystem — identical DTO shapes and attributes, inside a fully booted Laravel app with both libraries' caches warmed, 20,000 iterations per scenario after a 2,000-iteration warmup, PHP 8.4. Medians of 7 runs (5 for XML). Absolute numbers vary with hardware; the ratios stay stable across runs.</p>
    <div class="bm-stats">
      <div class="bm-stat">
        <div class="bm-stat-value">51×</div>
        <div class="bm-stat-title">Hydration throughput</div>
        <div class="bm-stat-detail">faster on a flat DTO</div>
      </div>
      <div class="bm-stat">
        <div class="bm-stat-value">60×</div>
        <div class="bm-stat-title">Serialization throughput</div>
        <div class="bm-stat-detail">faster on a flat DTO</div>
      </div>
      <div class="bm-stat">
        <div class="bm-stat-value">12×</div>
        <div class="bm-stat-title">Process memory, 52 MB XML feed</div>
        <div class="bm-stat-detail">less than a SimpleXML loop</div>
      </div>
    </div>
    <div class="bm-section-head">
      <h2 class="bm-h2">Throughput</h2>
      <span class="bm-note">higher is better · each scenario scaled to its own leader</span>
    </div>
    <div class="bm-chart">
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Hydration — flat DTO</span>
          <span class="bm-row-x">51× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">6.3M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:2.0%"></span></span>
          <span class="bm-value">125K ops/s</span>
        </div>
      </div>
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Hydration — nested DTO</span>
          <span class="bm-row-x">36× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">3.4M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:2.7%"></span></span>
          <span class="bm-value">93K ops/s</span>
        </div>
      </div>
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Hydration — collection of 20</span>
          <span class="bm-row-x">21× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">211K ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:4.9%"></span></span>
          <span class="bm-value">10K ops/s</span>
        </div>
      </div>
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Hydration — with a date cast</span>
          <span class="bm-row-x">12× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">1.4M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:8.4%"></span></span>
          <span class="bm-value">118K ops/s</span>
        </div>
      </div>
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Serialization — flat DTO</span>
          <span class="bm-row-x">60× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">14.5M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:1.7%"></span></span>
          <span class="bm-value">241K ops/s</span>
        </div>
      </div>
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Serialization — nested DTO</span>
          <span class="bm-row-x">48× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">7.7M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:2.1%"></span></span>
          <span class="bm-value">162K ops/s</span>
        </div>
      </div>
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Serialization — collection of 20</span>
          <span class="bm-row-x">15× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">415K ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:6.6%"></span></span>
          <span class="bm-value">27K ops/s</span>
        </div>
      </div>
    </div>
    <div class="bm-section-head">
      <h2 class="bm-h2">Streaming a 100,000-row CSV import</h2>
      <span class="bm-note">higher is better</span>
    </div>
    <p class="bm-note bm-note--block">Rows from a generator, hydrated one by one. Both libraries stream here and hold the same flat 12 KB — the difference is speed, not memory.</p>
    <div class="bm-chart">
      <div class="bm-row bm-row--tall">
        <div class="bm-row-head">
          <span class="bm-row-label">lazyCollection() — rows hydrated per second</span>
          <span class="bm-row-x">85% faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">67K rows/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:54.2%"></span></span>
          <span class="bm-value">36K rows/s</span>
        </div>
      </div>
    </div>
    <div class="bm-section-head">
      <h2 class="bm-h2">Streaming XML — 100,000 elements, 52 MB file</h2>
      <span class="bm-note">each scenario in its own process</span>
    </div>
    <p class="bm-note bm-note--block">The alternative has no XML support, so throughput is measured against what a consumer writes by hand: an XMLReader loop mapping each element to an array. That loop stays as flat on memory as lazyXml() — at a fifth of the speed. Memory is compared with SimpleXML twice: a careful loop that handles one element at a time and accumulates nothing, and the heaviest common pattern, collecting every row into an array before hydrating. The same app sitting idle is at 54 MB.</p>
    <div class="bm-chart bm-chart--last">
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Throughput — lazyXml() vs hand-written XMLReader loop</span>
          <span class="bm-row-x">5× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">80K nodes/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:18.1%"></span></span>
          <span class="bm-value">15K nodes/s</span>
        </div>
      </div>
      <div class="bm-row">
        <div class="bm-row-head">
          <span class="bm-row-label">Peak process memory — lazyXml() vs a SimpleXML loop</span>
          <span class="bm-row-x">12× less memory</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">lazyXml()</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:8%"></span></span>
          <span class="bm-value bm-value--us">55 MB</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">SimpleXML loop</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:100%"></span></span>
          <span class="bm-value">692 MB</span>
        </div>
      </div>
      <div class="bm-row bm-row--tall">
        <div class="bm-row-head">
          <span class="bm-row-label">Peak process memory — lazyXml() vs SimpleXML with every row collected first</span>
          <span class="bm-row-x">18× less memory</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">lazyXml()</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:5.5%"></span></span>
          <span class="bm-value bm-value--us">55 MB</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">SimpleXML, collected</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:100%"></span></span>
          <span class="bm-value">997 MB</span>
        </div>
      </div>
    </div>
<div class="bm-prose">

CPU time per operation follows the same ratios — less CPU burned per request means more headroom per server. The advantage is largest where the object itself is cheap and shrinks as real work (a date cast, twenty nested objects) takes a bigger share of each call. The `from()`/`toArray()` hot paths execute [compiled per-class closures](../features/cache.md), and [`lazyCollection()`](../features/collections.md#lazy-collections) keeps peak memory flat on any dataset size. [`lazyXml()`](../features/xml.md) does the same straight from a file: only the fields the DTO declares are read, so a 100,000-element document adds about 1 MB to the process. The SimpleXML figure is process memory (RSS), not the PHP heap — libxml builds the whole document outside PHP's memory manager, so `memory_get_peak_usage()` reports roughly 10 MB for `lazyXml()` and for the SimpleXML loop alike; only the collected variant shows up in the heap, at about 420 MB.

</div>
    <h2 class="bm-h2 bm-h2--table">The numbers</h2>
    <table class="bm-table">
      <thead>
        <tr>
          <th class="bm-th">Scenario</th>
          <th class="bm-th bm-th--num">Simple Data Objects</th>
          <th class="bm-th bm-th--num">Popular alternative</th>
          <th class="bm-th bm-th--num bm-th--last">Advantage</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="bm-td">Hydration — flat DTO</td>
          <td class="bm-td bm-td--num">~6,300,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~125,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~51×</td>
        </tr>
        <tr>
          <td class="bm-td">Hydration — nested DTO</td>
          <td class="bm-td bm-td--num">~3,400,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~93,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~36×</td>
        </tr>
        <tr>
          <td class="bm-td">Hydration — collection of 20</td>
          <td class="bm-td bm-td--num">~211,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~10,200 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~21×</td>
        </tr>
        <tr>
          <td class="bm-td">Hydration — with a date cast</td>
          <td class="bm-td bm-td--num">~1,400,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~118,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~12×</td>
        </tr>
        <tr>
          <td class="bm-td">Serialization — flat DTO</td>
          <td class="bm-td bm-td--num">~14,500,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~241,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~60×</td>
        </tr>
        <tr>
          <td class="bm-td">Serialization — nested DTO</td>
          <td class="bm-td bm-td--num">~7,700,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~162,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~48×</td>
        </tr>
        <tr>
          <td class="bm-td">Serialization — collection of 20</td>
          <td class="bm-td bm-td--num">~415,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~27,500 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~15×</td>
        </tr>
        <tr>
          <td class="bm-td">CSV — 100,000 rows, streamed</td>
          <td class="bm-td bm-td--num">~67,000 rows/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~36,000 rows/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~1.85×</td>
        </tr>
        <tr>
          <td class="bm-td">CSV — peak memory while streaming</td>
          <td class="bm-td bm-td--num">12 KB</td>
          <td class="bm-td bm-td--num bm-td--muted">12 KB</td>
          <td class="bm-td bm-td--num bm-td--adv">equal</td>
        </tr>
        <tr>
          <td class="bm-td">XML — 100,000 elements, streamed</td>
          <td class="bm-td bm-td--num">~80,000 nodes/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~15,000 nodes/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~5×</td>
        </tr>
        <tr>
          <td class="bm-td">XML — peak process memory vs a SimpleXML loop</td>
          <td class="bm-td bm-td--num">55 MB</td>
          <td class="bm-td bm-td--num bm-td--muted">692 MB (SimpleXML)</td>
          <td class="bm-td bm-td--num bm-td--adv">~12×</td>
        </tr>
        <tr>
          <td class="bm-td">XML — peak process memory vs SimpleXML, all rows collected</td>
          <td class="bm-td bm-td--num">55 MB</td>
          <td class="bm-td bm-td--num bm-td--muted">997 MB (SimpleXML)</td>
          <td class="bm-td bm-td--num bm-td--adv">~18×</td>
        </tr>
      </tbody>
    </table>
    <div class="bm-cta">
      <div class="bm-cta-text">
        <h2 class="bm-h2">Don't take these numbers on faith — run them</h2>
        <p>Every figure on this page comes from a public, runnable benchmark project: identical DTO shapes for both libraries, inside a booted Laravel app, with each library's cache warmed first. Clone it, read the code, swap in your own payloads. Your absolute numbers will differ with hardware — the ratios are what to compare.</p>
        <a class="bm-cta-link" href="https://github.com/std-out/simple-data-objects-benchmark" target="_blank" rel="noreferrer">Open the benchmark repository →</a>
      </div>
      <pre class="bm-cta-code"><code>git clone https://github.com/std-out/simple-data-objects-benchmark
cd simple-data-objects-benchmark
make bench       # everything, in Docker (PHP 8.4)
make bench-xml   # only the XML feed comparison</code></pre>
    </div>
  </div>
</section>

<style>
.bm-page .content-container { max-width: none !important; }
.bm-page .VPDoc .content { max-width: 1104px !important; }
.bm {
  --bm-paper: var(--vp-c-bg);
  --bm-ink: var(--vp-c-text-1);
  --bm-ink-soft: var(--vp-c-text-2);
  --bm-ink-muted: var(--vp-c-text-3);
  --bm-rule: var(--vp-c-divider);
  --bm-track: var(--vp-c-default-soft);
  --bm-accent: var(--vp-c-brand-1);
  --bm-context: var(--vp-c-text-3);
  --bm-serif: var(--vp-font-family-base);
  --bm-sans: var(--vp-font-family-base);
  --bm-mono: var(--vp-font-family-mono);
  color: var(--bm-ink);
  font-family: var(--bm-sans), sans-serif;
  margin: 8px 0 16px;
  padding: 16px 0 8px;
}
.bm-inner {
  max-width: 1120px;
  margin: 0 auto;
}
.bm-kicker {
  display: flex;
  align-items: baseline;
  gap: 14px;
  margin-bottom: 22px;
}
.bm-kicker-label {
  font-family: var(--bm-mono), monospace;
  font-size: 13px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--bm-accent);
  font-weight: 600;
}
.bm-kicker-rule {
  flex: 1;
  height: 1px;
  background: var(--bm-rule);
  align-self: center;
}
.bm h1.bm-title {
  font-family: var(--bm-serif), sans-serif;
  font-weight: 600;
  font-size: clamp(26px, 3.6vw, 38px);
  line-height: 1.2;
  letter-spacing: -.02em;
  margin: 0 0 20px;
  max-width: 780px;
  color: var(--bm-ink);
}
.bm-method {
  font-family: var(--bm-mono), monospace;
  font-size: 14px;
  line-height: 1.7;
  color: var(--bm-ink-soft);
  max-width: 660px;
  margin: 0 0 48px;
}
.bm-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1px;
  background: var(--bm-rule);
  border: 1px solid var(--bm-rule);
  margin-bottom: 64px;
}
.bm-stat {
  background: var(--bm-paper);
  padding: 28px 24px;
}
.bm-stat-value {
  font-family: var(--bm-serif), sans-serif;
  font-weight: 600;
  font-size: 48px;
  line-height: 1;
  color: var(--bm-accent);
  margin-bottom: 10px;
}
.bm-stat-title {
  font-size: 15px;
  color: var(--bm-ink);
  font-weight: 500;
  margin-bottom: 4px;
}
.bm-stat-detail {
  font-family: var(--bm-mono), monospace;
  font-size: 12.5px;
  color: var(--bm-ink-muted);
}
.bm-section-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}
.bm h2.bm-h2 {
  font-family: var(--bm-serif), sans-serif;
  font-weight: 600;
  font-size: 21px;
  margin: 0;
  padding: 0;
  border: none;
  letter-spacing: -.01em;
  color: var(--bm-ink);
}
.bm-note {
  font-family: var(--bm-mono), monospace;
  font-size: 12px;
  color: var(--bm-ink-muted);
}
.bm-note--block {
  margin: 0 0 12px;
  font-size: 12.5px;
}
.bm-chart { margin-bottom: 48px; }
.bm-chart--last { margin-bottom: 40px; }
.bm-row {
  padding: 22px 0;
  border-bottom: 1px solid var(--bm-rule);
}
.bm-row--tall { padding-bottom: 34px; }
.bm-row-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}
.bm-row-label {
  font-size: 15.5px;
  font-weight: 500;
}
.bm-row-x {
  font-family: var(--bm-mono), monospace;
  font-size: 13px;
  color: var(--bm-accent);
  font-weight: 600;
  white-space: nowrap;
}
.bm-line {
  display: flex;
  align-items: center;
  gap: 14px;
}
.bm-line + .bm-line { margin-top: 8px; }
.bm-series {
  width: 150px;
  flex-shrink: 0;
  font-family: var(--bm-mono), monospace;
  font-size: 12px;
  color: var(--bm-ink-muted);
}
.bm-series--us { color: var(--bm-ink-soft); }
.bm-track {
  flex: 1;
  height: 20px;
  background: var(--bm-track);
  border-radius: 2px;
  display: block;
  overflow: hidden;
}
.bm-fill {
  display: block;
  height: 100%;
  border-radius: 2px;
}
.bm-fill--us { background: var(--bm-accent); }
.bm-fill--them { background: var(--bm-context); }
.bm-value {
  width: 96px;
  flex-shrink: 0;
  text-align: right;
  font-family: var(--bm-mono), monospace;
  font-size: 12.5px;
  color: var(--bm-ink-muted);
}
.bm-value--us {
  font-weight: 600;
  color: var(--bm-ink);
}
.bm-prose {
  font-family: var(--bm-mono), monospace;
  font-size: 13px;
  line-height: 1.75;
  color: var(--bm-ink-soft);
  max-width: 700px;
  margin: 0 0 44px;
}
.bm .bm-prose code {
  background: var(--bm-track);
  color: var(--bm-ink);
  padding: 1px 5px;
  border-radius: 3px;
  font-family: var(--bm-mono), monospace;
  font-size: 12.5px;
}
.bm .bm-prose a > code {
  color: var(--bm-accent);
}
.bm .bm-prose a {
  color: var(--bm-accent);
  text-decoration: underline;
  text-underline-offset: 2px;
}
.bm-cta {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 24px;
  margin-top: 56px;
  padding: 32px;
  border: 1px solid var(--bm-accent);
  border-radius: 12px;
  background: var(--vp-c-brand-soft);
}
.bm-cta-text p {
  margin: 12px 0 18px;
  font-size: 15px;
  line-height: 1.7;
  color: var(--bm-ink-soft);
}
.bm .bm-cta-link {
  display: inline-block;
  padding: 10px 18px;
  border-radius: 20px;
  background: var(--vp-button-brand-bg);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}
.bm .bm-cta-link:hover { background: var(--vp-button-brand-hover-bg); }
.bm pre.bm-cta-code {
  margin: 0;
  padding: 20px 22px;
  border: 1px solid var(--bm-rule);
  border-radius: 10px;
  background: var(--bm-paper);
  overflow-x: auto;
}
.bm pre.bm-cta-code code {
  padding: 0;
  background: none;
  font-family: var(--bm-mono), monospace;
  font-size: 13px;
  line-height: 1.8;
  color: var(--bm-ink);
}
.bm-cta-text p { max-width: 720px; }
@media (max-width: 860px) {
  .bm-cta { padding: 24px; }
}
.bm h2.bm-h2--table {
  font-size: 20px;
  margin: 0 0 18px;
}
.bm table.bm-table {
  display: table;
  width: 100%;
  border-collapse: collapse;
  font-family: var(--bm-mono), monospace;
  font-size: 13px;
  margin: 0;
}
.bm .bm-table tr,
.bm .bm-table tr:nth-child(2n),
.bm .bm-table tr:hover { background: transparent; border: none; }
.bm .bm-th {
  text-align: left;
  padding: 10px 12px;
  color: var(--bm-ink-muted);
  font-weight: 500;
  border: none;
  border-bottom: 1px solid var(--bm-ink);
  text-transform: uppercase;
  letter-spacing: .04em;
  font-size: 11px;
  background: transparent;
}
.bm .bm-th:first-child { padding-left: 0; }
.bm .bm-th--num { text-align: right; }
.bm .bm-th--last { padding-right: 0; }
.bm .bm-td {
  padding: 11px 12px;
  border: none;
  border-bottom: 1px solid var(--bm-rule);
  background: transparent;
  color: var(--bm-ink);
}
.bm .bm-td:first-child { padding-left: 0; }
.bm .bm-td:last-child { padding-right: 0; }
.bm .bm-td--num { text-align: right; }
.bm .bm-td--muted { color: var(--bm-ink-muted); }
.bm .bm-td--adv {
  color: var(--bm-accent);
  font-weight: 600;
}
@media (max-width: 640px) {
  .bm-inner { padding: 0 20px; }
  .bm-line { flex-wrap: wrap; row-gap: 4px; }
  .bm-series { width: 100%; }
  .bm-stat-value { font-size: 44px; }
  .bm-table { font-size: 11.5px; }
}
</style>
