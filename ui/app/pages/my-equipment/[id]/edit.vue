<script setup lang="ts">
import type { Category, Tool, ToolInput } from '~/types/tool'

definePageMeta({ middleware: 'auth' })
const route = useRoute()
const id = Number(route.params.id)
const { getTool, listCategories, updateTool } = useTools()
const tool = ref<Tool | null>(null)
const categories = ref<Category[]>([])
const busy = ref(false)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const [loadedTool, loadedCategories] = await Promise.all([getTool(id), listCategories()])
    tool.value = loadedTool
    categories.value = loadedCategories
  } catch (e: any) {
    error.value = e?.data?.message || 'Could not load tool.'
  } finally { loading.value = false }
})

async function submit(payload: ToolInput) {
  busy.value = true
  error.value = ''
  try {
    await updateTool(id, payload)
    await navigateTo('/my-equipment')
  } catch (e: any) {
    error.value = e?.data?.message || 'Unable to update tool.'
  } finally { busy.value = false }
}
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="max-w-3xl py-10 md:py-14">
      <NuxtLink to="/my-equipment" class="text-sm font-semibold text-[#176B52]">← My Listings</NuxtLink>
      <h1 class="my-6 mt-5 text-3xl font-bold text-[#172B25]">Edit Tool</h1>
      <p v-if="error" role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-red-700">{{ error }}</p>
      <p v-if="loading">Loading tool...</p>
      <ToolForm v-else-if="tool" :initial="{ ...tool, daily_rate: Number(tool.daily_rate) }" :categories="categories" :busy="busy" @submit="submit" />
    </UContainer>
  </div>
</template>
