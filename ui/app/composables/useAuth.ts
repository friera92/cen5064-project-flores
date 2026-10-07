interface LoginCredentials {
  email: string
  password: string
}

interface AuthUser {
  id: number
  name: string
  email: string
}

interface LoginResponse {
  message: string
  user: AuthUser
  token: string
}

export function useAuth() {
  const { request } = useApi()

  const token = useCookie<string | null>('auth_token', {
    default: () => null
  })

  const user = useState<AuthUser | null>(
    'auth_user',
    () => null
  )

  async function login(credentials: LoginCredentials) {
    const response = await request<LoginResponse>(
      '/api/login',
      {
        method: 'POST',
        body: credentials
      }
    )

    token.value = response.token
    user.value = response.user

    return response
  }

  return {
    user,
    token,
    login
  }
}