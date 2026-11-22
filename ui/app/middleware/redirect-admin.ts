export default defineNuxtRouteMiddleware(async () => {
  // User data is loaded from the Laravel API in the browser.
  if (import.meta.server) return

  const { token, user, fetchUser } = useAuth()

  if (!token.value) return

  const currentUser = user.value ?? await fetchUser()

  if (currentUser?.is_admin === true) {
    return navigateTo('/admin', { replace: true })
  }
})