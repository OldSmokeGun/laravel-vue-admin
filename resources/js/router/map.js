export default {
  'layout': () => import('@/js/layout/index'),
  'missing': () => import('@/js/components/error/missing'),
  'admin': () => import('@/js/views/admin/index'),
  'role': () => import('@/js/views/role/index'),
  'permission': () => import('@/js/views/permission/index'),
  'login': () => import('@/js/views/login/Login')
}