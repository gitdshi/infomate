import Vue from 'vue'
import App from './views/App.vue'
Vue.mixin({ methods: { t, n } })

const VueApp = Vue.extend(App)
new VueApp().$mount('#content')
