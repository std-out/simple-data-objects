import { defineConfig } from 'vitepress'

// "Blueprint" code palette from the design handoff (docs-page-1d)
const blueprintCodeTheme = {
  name: 'blueprint-dark',
  type: 'dark',
  colors: {
    'editor.background': '#0C1220',
    'editor.foreground': '#C9D4EE',
  },
  tokenColors: [
    { scope: ['comment', 'punctuation.definition.comment'], settings: { foreground: '#566184' } },
    { scope: ['keyword', 'storage.type', 'storage.modifier', 'keyword.operator'], settings: { foreground: '#FF7A1A' } },
    { scope: ['string', 'punctuation.definition.string', 'entity.name.function', 'support.function'], settings: { foreground: '#7EA6FF' } },
    { scope: ['constant.numeric', 'constant.language', 'support.constant'], settings: { foreground: '#FFB36B' } },
    { scope: ['variable', 'variable.other', 'entity.name.type', 'entity.name.class', 'support.class'], settings: { foreground: '#C9D4EE' } },
  ],
}

export default defineConfig({
  title: 'Simple Data Objects',
  description: 'Lightweight, attribute-driven Data Transfer Objects for PHP 8.4+',
  base: '/simple-data-objects/',

  head: [
    ['link', { rel: 'icon', href: '/simple-data-objects/favicon.ico' }],
    ['link', { rel: 'preconnect', href: 'https://fonts.googleapis.com' }],
    ['link', { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' }],
    ['link', { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap' }],
  ],

  markdown: {
    theme: blueprintCodeTheme,
  },

  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'Features', link: '/features/hydration' },
      { text: 'Casts', link: '/casts/' },
      {
        text: 'GitHub',
        link: 'https://github.com/std-out/simple-data-objects',
      },
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Introduction', link: '/guide/introduction' },
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Quick Start', link: '/guide/quick-start' },
          { text: 'Performance', link: '/guide/performance' },
        ],
      },
      {
        text: 'Features',
        items: [
          { text: 'Hydration', link: '/features/hydration' },
          { text: 'Serialization', link: '/features/serialization' },
          { text: 'Validation', link: '/features/validation' },
          { text: 'DataPipe — Preprocessing', link: '/features/pipes' },
          { text: 'Immutable Copies — with()', link: '/features/with' },
          { text: 'Comparison — equals() & diff()', link: '/features/comparison' },
          { text: 'Collections', link: '/features/collections' },
          { text: 'Laravel Integration', link: '/features/laravel' },
          { text: 'Metadata Cache', link: '/features/cache' },
        ],
      },
      {
        text: 'Integrations',
        items: [
          { text: 'Plain PHP', link: '/integrations/plain-php' },
          { text: 'Laravel', link: '/integrations/laravel' },
          { text: 'Symfony', link: '/integrations/symfony' },
          { text: 'Slim & PSR-7', link: '/integrations/psr-7' },
        ],
      },
      {
        text: 'Attributes',
        items: [
          { text: 'Overview', link: '/attributes/' },
          { text: '#[Cast]', link: '/attributes/cast' },
          { text: '#[Rules]', link: '/attributes/rules' },
          { text: '#[Pipe]', link: '/attributes/pipe' },
          { text: '#[Flatten]', link: '/attributes/flatten' },
          { text: '#[Hidden]', link: '/attributes/hidden' },
          { text: '#[IgnoreIfNull]', link: '/attributes/ignore-if-null' },
          { text: '#[MapPropertyName]', link: '/attributes/map-property-name' },
          { text: '#[TransformKeys]', link: '/attributes/transform-keys' },
          { text: '#[DataCollection]', link: '/attributes/data-collection' },
        ],
      },
      {
        text: 'Built-in Casts',
        items: [
          { text: 'Overview', link: '/casts/' },
          { text: 'DateTimeCast', link: '/casts/date-time' },
          { text: 'EnumCast', link: '/casts/enum' },
          { text: 'BooleanCast', link: '/casts/boolean' },
          { text: 'IntegerCast & FloatCast', link: '/casts/numeric' },
          { text: 'TrimCast', link: '/casts/trim' },
          { text: 'JsonCast', link: '/casts/json' },
          { text: 'EncryptedCast', link: '/casts/encrypted' },
          { text: 'UuidCast', link: '/casts/uuid' },
          { text: 'Custom Casts', link: '/casts/custom' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/std-out/simple-data-objects' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024–present std-out',
    },

    search: {
      provider: 'local',
    },
  },
})
