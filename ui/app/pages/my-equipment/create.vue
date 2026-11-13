<script setup lang="ts">
import type { Category, ToolInput } from '~/types/tool'

definePageMeta({ middleware: 'auth' })
const { createTool, listCategories } = useTools()
const categories = ref<Category[]>([])
const busy = ref(false)
const error = ref('')

onMounted(async () => {
  try { categories.value = await listCategories() }
  catch (e: any) { error.value = e?.data?.message || 'Unable to load categories.' }
})

async function submit(payload: ToolInput) {
  busy.value = true
  error.value = ''
  try {
    await createTool(payload)
    await navigateTo('/my-equipment')
  } catch (e: any) {
    error.value = e?.data?.message || 'Unable to create tool.'
  } finally { busy.value = false }
}
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="max-w-3xl py-10 md:py-14">
      <NuxtLink to="/my-equipment" class="text-sm font-semibold text-[#176B52]">← My Listings</NuxtLink>
      <h1 class="mt-5 text-3xl font-bold text-[#172B25]">List a Tool</h1>
      <p class="my-6 text-[#66736D]">Add an item to the community catalog.</p>
      <p v-if="error" role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-red-700">{{ error }}</p>
      <ToolForm :categories="categories" :busy="busy" @submit="submit" />
    </UContainer>
  </div>
</template>
