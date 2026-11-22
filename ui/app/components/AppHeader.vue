
<script setup lang="ts">
const { user, isAuthenticated, logout } = useAuth()

const isHydrated = ref(false)

const isAdmin = computed(() =>
  isHydrated.value && user.value?.is_admin === true
)

onMounted(() => {
  isHydrated.value = true
})

const publicNavigation = [
  { label: 'Explore', to: '/' }
]

const authenticatedNavigation = [
  { label: 'My Reservations', to: '/reservations' },
  { label: 'Incoming Reservations', to: '/lender/reservations' },
  { label: 'Disputes', to: '/disputes' },
  { label: 'My Listings', to: '/my-equipment' }
]

const navigation = computed(() => {
  if (!isAuthenticated.value) {
    return publicNavigation
  }

  if (isAdmin.value) {
    return []
  }

  return [
    ...publicNavigation,
    ...authenticatedNavigation
  ]
})

const displayName = computed(() =>
  isHydrated.value
    ? (user.value?.name ?? 'User')
    : 'User'
)

async function handleLogout() {
  const wasAdmin = isAdmin.value

  await logout()

  await navigateTo(wasAdmin ? '/login' : '/')
}
</script>

<template>
  <header class="border-b border-[#E5E9E4] bg-white">

    <!-- FIRST ROW: LOGO AND USER ACTIONS -->
    <UContainer>
      <div
        class="flex min-h-20 items-center justify-between gap-4 py-3"
      >
        <!-- Logo -->
        <NuxtLink
          :to="isAdmin ? '/admin' : '/'"
          class="flex shrink-0 items-center gap-2"
        >
          <div
            class="flex size-10 items-center justify-center rounded-xl bg-[#176B52] text-white"
          >
            <UIcon
              name="i-lucide-handshake"
              class="size-6"
            />
          </div>

          <span class="whitespace-nowrap text-xl font-bold tracking-tight text-[#172B25]">
            Neighbour<span class="text-[#176B52]">Lend</span>
          </span>
        </NuxtLink>

        <!-- User actions -->
        <div class="flex items-center gap-3">

          <template v-if="isAuthenticated">
            <UButton v-if="!isAdmin"
              to="/my-equipment/create"
              label="List an Item"
              icon="i-lucide-plus"
              class="hidden shrink-0 bg-[#176B52] text-white hover:bg-[#124D3D] md:flex"
            />

            <div
              class="hidden min-w-0 items-center gap-2 text-sm font-medium text-[#172B25] lg:flex"
            >
              <UIcon
                name="i-lucide-user-round"
                class="size-5 shrink-0 text-[#176B52]"
              />

              <span
                class="max-w-56 truncate"
                :title="displayName"
              >
                Hi, {{ displayName }}
              </span>
            </div>

            <UButton
              label="Log out"
              icon="i-lucide-log-out"
              color="neutral"
              variant="ghost"
              class="hidden shrink-0 sm:flex"
              @click="handleLogout"
            />

            <UButton
              icon="i-lucide-log-out"
              color="neutral"
              variant="ghost"
              class="sm:hidden"
              aria-label="Log out"
              @click="handleLogout"
            />
          </template>

          <template v-else>
            <UButton
              to="/login"
              label="Log in"
              icon="i-lucide-user-round"
              color="neutral"
              variant="ghost"
              class="hidden sm:flex"
            />

            <UButton
              to="/login"
              icon="i-lucide-user-round"
              color="neutral"
              variant="ghost"
              class="sm:hidden"
              aria-label="Log in"
            />

            <UButton
              to="/register"
              label="Sign up"
              class="hidden bg-[#176B52] text-white hover:bg-[#124D3D] sm:flex"
            />
          </template>

        </div>
      </div>
    </UContainer>

    <!-- SECOND ROW: NAVIGATION -->
    <div v-if="!isAdmin" class="border-t border-[#E5E9E4]">
      <UContainer>

        <!-- Desktop and tablet -->
        <nav
          aria-label="Main navigation"
          class="hidden min-w-0 items-center gap-2 overflow-x-auto py-2 sm:flex"
        >
          <NuxtLink
            v-for="item in navigation"
            :key="item.to"
            :to="item.to"
            class="shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium text-[#66736D] transition-colors hover:bg-[#E9F4ED] hover:text-[#176B52]"
            active-class="!bg-[#E9F4ED] !text-[#176B52]"
          >
            {{ item.label }}
          </NuxtLink>
        </nav>

        <!-- Mobile navigation -->
        <div
          class="flex items-center justify-between py-2 sm:hidden"
        >
          <span class="text-sm font-medium text-[#66736D]">
            Navigation
          </span>

          <UDropdownMenu :items="navigation">
            <UButton
              icon="i-lucide-menu"
              label="Menu"
              color="neutral"
              variant="ghost"
              aria-label="Open navigation menu"
            />
          </UDropdownMenu>
        </div>

      </UContainer>
    </div>

  </header>
</template>
