import { generateFilePath } from '@nextcloud/router'
import Vue from 'vue'
import App from './App.vue'

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('infomate', '', 'js/')

Vue.mixin({ methods: { t, n } })

const View = Vue.extend(App)
new View().$mount('#infomate')
