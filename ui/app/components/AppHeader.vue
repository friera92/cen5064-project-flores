
<script setup lang="ts">
const navigation = [
  { label: 'Explore', to: '/' },
  { label: 'My Reservations', to: '/reservations' },
  { label: 'My Listings', to: '/my-equipment' }
]
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
        <UButton
          to="/my-equipment/create"
          label="List an Item"
          icon="i-lucide-plus"
          class="hidden bg-[#176B52] text-white
                 hover:bg-[#124D3D] sm:flex"
        />

        <UButton
          to="/auth/login"
          icon="i-lucide-user-round"
          color="neutral"
          variant="ghost"
          aria-label="Account"
        />

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