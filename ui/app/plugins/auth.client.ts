export default defineNuxtPlugin(async () => {
    console.log('auth plugin loaded')
    const { token, user, fetchUser } = useAuth()

    if (token.value && !user.value) {
        await fetchUser()
    }
})