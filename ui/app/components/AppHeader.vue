<script setup lang="ts">
const { user, isAuthenticated, logout } = useAuth()

const publicNavigation = [
  { label: 'Explore', to: '/' }
]

const authenticatedNavigation = [
  { label: 'My Reservations', to: '/reservations' },
  { label: 'My Listings', to: '/my-equipment' }
]

const navigation = computed(() => {
  if (!isAuthenticated.value) {
    return publicNavigation
  }

  return [
    ...publicNavigation,
    ...authenticatedNavigation
  ]
})

async function handleLogout() {
  await logout()
  await navigateTo('/')
}
</script>

<template>
  <header class="border-b border-[#E5E9E4] bg-white">
    <UContainer
      class="flex min-h-20 items-center justify-between gap-4"
    >
      <!-- Logo -->
      <NuxtLink
        to="/"
        class="flex items-center gap-2"
      >
        <div
          class="flex size-10 items-center justify-center
                 rounded-xl bg-[#176B52] text-white"
        >
          <UIcon name="i-lucide-handshake" class="size-6" />
        </div>

        <span
          class="text-xl font-bold tracking-tight text-[#172B25]"
        >
          Neighbour<span class="text-[#176B52]">Lend</span>
        </span>
      </NuxtLink>

      <!-- Desktop navigation -->
      <nav class="hidden items-center gap-8 md:flex">
        <NuxtLink
          v-for="item in navigation"
          :key="item.to"
          :to="item.to"
          class="text-sm font-medium text-[#66736D]
                 transition-colors hover:text-[#176B52]"
          active-class="!text-[#176B52]"
        >
          {{ item.label }}
        </NuxtLink>
      </nav>

      <!-- Actions -->
      <div class="flex items-center gap-3">
        <!-- Authenticated user actions -->
        <template v-if="isAuthenticated">
          <UButton
            to="/my-equipment/create"
            label="List an Item"
            icon="i-lucide-plus"
            class="hidden bg-[#176B52] text-white
                   hover:bg-[#124D3D] sm:flex"
          />

          <div
            class="hidden items-center gap-2 text-sm font-medium
                   text-[#172B25] sm:flex"
          >
            <UIcon
              name="i-lucide-user-round"
              class="size-5 text-[#176B52]"
            />
            <span>
              Hi, {{ user?.name ?? 'User' }}
            </span>
          </div>

          <UButton
            label="Log out"
            icon="i-lucide-log-out"
            color="neutral"
            variant="ghost"
            class="hidden sm:flex"
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

        <!-- Guest actions -->
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
            class="hidden bg-[#176B52] text-white
                   hover:bg-[#124D3D] sm:flex"
          />
        </template>

        <UDropdownMenu
          :items="navigation"
          class="md:hidden"
        >
          <UButton
            icon="i-lucide-menu"
            color="neutral"
            variant="ghost"
            aria-label="Open navigation"
          />
        </UDropdownMenu>
      </div>
    </UContainer>
  </header>
</template>
