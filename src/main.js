import Vue from 'vue'
import App from './App.vue'

/*
new Vue(
    {
        render: h => h(App)
    }
).$mount('#app')
*/

Vue.mixin({ methods: { t, n } })

const View = Vue.extend(App)
new View().$mount('#infomate')
