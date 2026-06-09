import { defineConfig } from 'vitepress'

const enSidebar = [
  {
    text: 'Getting Started',
    items: [
      { text: 'Introduction', link: '/en/guide/introduction' },
      { text: 'Installation', link: '/en/guide/installation' },
      { text: 'Configuration', link: '/en/guide/configuration' },
    ],
  },
  {
    text: 'Core Concepts',
    items: [
      { text: 'Concepts', link: '/en/guide/concepts' },
      { text: 'Registering Modules & Permissions', link: '/en/guide/seeding' },
      { text: 'Permissionable Interface', link: '/en/guide/permissionable' },
    ],
  },
  {
    text: 'Usage',
    items: [
      { text: 'Granting Access', link: '/en/guide/granting' },
      { text: 'Revoking Access', link: '/en/guide/revoking' },
      { text: 'Checking Permissions', link: '/en/guide/checking' },
      { text: 'Listing Resources', link: '/en/guide/listing' },
    ],
  },
  {
    text: 'Reference',
    items: [
      { text: 'API Reference', link: '/en/guide/api-reference' },
      { text: 'Running Tests', link: '/en/guide/testing' },
      { text: 'FAQ', link: '/en/guide/faq' },
    ],
  },
]

const ptBrSidebar = [
  {
    text: 'Primeiros Passos',
    items: [
      { text: 'Introdução', link: '/pt-br/guide/introduction' },
      { text: 'Instalação', link: '/pt-br/guide/installation' },
      { text: 'Configuração', link: '/pt-br/guide/configuration' },
    ],
  },
  {
    text: 'Conceitos Fundamentais',
    items: [
      { text: 'Conceitos', link: '/pt-br/guide/concepts' },
      { text: 'Registrando Módulos e Permissões', link: '/pt-br/guide/seeding' },
      { text: 'Interface Permissionable', link: '/pt-br/guide/permissionable' },
    ],
  },
  {
    text: 'Uso',
    items: [
      { text: 'Concedendo Acesso', link: '/pt-br/guide/granting' },
      { text: 'Revogando Acesso', link: '/pt-br/guide/revoking' },
      { text: 'Verificando Permissões', link: '/pt-br/guide/checking' },
      { text: 'Listando Recursos', link: '/pt-br/guide/listing' },
    ],
  },
  {
    text: 'Referência',
    items: [
      { text: 'Referência da API', link: '/pt-br/guide/api-reference' },
      { text: 'Rodando os Testes', link: '/pt-br/guide/testing' },
      { text: 'FAQ', link: '/pt-br/guide/faq' },
    ],
  },
]

export default defineConfig({
  title: 'gustavocabreira/rbac',
  description: 'Resource-scoped RBAC for any PHP system (PDO/MySQL, framework-agnostic).',
  base: '/rbac/',

  locales: {
    en: {
      label: 'English',
      lang: 'en',
      link: '/en/',
      themeConfig: {
        nav: [
          { text: 'Guide', link: '/en/guide/introduction' },
          { text: 'API Reference', link: '/en/guide/api-reference' },
          { text: 'GitHub', link: 'https://github.com/gustavocabreira/rbac' },
        ],
        sidebar: enSidebar,
        editLink: {
          pattern: 'https://github.com/gustavocabreira/rbac/edit/main/docs/:path',
          text: 'Edit this page on GitHub',
        },
        footer: {
          message: 'Released under the MIT License.',
          copyright: 'Copyright © Huggy',
        },
      },
    },
    'pt-br': {
      label: 'Português (BR)',
      lang: 'pt-BR',
      link: '/pt-br/',
      themeConfig: {
        nav: [
          { text: 'Guia', link: '/pt-br/guide/introduction' },
          { text: 'Referência da API', link: '/pt-br/guide/api-reference' },
          { text: 'GitHub', link: 'https://github.com/gustavocabreira/rbac' },
        ],
        sidebar: ptBrSidebar,
        editLink: {
          pattern: 'https://github.com/gustavocabreira/rbac/edit/main/docs/:path',
          text: 'Editar esta página no GitHub',
        },
        footer: {
          message: 'Lançado sob a Licença MIT.',
          copyright: 'Copyright © Huggy',
        },
      },
    },
  },

  themeConfig: {
    logo: '/logo.svg',
    search: {
      provider: 'local',
    },
  },
})
