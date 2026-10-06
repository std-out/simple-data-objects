import { h } from 'vue'
import DefaultTheme from 'vitepress/theme'
import Breadcrumb from './Breadcrumb.vue'
import HeroBench from './HeroBench.vue'
import './custom.css'

export default {
  extends: DefaultTheme,
  Layout() {
    return h(DefaultTheme.Layout, null, {
      'doc-before': () => h(Breadcrumb),
      'home-hero-image': () => h(HeroBench),
    })
  },
}
