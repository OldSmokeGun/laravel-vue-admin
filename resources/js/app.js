import Vue from 'vue'
import router from '@/js/router'
import store from '@/js/store'
import App from '@/js/views/App'

import ElementUI from 'element-ui'
import 'element-ui/lib/theme-chalk/index.css'

import '@/js/directives'

import 'normalize.css/normalize.css'
import '@/styles/index.scss'
import '@/js/icons'
import '@/js/router/guard'

Vue.use(ElementUI)

Vue.config.productionTip = false

const app = new Vue({
    router,
    store,
    render: h => h(App),
}).$mount('#app')
