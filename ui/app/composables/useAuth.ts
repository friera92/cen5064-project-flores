interface AuthUser {
  id: number
  name: string
  email: string
}

interface LoginCredentials {
  email: string
  password: string
}

interface LoginResponse {
  message: string
  user: AuthUser
  token: string
}

interface RegisterData {
  name: string
  email: string
  phone?: string | null
  address?: string | null
  password: string
  password_confirmation: string
}

export function useAuth() {
  const { request } = useApi()

  const token = useCookie<string | null>('auth_token', {
    default: () => null,
    sameSite: 'lax'
  })

  const user = useState<AuthUser | null>('auth_user', () => null)

  const isAuthenticated = computed(() => Boolean(token.value))

  async function login(credentials: LoginCredentials) {
    const response = await request<LoginResponse>('/api/login', {
      method: 'POST',
      body: credentials
    })

    token.value = response.token
    user.value = response.user

    return response
  }

  async function fetchUser() {
    if (!token.value) {
      user.value = null
      return null
    }

    try {
      const response = await request<AuthUser>('/api/me')

      user.value = response

      return response
    } catch {
      token.value = null
      user.value = null

      return null
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await request('/api/logout', {
          method: 'POST'
        })
      }
    } finally {
      token.value = null
      user.value = null
    }
  }

  async function register(data: RegisterData) {
    const response = await request<LoginResponse>('/api/register', {
      method: 'POST',
      body: data
    })

    token.value = response.token
    user.value = response.user

    return response
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    fetchUser,
    logout,
    register
  }
}