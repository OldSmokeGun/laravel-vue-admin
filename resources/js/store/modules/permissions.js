import componentsMap from '@/js/router/map'
import { baseRoutes, resetRouter } from '@/js/router/routes'

const defaultState = () => {
  return {
    routes: [],
    addRoutes: [],
    maps: []
  }
}

export default {
  namespaced: true,
  state: defaultState(),
  actions: {
    setRoutes({ commit }, permissions) {
      return new Promise(resolve => {
        let generateRoutes = (routes) => {
          let addRoutes = []
          for (let item of routes) {
            let route = {}
            route.path = item.identification
            if (!Number(item.parent_id) && !item.component) {
              route.component = componentsMap['layout']
            } else {
              route.component = (item.component in componentsMap) ? componentsMap[item.component] : componentsMap['missing']
            }
            route.meta = { title: item.title, icon: item.icon }
            route.redirect = item.redirect
            route.hidden = item.display ? false : true
            if (item.children.length) {
              route.children = generateRoutes(item.children)
            }
            addRoutes.push(route)
          }
          return addRoutes
        }

        let addRoutes = generateRoutes(permissions.routes)

        addRoutes.push({
          path: '*',
          name: '404',
          component: () => import('@/js/components/error/404'),
          hidden: true
        })

        commit('SET_ROUTES', { addRoutes, maps: permissions.maps })
        resolve(addRoutes)
      })
    },
    resetRoutes({ commit }) {
      return new Promise(resolve => {
        commit('RESET_ROUTES')
        resolve()
      })
    }
  },
  mutations: {
    SET_ROUTES(state, routes) {
      state.routes = [...baseRoutes, ...routes.addRoutes]
      state.addRoutes = routes.addRoutes
      state.maps = routes.maps
    },
    RESET_ROUTES(state) {
      Object.assign(state, defaultState())
      resetRouter()
    }
  }
}