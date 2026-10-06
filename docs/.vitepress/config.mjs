import { defineConfig } from 'vitepress'

const guide = [
  {
    text: 'Get started',
    items: [
      { text: 'Introduction', link: '/guide/introduction' },
      { text: 'Installation', link: '/guide/installation' },
      { text: 'Quick start', link: '/guide/quick-start' },
    ],
  },
  {
    text: 'Creating objects',
    items: [
      { text: 'Hydration', link: '/features/hydration' },
      { text: 'Validation', link: '/features/validation' },
      { text: 'Collecting errors', link: '/features/error-accumulation' },
      { text: 'Optional values', link: '/features/optional' },
      { text: 'Input pipes', link: '/features/pipes' },
    ],
  },
  {
    text: 'Output',
    items: [
      { text: 'Serialization', link: '/features/serialization' },
      { text: 'JSON Schema & TypeScript', link: '/features/schema-generation' },
    ],
  },
  {
    text: 'Working with objects',
    items: [
      { text: 'Immutable copies', link: '/features/with' },
      { text: 'Comparing objects', link: '/features/comparison' },
      { text: 'Collections', link: '/features/collections' },
    ],
  },
  {
    text: 'Large data & production',
    items: [
      { text: 'Streaming XML', link: '/features/xml' },
      { text: 'Metadata cache', link: '/features/cache' },
      { text: 'Performance', link: '/guide/performance' },
    ],
  },
  {
    text: 'Coming from another library',
    items: [
      { text: 'Migrating from laravel-data', link: '/guide/migrating-from-laravel-data' },
    ],
  },
]

const attributes = [
  {
    text: 'Overview',
    items: [{ text: 'All attributes', link: '/attributes/' }],
  },
  {
    text: 'Input & mapping',
    items: [
      { text: '#[MapPropertyName]', link: '/attributes/map-property-name' },
      { text: '#[MapInputName] / #[MapOutputName]', link: '/attributes/map-input-output-name' },
      { text: '#[TransformKeys]', link: '/attributes/transform-keys' },
      { text: '#[Flatten]', link: '/attributes/flatten' },
      { text: '#[Pipe]', link: '/attributes/pipe' },
      { text: '#[RejectUnknownKeys]', link: '/attributes/reject-unknown-keys' },
    ],
  },
  {
    text: 'Types & validation',
    items: [
      { text: '#[Cast]', link: '/attributes/cast' },
      { text: '#[DataCollection]', link: '/attributes/data-collection' },
      { text: '#[Discriminator]', link: '/attributes/discriminator' },
      { text: '#[Rules]', link: '/attributes/rules' },
      { text: '#[InferRules]', link: '/attributes/infer-rules' },
    ],
  },
  {
    text: 'Output',
    items: [
      { text: '#[Hidden]', link: '/attributes/hidden' },
      { text: '#[IgnoreIfNull]', link: '/attributes/ignore-if-null' },
      { text: '#[Computed]', link: '/attributes/computed' },
      { text: '#[WrapIn]', link: '/attributes/wrap-in' },
    ],
  },
  {
    text: 'XML & Laravel',
    items: [
      { text: '#[XmlAttribute] / #[XmlElement] / #[XmlText]', link: '/attributes/xml' },
      { text: '#[WhenLoaded]', link: '/attributes/when-loaded' },
    ],
  },
]

const casts = [
  {
    text: 'Overview',
    items: [
      { text: 'All casts', link: '/casts/' },
      { text: 'Writing your own', link: '/casts/custom' },
    ],
  },
  {
    text: 'Dates & enums',
    items: [
      { text: 'DateTimeCast', link: '/casts/date-time' },
      { text: 'EnumCast', link: '/casts/enum' },
    ],
  },
  {
    text: 'Scalars',
    items: [
      { text: 'BooleanCast', link: '/casts/boolean' },
      { text: 'IntegerCast & FloatCast', link: '/casts/numeric' },
      { text: 'TrimCast', link: '/casts/trim' },
    ],
  },
  {
    text: 'Structured values',
    items: [
      { text: 'JsonCast', link: '/casts/json' },
      { text: 'CommaSeparatedCast', link: '/casts/comma-separated' },
      { text: 'MoneyCast', link: '/casts/money' },
      { text: 'UuidCast', link: '/casts/uuid' },
    ],
  },
  {
    text: 'Security',
    items: [{ text: 'EncryptedCast', link: '/casts/encrypted' }],
  },
]

const frameworks = [
  {
    text: 'Laravel',
    items: [
      { text: 'Setup in five minutes', link: '/integrations/laravel' },
      { text: 'Requests, models & responses', link: '/laravel/' },
      { text: 'Service provider & commands', link: '/laravel/service-provider' },
      { text: 'Eloquent casting', link: '/laravel/eloquent-casting' },
      { text: 'Livewire', link: '/laravel/livewire' },
      { text: 'Pagination & response envelope', link: '/laravel/pagination' },
    ],
  },
  {
    text: 'Other environments',
    items: [
      { text: 'Plain PHP', link: '/integrations/plain-php' },
      { text: 'Symfony', link: '/integrations/symfony' },
      { text: 'Slim & PSR-7', link: '/integrations/psr-7' },
    ],
  },
]

export default defineConfig({
  title: 'Simple Data Objects',
  description: 'Lightweight, attribute-driven Data Transfer Objects for PHP 8.4+',
  base: '/simple-data-objects/',

  head: [
    ['link', { rel: 'icon', type: 'image/png', href: '/simple-data-objects/favicon.png' }],
  ],

  markdown: {
    theme: { light: 'github-light', dark: 'github-dark' },
  },

  themeConfig: {
    logo: { light: '/logo.png', dark: '/logo-dark.png', alt: 'std-out' },

    nav: [
      { text: 'Guide', link: '/guide/introduction', activeMatch: '^/(guide|features)/' },
      {
        text: 'Reference',
        activeMatch: '^/(attributes|casts)/',
        items: [
          { text: 'Attributes', link: '/attributes/' },
          { text: 'Built-in casts', link: '/casts/' },
        ],
      },
      {
        text: 'Frameworks',
        activeMatch: '^/(laravel|integrations)/',
        items: [
          { text: 'Laravel', link: '/integrations/laravel' },
          { text: 'Plain PHP', link: '/integrations/plain-php' },
          { text: 'Symfony', link: '/integrations/symfony' },
          { text: 'Slim & PSR-7', link: '/integrations/psr-7' },
        ],
      },
      { text: 'Performance', link: '/guide/performance' },
      {
        text: 'v2',
        items: [
          { text: 'v2 (current)', link: '/' },
          { text: 'v1 (stable)', link: 'https://std-out.github.io/simple-data-objects/v1/' },
          { text: 'Changelog', link: 'https://github.com/std-out/simple-data-objects/blob/main/CHANGELOG.md' },
        ],
      },
    ],

    sidebar: {
      '/guide/': guide,
      '/features/': guide,
      '/attributes/': attributes,
      '/casts/': casts,
      '/laravel/': frameworks,
      '/integrations/': frameworks,
    },

    outline: { level: [2, 3], label: 'On this page' },

    editLink: {
      pattern: 'https://github.com/std-out/simple-data-objects/edit/main/docs/:path',
      text: 'Suggest a change to this page',
    },

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
