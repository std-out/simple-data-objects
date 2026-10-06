<script setup>
import { computed } from 'vue'
import { useData } from 'vitepress'

const { page, theme, frontmatter } = useData()

const sections = {
  guide: 'Guide',
  features: 'Guide',
  attributes: 'Reference · Attributes',
  casts: 'Reference · Casts',
  laravel: 'Frameworks',
  integrations: 'Frameworks',
}

const trail = computed(() => {
  if (frontmatter.value.breadcrumb === false) {
    return null
  }

  const path = page.value.relativePath
  const segment = path.split('/')[0]
  const section = sections[segment]

  if (!section) {
    return null
  }

  const link = '/' + path.replace(/(index)?\.md$/, '')
  const groups = theme.value.sidebar?.[`/${segment}/`] ?? []

  const group = groups.find((g) =>
    g.items?.some((item) => item.link === link || item.link + '/' === link),
  )?.text

  return [section, group].filter((part, i, all) => part && all.indexOf(part) === i)
})
</script>

<template>
  <nav v-if="trail" class="sdo-breadcrumb" aria-label="Breadcrumb">
    <template v-for="(part, i) in trail" :key="part">
      <span v-if="i > 0" class="sdo-breadcrumb-sep" aria-hidden="true">/</span>
      <span>{{ part }}</span>
    </template>
  </nav>
</template>
