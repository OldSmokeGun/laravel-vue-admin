export default {
  'layout': () => import('@/js/layout/index'),
  'missing': () => import('@/js/components/error/missing'),
  'admins': () => import('@/js/views/admins/index'),
  'roles': () => import('@/js/views/roles/index'),
  'permissions': () => import('@/js/views/permissions/index'),
  'login': () => import('@/js/views/login/Login')
}