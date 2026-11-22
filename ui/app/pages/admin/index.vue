<script setup lang="ts">
definePageMeta({ middleware: 'admin' })
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="py-10 md:py-14">
      <h1 class="text-3xl font-bold text-[#172B25]">Administration</h1>
      <p class="mt-2 text-[#66736D]">Manage catalog categories and review disputes reported through inspections.</p>
      <div class="mt-8 grid gap-5 md:grid-cols-2">
        <NuxtLink to="/admin/categories" class="rounded-2xl border border-[#E5E9E4] bg-white p-7 hover:border-[#176B52]">
          <h2 class="text-xl font-bold text-[#172B25]">Categories</h2>
          <p class="mt-2 text-sm text-[#66736D]">Create, update, and remove unused categories.</p>
        </NuxtLink>
        <NuxtLink to="/admin/disputes" class="rounded-2xl border border-[#E5E9E4] bg-white p-7 hover:border-[#176B52]">
          <h2 class="text-xl font-bold text-[#172B25]">Disputes</h2>
          <p class="mt-2 text-sm text-[#66736D]">View all reported disputes and inspection notes. Resolution is not available yet.</p>
        </NuxtLink>
      </div>
    </UContainer>
  </div>
</template>
