<script setup lang="ts">
const email = ref('')
const password = ref('')

const loading = ref(false)
const errorMessage = ref('')

const { login } = useAuth()

async function handleLogin() {
  errorMessage.value = ''
  loading.value = true

  try {
    await login({
      email: email.value,
      password: password.value
    })

    await navigateTo('/')
  } catch (error: any) {
    console.error(error)

    if (error?.status === 422) {
      errorMessage.value =
        'The email or password you entered is incorrect.'
    } else {
      errorMessage.value =
        'Unable to log in. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />

    <main
      class="mx-auto flex max-w-7xl justify-center px-6 py-16"
    >
      <div
        class="w-full max-w-md rounded-2xl border
               border-[#E5E9E4] bg-white p-8 shadow-sm"
      >
        <div class="mb-8 text-center">
          <h1
            class="text-3xl font-bold text-[#172B25]"
          >
            Welcome back
          </h1>

          <p class="mt-2 text-sm text-[#66736D]">
            Sign in to your NeighbourLend account
          </p>
        </div>

        <form
          class="space-y-5"
          @submit.prevent="handleLogin"
        >
          <label class="block">
            <span
              class="mb-2 block text-sm font-medium
                     text-[#172B25]"
            >
              Email
            </span>

            <input
              v-model="email"
              type="email"
              required
              autocomplete="email"
              class="w-full rounded-lg border
                     border-[#E5E9E4] px-4 py-3
                     outline-none focus:border-[#176B52]"
              placeholder="you@example.com"
            >
          </label>

          <label class="block">
            <span
              class="mb-2 block text-sm font-medium
                     text-[#172B25]"
            >
              Password
            </span>

            <input
              v-model="password"
              type="password"
              required
              autocomplete="current-password"
              class="w-full rounded-lg border
                     border-[#E5E9E4] px-4 py-3
                     outline-none focus:border-[#176B52]"
              placeholder="Enter your password"
            >
          </label>

          <p
            v-if="errorMessage"
            class="text-sm text-red-600"
          >
            {{ errorMessage }}
          </p>

          <button
            type="submit"
            :disabled="loading"
            class="w-full rounded-lg bg-[#176B52]
                   px-4 py-3 font-semibold text-white
                   transition hover:bg-[#124D3D]
                   disabled:cursor-not-allowed
                   disabled:opacity-60"
          >
            {{ loading ? 'Signing in...' : 'Sign in' }}
          </button>
        </form>
      </div>
    </main>
  </div>
</template>