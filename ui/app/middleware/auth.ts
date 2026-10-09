export default defineNuxtRouteMiddleware(async (to) => {
	const { token, user, fetchUser } = useAuth()

	const loginUrl = `/login?redirect=${encodeURIComponent(to.fullPath)}`

	if (!token.value) {
		return navigateTo(loginUrl)
	}

	if (!user.value) {
		const authenticatedUser = await fetchUser()

		if (!authenticatedUser) {
			return navigateTo(loginUrl)
		}
	}
})