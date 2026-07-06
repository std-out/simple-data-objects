<script setup>
import { computed } from 'vue'
import { useData } from 'vitepress'

const { page, theme, frontmatter } = useData()

const trail = computed(() => {
  if (frontmatter.value.breadcrumb === false) {
    return null
  }

  const path = page.value.relativePath
  if (!path.includes('/')) {
    return null
  }

  const segment = path.split('/')[0]
  const section = segment.charAt(0).toUpperCase() + segment.slice(1)

  const link = '/' + path.replace(/(index)?\.md$/, '')
  const group = (theme.value.sidebar ?? []).find((g) =>
    g.items?.some((item) => item.link === link || item.link + '/' === link),
  )?.text

  return !group || group === section ? section : `${section} / ${group}`
})
</script>

<template>
  <nav v-if="trail" class="bp-breadcrumb" aria-label="Breadcrumb">{{ trail }}</nav>
</template>
