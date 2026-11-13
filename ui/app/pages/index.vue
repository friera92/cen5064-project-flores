<script setup lang="ts">
import type { Category, PaginatedTools, ToolFilters } from '~/types/tool'

const { listTools, listCategories } = useTools()
const categories = ref<Category[]>([])
const result = ref<PaginatedTools | null>(null)
const busy = ref(false)
const error = ref('')
const page = ref(1)

const search = reactive({
  q: '',
  category_id: '',
  availability_status: '',
  min_rate: '',
  max_rate: '',
  condition: '',
})

async function fetchTools() {
  busy.value = true
  error.value = ''
  try {
    const filters: ToolFilters = { page: page.value, per_page: 12 }
    if (search.q.trim()) filters.q = search.q.trim()
    if (search.category_id) filters.category_id = Number(search.category_id)
    if (search.availability_status) filters.availability_status = search.availability_status as ToolFilters['availability_status']
    if (search.condition) filters.condition = search.condition as ToolFilters['condition']
    if (search.min_rate !== '') filters.min_rate = Number(search.min_rate)
    if (search.max_rate !== '') filters.max_rate = Number(search.max_rate)
    result.value = await listTools(filters)
  } catch (e: any) {
    error.value = e?.data?.message || 'Unable to load tools from the API.'
  } finally {
    busy.value = false
  }
}

function applyFilters() { page.value = 1; fetchTools() }
function resetFilters() {
  Object.assign(search, { q: '', category_id: '', availability_status: '', min_rate: '', max_rate: '', condition: '' })
  applyFilters()
}
function changePage(next: number) { page.value = next; fetchTools() }

onMounted(async () => {
  const [categoriesResult] = await Promise.allSettled([listCategories()])
  if (categoriesResult.status === 'fulfilled') categories.value = categoriesResult.value
  await fetchTools()
})
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="py-10 md:py-14">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-[#172B25]">Explore Equipment</h1>
          <p class="mt-2 text-[#66736D]">Browse tools listed by the community.</p>
        </div>
        <NuxtLink to="/my-equipment/create" class="rounded-xl bg-[#176B52] px-5 py-3 font-semibold text-white">List an Item</NuxtLink>
      </div>

      <form class="mt-8 grid gap-3 rounded-2xl border border-[#E5E9E4] bg-white p-5 sm:grid-cols-2 lg:grid-cols-7" @submit.prevent="applyFilters">
        <input v-model="search.q" aria-label="Search tools" placeholder="Search tools" class="rounded-lg border p-3 lg:col-span-2" />
        <select v-model="search.category_id" aria-label="Category" class="rounded-lg border p-3">
          <option value="">All categories</option>
          <option v-for="c in categories" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
        </select>
        <select v-model="search.availability_status" aria-label="Availability" class="rounded-lg border p-3">
          <option value="">All statuses</option>
          <option value="AVAILABLE">Available</option>
          <option value="IN_USE">In use</option>
          <option value="MAINTENANCE">Maintenance</option>
          <option value="RETIRED">Retired</option>
        </select>
        <select v-model="search.condition" aria-label="Tool condition" class="rounded-lg border p-3">
          <option value="">All conditions</option>
          <option value="NEW">New</option>
          <option value="GOOD">Good</option>
          <option value="FAIR">Fair</option>
          <option value="POOR">Poor</option>
          <option value="CREATED">Created</option>
        </select>
        <input v-model="search.min_rate" type="number" min="0" step="0.01" aria-label="Minimum daily rate" placeholder="Min $/day" class="rounded-lg border p-3" />
        <input v-model="search.max_rate" type="number" min="0" step="0.01" aria-label="Maximum daily rate" placeholder="Max $/day" class="rounded-lg border p-3" />
        <div class="flex gap-3 lg:col-span-7">
          <button type="submit" class="rounded-xl bg-[#176B52] px-5 py-2 text-white">Apply filters</button>
          <button type="button" class="rounded-xl border px-5 py-2" @click="resetFilters">Reset</button>
        </div>
      </form>

      <p v-if="error" role="alert" class="mt-5 rounded-lg bg-red-50 p-4 text-red-700">{{ error }}</p>
      <p v-else-if="busy" class="mt-8 text-[#66736D]">Loading tools from database...</p>
      <p v-else-if="!result?.data.length" class="mt-8 text-[#66736D]">No tools match these filters.</p>

      <div v-if="!busy && result?.data.length" class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <NuxtLink v-for="tool in result.data" :key="tool.id" :to="`/equipment/${tool.id}`" class="overflow-hidden rounded-2xl border border-[#E5E9E4] bg-white transition hover:shadow-md">
          <img v-if="tool.picture_url" :src="tool.picture_url" :alt="tool.title" class="h-48 w-full object-cover" />
          <div v-else class="flex h-48 items-center justify-center bg-[#E9F4ED] text-[#176B52]">No image available</div>
          <div class="p-5">
            <p class="text-xs uppercase tracking-wide text-[#66736D]">{{ tool.category?.name || 'Equipment' }}</p>
            <h2 class="mt-2 text-lg font-semibold text-[#172B25]">{{ tool.title }}</h2>
            <div class="mt-4 flex items-center justify-between text-sm">
              <span class="font-bold text-[#176B52]">${{ Number(tool.daily_rate).toFixed(2) }}/day</span>
              <span class="rounded-full bg-[#E9F4ED] px-3 py-1 text-[#176B52]">{{ tool.availability_status }}</span>
            </div>
          </div>
        </NuxtLink>
      </div>

      <div v-if="result && result.last_page > 1" class="mt-8 flex items-center justify-center gap-4">
        <button :disabled="busy || page <= 1" class="rounded-lg border px-4 py-2 disabled:opacity-50" @click="changePage(page - 1)">Previous</button>
        <span>Page {{ page }} / {{ result.last_page }}</span>
        <button :disabled="busy || page >= result.last_page" class="rounded-lg border px-4 py-2 disabled:opacity-50" @click="changePage(page + 1)">Next</button>
      </div>
    </UContainer>
  </div>
</template>
