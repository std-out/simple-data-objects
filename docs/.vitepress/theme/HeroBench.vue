<script setup>
import { withBase } from 'vitepress'

const rows = [
  { label: 'Hydration', unit: 'flat object', ours: '6.3M ops/s', theirs: '125K ops/s', width: 2.0, factor: '51× faster' },
  { label: 'Serialization', unit: 'flat object', ours: '14.5M ops/s', theirs: '241K ops/s', width: 1.7, factor: '60× faster' },
  { label: 'Collection of 20', unit: 'hydration', ours: '211K ops/s', theirs: '10K ops/s', width: 4.9, factor: '21× faster' },
]
</script>

<template>
  <a class="sdo-bench" :href="withBase('/guide/performance')" aria-label="Benchmark results — open the performance page">
    <div class="sdo-bench-head">
      <span>Benchmark</span>
      <span>vs the most popular alternative · PHP 8.4</span>
    </div>

    <div v-for="row in rows" :key="row.label" class="sdo-bench-row">
      <div class="sdo-bench-title">
        <span><strong>{{ row.label }}</strong> {{ row.unit }}</span>
        <em>{{ row.factor }}</em>
      </div>
      <div class="sdo-bench-line">
        <span class="sdo-bench-track"><span class="sdo-bench-fill sdo-bench-fill--ours" style="width: 100%"></span></span>
        <span class="sdo-bench-value sdo-bench-value--ours">{{ row.ours }}</span>
      </div>
      <div class="sdo-bench-line">
        <span class="sdo-bench-track"><span class="sdo-bench-fill" :style="{ width: row.width + '%' }"></span></span>
        <span class="sdo-bench-value">{{ row.theirs }}</span>
      </div>
    </div>

    <div class="sdo-bench-foot">
      <span><i class="sdo-bench-dot sdo-bench-dot--ours"></i>Simple Data Objects</span>
      <span><i class="sdo-bench-dot"></i>Alternative</span>
      <span class="sdo-bench-more">All results →</span>
    </div>
  </a>
</template>
