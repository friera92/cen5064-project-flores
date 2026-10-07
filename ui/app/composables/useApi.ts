export function useApi() {
  const config = useRuntimeConfig()

  const token = useCookie<string | null>('auth_token')

  function request<T>(
    path: string,
    options: Parameters<typeof $fetch<T>>[1] = {}
  ) {
    return $fetch<T>(
      `${config.public.apiBase}${path}`,
      {
        ...options,

        headers: {
          ...options.headers,
          ...(token.value
            ? {
                Authorization: `Bearer ${token.value}`
              }
            : {})
        }
      }
    )
  }

  return { request }
}