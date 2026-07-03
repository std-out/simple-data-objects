---
title: Performance
aside: false
pageClass: bm-page
---

<section class="bm">
  <div class="bm-inner">
    <div class="bm-kicker bm-rise">
      <span class="bm-kicker-label">Performance</span>
      <span class="bm-kicker-rule"></span>
    </div>
    <h1 class="bm-title bm-rise bm-d1">35× faster than the field-leading PHP data-object library — without changing how you write DTOs.</h1>
    <p class="bm-method bm-rise bm-d2">Benchmarked against the most popular full-featured data-object library in the PHP/Laravel ecosystem — identical DTO shapes and attributes, 20,000 iterations per scenario after a 2,000-iteration warmup, PHP 8.4. Absolute numbers vary with hardware; the ratios stay stable across runs.</p>
    <div class="bm-stats bm-rise bm-d3">
      <div class="bm-stat">
        <div class="bm-stat-value">35×</div>
        <div class="bm-stat-title">Hydration throughput</div>
        <div class="bm-stat-detail">faster on a flat DTO</div>
      </div>
      <div class="bm-stat">
        <div class="bm-stat-value">37×</div>
        <div class="bm-stat-title">Serialization throughput</div>
        <div class="bm-stat-detail">faster on a flat DTO</div>
      </div>
      <div class="bm-stat">
        <div class="bm-stat-value">50×</div>
        <div class="bm-stat-title">Peak memory, streaming 50k rows</div>
        <div class="bm-stat-detail">less with lazyCollection()</div>
      </div>
    </div>
    <div class="bm-section-head bm-rise bm-d4">
      <h2 class="bm-h2">Throughput</h2>
      <span class="bm-note">higher is better · each scenario scaled to its own leader</span>
    </div>
    <div class="bm-chart">
      <div class="bm-row bm-rise">
        <div class="bm-row-head">
          <span class="bm-row-label">Hydration — flat DTO</span>
          <span class="bm-row-x">35× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">4.5M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:2.9%"></span></span>
          <span class="bm-value">130K ops/s</span>
        </div>
      </div>
      <div class="bm-row bm-rise">
        <div class="bm-row-head">
          <span class="bm-row-label">Hydration — nested DTO</span>
          <span class="bm-row-x">30× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">2.2M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:3.4%"></span></span>
          <span class="bm-value">74K ops/s</span>
        </div>
      </div>
      <div class="bm-row bm-rise">
        <div class="bm-row-head">
          <span class="bm-row-label">Hydration — collection of 20</span>
          <span class="bm-row-x">29× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">220K ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:3.4%"></span></span>
          <span class="bm-value">7.5K ops/s</span>
        </div>
      </div>
      <div class="bm-row bm-rise">
        <div class="bm-row-head">
          <span class="bm-row-label">Serialization — flat DTO</span>
          <span class="bm-row-x">37× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">7.4M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:2.7%"></span></span>
          <span class="bm-value">200K ops/s</span>
        </div>
      </div>
      <div class="bm-row bm-rise">
        <div class="bm-row-head">
          <span class="bm-row-label">Serialization — nested DTO</span>
          <span class="bm-row-x">34× faster</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:100%"></span></span>
          <span class="bm-value bm-value--us">4.0M ops/s</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:2.9%"></span></span>
          <span class="bm-value">117K ops/s</span>
        </div>
      </div>
    </div>
    <div class="bm-section-head bm-rise">
      <h2 class="bm-h2">Peak memory — streaming 50,000 hydrated rows</h2>
      <span class="bm-note">lower is better</span>
    </div>
    <p class="bm-note bm-note--block">Rows from a generator, consumed one by one.</p>
    <div class="bm-chart bm-chart--last">
      <div class="bm-row bm-row--tall">
        <div class="bm-row-head">
          <span class="bm-row-label">lazyCollection()</span>
          <span class="bm-row-x">50× less memory</span>
        </div>
        <div class="bm-line">
          <span class="bm-series bm-series--us">Simple Data Objects</span>
          <span class="bm-track"><span class="bm-fill bm-fill--us" style="width:2%"></span></span>
          <span class="bm-value bm-value--us">0.26 MB</span>
        </div>
        <div class="bm-line">
          <span class="bm-series">Popular alternative</span>
          <span class="bm-track"><span class="bm-fill bm-fill--them" style="width:100%"></span></span>
          <span class="bm-value">13 MB</span>
        </div>
      </div>
    </div>
    <p class="bm-prose">CPU time per operation follows the same ratios — less CPU burned per request means more headroom per server. The <code>from()</code>/<code>toArray()</code> hot paths execute <a href="../features/cache.html">compiled per-class closures</a>, and <a href="../features/collections.html#lazy-collections"><code>lazyCollection()</code></a> keeps peak memory flat on any dataset size.</p>
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
          <td class="bm-td bm-td--num">~4,500,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~130,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~35×</td>
        </tr>
        <tr>
          <td class="bm-td">Hydration — nested DTO</td>
          <td class="bm-td bm-td--num">~2,200,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~74,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~30×</td>
        </tr>
        <tr>
          <td class="bm-td">Hydration — collection of 20</td>
          <td class="bm-td bm-td--num">~220,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~7,500 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~29×</td>
        </tr>
        <tr>
          <td class="bm-td">Serialization — flat DTO</td>
          <td class="bm-td bm-td--num">~7,400,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~200,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~37×</td>
        </tr>
        <tr>
          <td class="bm-td">Serialization — nested DTO</td>
          <td class="bm-td bm-td--num">~4,000,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--muted">~117,000 ops/s</td>
          <td class="bm-td bm-td--num bm-td--adv">~34×</td>
        </tr>
        <tr>
          <td class="bm-td">Peak memory — streaming 50,000 rows</td>
          <td class="bm-td bm-td--num">0.26 MB</td>
          <td class="bm-td bm-td--num bm-td--muted">~13 MB</td>
          <td class="bm-td bm-td--num bm-td--adv">~50×</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<style>
.bm-page .content-container { max-width: none !important; }
.bm-page .VPDoc .content { max-width: 1104px !important; }
@keyframes bm-rise-in {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes bm-grow-bar {
  from { width: 0%; }
}
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
  font-family: var(--bm-sans);
  margin: 8px 0 16px;
  padding: 16px 0 8px;
}
.bm-inner {
  max-width: 1120px;
  margin: 0 auto;
}
.bm-rise { animation: bm-rise-in .6s ease both; }
.bm-d1 { animation-delay: .05s; }
.bm-d2 { animation-delay: .1s; }
.bm-d3 { animation-delay: .15s; }
.bm-d4 { animation-delay: .2s; }
.bm-kicker {
  display: flex;
  align-items: baseline;
  gap: 14px;
  margin-bottom: 22px;
}
.bm-kicker-label {
  font-family: var(--bm-mono);
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
  font-family: var(--bm-serif);
  font-weight: 600;
  font-size: clamp(26px, 3.6vw, 38px);
  line-height: 1.2;
  letter-spacing: -.02em;
  margin: 0 0 20px;
  max-width: 780px;
  color: var(--bm-ink);
}
.bm-method {
  font-family: var(--bm-mono);
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
  font-family: var(--bm-serif);
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
  font-family: var(--bm-mono);
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
  font-family: var(--bm-serif);
  font-weight: 600;
  font-size: 21px;
  margin: 0;
  padding: 0;
  border: none;
  letter-spacing: -.01em;
  color: var(--bm-ink);
}
.bm-note {
  font-family: var(--bm-mono);
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
  font-family: var(--bm-mono);
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
  font-family: var(--bm-mono);
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
  animation: bm-grow-bar .8s ease both;
}
.bm-fill--us { background: var(--bm-accent); }
.bm-fill--them { background: var(--bm-context); }
.bm-value {
  width: 96px;
  flex-shrink: 0;
  text-align: right;
  font-family: var(--bm-mono);
  font-size: 12.5px;
  color: var(--bm-ink-muted);
}
.bm-value--us {
  font-weight: 600;
  color: var(--bm-ink);
}
.bm-prose {
  font-family: var(--bm-mono);
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
  font-family: var(--bm-mono);
  font-size: 12.5px;
}
.bm .bm-prose a > code {
  color: var(--bm-accent);
}
.bm-prose a {
  color: var(--bm-accent);
  text-decoration: underline;
  text-underline-offset: 2px;
}
.bm h2.bm-h2--table {
  font-size: 20px;
  margin: 0 0 18px;
}
.bm table.bm-table {
  display: table;
  width: 100%;
  border-collapse: collapse;
  font-family: var(--bm-mono);
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
@media (prefers-reduced-motion: reduce) {
  .bm-rise, .bm-fill { animation: none; }
}
</style>
