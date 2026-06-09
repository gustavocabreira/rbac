import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'gustavocabreira/rbac',
  description: 'Resource-scoped RBAC for any PHP system (PDO/MySQL, framework-agnostic).',
  base: '/rbac/',

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/introduction' },
      { text: 'API Reference', link: '/guide/api-reference' },
      { text: 'GitHub', link: 'https://github.com/gustavocabreira/rbac' },
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Introduction', link: '/guide/introduction' },
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Configuration', link: '/guide/configuration' },
        ],
      },
      {
        text: 'Core Concepts',
        items: [
          { text: 'Concepts', link: '/guide/concepts' },
          { text: 'Permissionable Interface', link: '/guide/permissionable' },
        ],
      },
      {
        text: 'Usage',
        items: [
          { text: 'Granting Access', link: '/guide/granting' },
          { text: 'Revoking Access', link: '/guide/revoking' },
          { text: 'Checking Permissions', link: '/guide/checking' },
          { text: 'Listing Resources', link: '/guide/listing' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'API Reference', link: '/guide/api-reference' },
          { text: 'Running Tests', link: '/guide/testing' },
          { text: 'FAQ', link: '/guide/faq' },
        ],
      },
    ],

    editLink: {
      pattern: 'https://github.com/gustavocabreira/rbac/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © Huggy',
    },
  },
})
