<script setup lang="ts">
import type { ReturnedCondition, ReservationStatus } from '~/types/reservation'
import { getAdminErrorMessage } from '~/utils/getAdminErrorMessage'

definePageMeta({ middleware: 'admin' })

type Dispute = {
  id: number
  tool_id: number
  borrower_id: number
  start_date: string
  end_date: string
  created_at: string
  updated_at: string
  status: ReservationStatus
  returned_condition: ReturnedCondition | null
  inspection_notes: string | null
  borrower: { id: number; name: string } | null
  tool: { id: number; title: string; owner: { id: number; name: string } | null } | null
}

type DisputePage = {
  data: Dispute[]
  current_page: number
  last_page: number
  total: number
}

const { request } = useApi()
const search = ref('')
const condition = ref('')
const fromDate = ref('')
const toDate = ref('')
const page = ref(1)
const result = ref<DisputePage | null>(null)
const loading = ref(true)
const errorMessage = ref('')

function displayDate(value: string): string {
  const date = new Date(value.slice(0, 10) + 'T00:00:00Z')
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('en-US', {
    year: 'numeric', month: 'short', day: 'numeric', timeZone: 'UTC',
  }).format(date)
}

async function loadDisputes() {
  loading.value = true
  errorMessage.value = ''
  try {
    const query = new URLSearchParams({ page: String(page.value) })
    if (search.value.trim()) query.set('q', search.value.trim())
    if (condition.value) query.set('condition', condition.value)
    if (fromDate.value) query.set('from', fromDate.value)
    if (toDate.value) query.set('to', toDate.value)
    result.value = await request<DisputePage>(`/api/admin/disputes?${query}`)
  } catch (error) {
    errorMessage.value = getAdminErrorMessage(error)
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  page.value = 1
  loadDisputes()
}

function changePage(next: number) {
  if (!result.value || next < 1 || next > result.value.last_page) return
  page.value = next
  loadDisputes()
}

onMounted(loadDisputes)
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="py-10 md:py-14">
      <NuxtLink to="/admin" class="text-sm font-semibold text-[#176B52]">← Administration</NuxtLink>
      <h1 class="mt-5 text-3xl font-bold text-[#172B25]">Dispute Management</h1>
      <p class="mt-2 text-[#66736D]">All reported disputes across the platform. This view is read-only until resolution rules are defined.</p>

      <form class="mt-7 grid gap-4 rounded-2xl border border-[#E5E9E4] bg-white p-5 md:grid-cols-4" @submit.prevent="applyFilters">
        <label class="text-sm font-semibold text-[#172B25]">Search
          <input v-model="search" maxlength="100" placeholder="ID, tool or participant" class="mt-2 block w-full rounded-xl border border-[#E5E9E4] px-3 py-2 font-normal" />
        </label>
        <label class="text-sm font-semibold text-[#172B25]">Condition
          <select v-model="condition" class="mt-2 block w-full rounded-xl border border-[#E5E9E4] px-3 py-2 font-normal">
            <option value="">All</option><option value="DAMAGED">Damaged</option><option value="BROKEN">Broken</option><option value="MISSING_PARTS">Missing parts</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-[#172B25]">Rental start on/after
          <input v-model="fromDate" type="date" class="mt-2 block w-full rounded-xl border border-[#E5E9E4] px-3 py-2 font-normal" />
        </label>
        <label class="text-sm font-semibold text-[#172B25]">Rental end on/before
          <input v-model="toDate" type="date" class="mt-2 block w-full rounded-xl border border-[#E5E9E4] px-3 py-2 font-normal" />
        </label>
        <button type="submit" :disabled="loading" class="rounded-xl bg-[#176B52] px-5 py-2 text-sm font-semibold text-white disabled:opacity-50 md:col-span-4 md:justify-self-start">{{ loading ? 'Loading...' : 'Apply filters' }}</button>
      </form>

      <p v-if="errorMessage" role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ errorMessage }}</p>
      <p v-if="loading" role="status" class="mt-5 text-[#66736D]">Loading disputes...</p>
      <template v-else-if="result && !errorMessage">
        <p class="mt-6 text-sm text-[#66736D]">{{ result.total }} dispute(s) found</p>
        <p v-if="!result.data.length" class="mt-5 rounded-2xl border bg-white p-8 text-center text-[#66736D]">No disputes match these filters.</p>
        <div v-else class="mt-4 grid gap-4">
          <article v-for="item in result.data" :key="item.id" class="rounded-2xl border border-[#E5E9E4] bg-white p-6">
            <div class="flex flex-wrap justify-between gap-3">
              <div>
                <p class="text-xs text-[#66736D]">Reservation #{{ item.id }}</p>
                <h2 class="mt-1 text-lg font-semibold text-[#172B25]">{{ item.tool?.title ?? 'Equipment unavailable' }}</h2>
              </div>
              <span class="self-start rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">{{ item.status }}</span>
            </div>
            <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div><dt class="font-semibold text-[#172B25]">Lender</dt><dd>{{ item.tool?.owner?.name ?? 'Unavailable' }}</dd></div>
              <div><dt class="font-semibold text-[#172B25]">Borrower</dt><dd>{{ item.borrower?.name ?? 'Unavailable' }}</dd></div>
              <div><dt class="font-semibold text-[#172B25]">Returned condition</dt><dd>{{ item.returned_condition?.replaceAll('_', ' ') ?? 'Not recorded' }}</dd></div>
              <div><dt class="font-semibold text-[#172B25]">Start date</dt><dd>{{ displayDate(item.start_date) }}</dd></div>
              <div><dt class="font-semibold text-[#172B25]">End date</dt><dd>{{ displayDate(item.end_date) }}</dd></div>
              <div><dt class="font-semibold text-[#172B25]">Reported</dt><dd>{{ displayDate(item.updated_at) }}</dd></div>
            </dl>
            <div class="mt-5 border-t border-[#E5E9E4] pt-4">
              <h3 class="text-sm font-semibold text-[#172B25]">Inspection notes</h3>
              <p class="mt-1 whitespace-pre-wrap text-sm text-[#66736D]">{{ item.inspection_notes || 'No notes were provided.' }}</p>
            </div>
          </article>
        </div>
        <div v-if="result.last_page > 1" class="mt-6 flex items-center justify-between gap-4 text-sm">
          <button type="button" :disabled="page <= 1 || loading" class="rounded-xl border bg-white px-4 py-2 disabled:opacity-40" @click="changePage(page - 1)">Previous</button>
          <span>Page {{ result.current_page }} of {{ result.last_page }}</span>
          <button type="button" :disabled="page >= result.last_page || loading" class="rounded-xl border bg-white px-4 py-2 disabled:opacity-40" @click="changePage(page + 1)">Next</button>
        </div>
      </template>
    </UContainer>
  </div>
</template>
