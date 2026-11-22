/** UI guard only; Laravel separately enforces admin authorization on every endpoint. */
export default defineNuxtRouteMiddleware(async (to) => {
  const { token, fetchUser } = useAuth()

  if (!token.value) {
    return navigateTo(`/login?redirect=${encodeURIComponent(to.fullPath)}`)
  }

  // The Laravel API is called from the browser in the current Docker setup.
  // No administrative data is rendered server-side; the API enforces access.
  if (import.meta.server) return

  // Always revalidate the current role, not a potentially stale cached UI user.
  const currentUser = await fetchUser()
  if (!currentUser) {
    return navigateTo(`/login?redirect=${encodeURIComponent(to.fullPath)}`)
  }

  if (currentUser.is_admin !== true) {
    return navigateTo('/')
  }
})
