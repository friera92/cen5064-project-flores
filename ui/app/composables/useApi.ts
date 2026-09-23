
export function useApi() {
  const config = useRuntimeConfig()

  function request<T>(
    path: string,
    options: Parameters<typeof $fetch<T>>[1] = {}
  ) {
    return $fetch<T>(
      `${config.public.apiBase}${path}`,
      {
        credentials: 'include',
        ...options
      }
    )
  }

  return { request }
}