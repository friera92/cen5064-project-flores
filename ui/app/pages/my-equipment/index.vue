<script setup lang="ts">
import type { PaginatedTools } from '~/types/tool'

definePageMeta({ middleware: 'auth' })
const { listMyTools, deleteTool } = useTools()
const result = ref<PaginatedTools | null>(null)
const busy = ref(false)
const deletingId = ref<number | null>(null)
const page = ref(1)
const error = ref('')

async function load() {
  busy.value = true
  error.value = ''
  try { result.value = await listMyTools({ per_page: 12, page: page.value }) }
  catch (e: any) { error.value = e?.data?.message || 'Could not load your tools.' }
  finally { busy.value = false }
}

async function changePage(next: number) { page.value = next; await load() }

async function remove(id: number, title: string) {
  if (!window.confirm(`Remove "${title}" from your listings?`)) return
  deletingId.value = id
  error.value = ''
  try {
    await deleteTool(id)
    await load()
    if (result && result.value && result.value.data.length === 0 && page.value > 1) {
      await changePage(page.value - 1)
    }
  }
  catch (e: any) { error.value = e?.data?.message || 'Unable to remove this tool. It may have pending reservations.' }
  finally { deletingId.value = null }
}

onMounted(load)
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="py-10 md:py-14">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-[#172B25]">My Listings</h1>
          <p class="mt-2 text-[#66736D]">Manage tools that you have added to NeighbourLend.</p>
        </div>
        <NuxtLink to="/my-equipment/create" class="rounded-xl bg-[#176B52] px-5 py-3 font-semibold text-white">+ List an Item</NuxtLink>
      </div>
      <p v-if="error" role="alert" class="mt-6 rounded-lg bg-red-50 p-4 text-red-700">{{ error }}</p>
      <p v-if="busy" class="mt-8">Loading your listings...</p>
      <p v-else-if="!result?.data.length" class="mt-8 text-[#66736D]">You have not listed any tools yet.</p>
      <div v-if="!busy && result?.data.length" class="mt-8 grid gap-4">
        <article v-for="tool in result.data" :key="tool.id" class="flex flex-wrap items-center gap-5 rounded-2xl border border-[#E5E9E4] bg-white p-5">
          <img v-if="tool.picture_url" :src="tool.picture_url" :alt="tool.title" class="h-24 w-28 rounded-xl object-cover" />
          <div v-else class="flex h-24 w-28 items-center justify-center rounded-xl bg-[#E9F4ED] text-xs text-[#176B52]">No image</div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg font-bold text-[#172B25]">{{ tool.title }}</h2>
            <p class="mt-1 text-sm text-[#66736D]">{{ tool.category?.name }} · ${{ Number(tool.daily_rate).toFixed(2) }}/day · {{ tool.availability_status }}</p>
          </div>
          <div class="flex gap-2">
            <NuxtLink :to="`/my-equipment/${tool.id}/edit`" class="rounded-xl border px-4 py-2">Edit</NuxtLink>
            <button :disabled="deletingId === tool.id" class="rounded-xl border border-red-200 px-4 py-2 text-red-700 disabled:opacity-50" @click="remove(tool.id, tool.title)">
              {{ deletingId === tool.id ? 'Removing...' : 'Remove' }}
            </button>
          </div>
        </article>
      </div>
      <div v-if="result && result.last_page > 1" class="mt-8 flex justify-center gap-4">
        <button :disabled="busy || page <= 1" class="rounded-lg border px-4 py-2 disabled:opacity-50" @click="changePage(page - 1)">Previous</button>
        <span class="self-center">Page {{ page }} / {{ result.last_page }}</span>
        <button :disabled="busy || page >= result.last_page" class="rounded-lg border px-4 py-2 disabled:opacity-50" @click="changePage(page + 1)">Next</button>
      </div>
    </UContainer>
  </div>
</template>
