<script setup lang="ts">
import type { Reservation } from '~/types/reservation'
import type { LenderReservation } from '~/types/lender-reservation'
import { getReservationErrorMessage } from '~/utils/getReservationErrorMessage'

definePageMeta({ middleware: 'auth' })

type Role = 'BORROWER' | 'LENDER'
type RoleFilter = 'ALL' | Role

type DisputeEntry = {
  id: number
  role: Role
  toolTitle: string
  pictureUrl: string | null
  borrowerName: string | null
  returnedCondition: string | null
  startDate: string
  endDate: string
  detailUrl: string
}

const { getReservations, getReceivedReservations } = useReservations()
const filter = ref<RoleFilter>('ALL')
const loading = ref(true)
const refreshing = ref(false)
const errorMessage = ref('')
const warningMessage = ref('')
const disputes = ref<DisputeEntry[]>([])

const filteredDisputes = computed(() => filter.value === 'ALL'
  ? disputes.value
  : disputes.value.filter(item => item.role === filter.value))

const borrowerCount = computed(() => disputes.value.filter(item => item.role === 'BORROWER').length)
const lenderCount = computed(() => disputes.value.filter(item => item.role === 'LENDER').length)

const tabs: Array<{ label: string; value: RoleFilter }> = [
  { label: 'All disputes', value: 'ALL' },
  { label: 'As borrower', value: 'BORROWER' },
  { label: 'As lender', value: 'LENDER' },
]

function displayDate(value: string): string {
  const day = value.slice(0, 10)
  const date = new Date(`${day}T00:00:00Z`)
  return Number.isNaN(date.getTime()) ? day : new Intl.DateTimeFormat('en-US', {
    month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC',
  }).format(date)
}

async function loadDisputes(): Promise<void> {
  refreshing.value = true
  loading.value = disputes.value.length === 0
  errorMessage.value = ''
  warningMessage.value = ''

  try {
    const [borrowedResult, receivedResult] = await Promise.allSettled([
      getReservations(),
      getReceivedReservations(),
    ])

    if (borrowedResult.status === 'rejected' && receivedResult.status === 'rejected') {
      errorMessage.value = getReservationErrorMessage(borrowedResult.reason)
      return
    }

    if (borrowedResult.status === 'rejected' || receivedResult.status === 'rejected') {
      warningMessage.value = 'Some disputes could not be loaded. Please refresh to try again.'
    }

    const borrowed: Reservation[] = borrowedResult.status === 'fulfilled'
      ? borrowedResult.value.data : []
    const received: LenderReservation[] = receivedResult.status === 'fulfilled'
      ? receivedResult.value.data : []

    const byId = new Map<number, DisputeEntry>()
    for (const item of borrowed) {
      if (item.status !== 'DISPUTED') continue
      byId.set(item.id, {
        id: item.id,
        role: 'BORROWER',
        toolTitle: item.tool?.title ?? 'Equipment unavailable',
        pictureUrl: item.tool?.picture_url ?? null,
        borrowerName: null,
        returnedCondition: item.returned_condition,
        startDate: item.start_date,
        endDate: item.end_date,
        detailUrl: `/reservations/${item.id}`,
      })
    }
    for (const item of received) {
      if (item.status !== 'DISPUTED') continue
      byId.set(item.id, {
        id: item.id,
        role: 'LENDER',
        toolTitle: item.tool?.title ?? 'Equipment unavailable',
        pictureUrl: item.tool?.picture_url ?? null,
        borrowerName: item.borrower?.name ?? null,
        returnedCondition: item.returned_condition,
        startDate: item.start_date,
        endDate: item.end_date,
        detailUrl: `/lender/reservations/${item.id}`,
      })
    }
    disputes.value = [...byId.values()].sort((a, b) => b.id - a.id)
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

onMounted(loadDisputes)
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="py-10 md:py-14">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-[#172B25]">My Disputes</h1>
          <p class="mt-2 text-[#66736D]">Returned equipment with reported damage or missing parts, as a borrower or lender.</p>
        </div>
        <button type="button" :disabled="refreshing" class="rounded-xl border border-[#E5E9E4] bg-white px-4 py-2 text-sm font-semibold disabled:opacity-50" @click="loadDisputes">
          {{ refreshing ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>

      <div class="mt-7 flex flex-wrap gap-2" aria-label="Filter disputes by role">
        <button v-for="tab in tabs" :key="tab.value" type="button"
          :aria-pressed="filter === tab.value"
          class="rounded-xl border px-4 py-2 text-sm font-semibold transition-colors"
          :class="filter === tab.value ? 'border-[#176B52] bg-[#E9F4ED] text-[#176B52]' : 'border-[#E5E9E4] bg-white text-[#66736D]'"
          @click="filter = tab.value">
          {{ tab.label }} ({{ tab.value === 'ALL' ? disputes.length : tab.value === 'BORROWER' ? borrowerCount : lenderCount }})
        </button>
      </div>

      <p v-if="warningMessage" role="status" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ warningMessage }}</p>
      <p v-if="errorMessage" role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ errorMessage }}</p>
      <p v-if="loading" role="status" class="mt-6 rounded-2xl border border-[#E5E9E4] bg-white p-10 text-center text-[#66736D]">Loading disputes...</p>

      <div v-else-if="!filteredDisputes.length && !errorMessage" class="mt-6 rounded-2xl border border-[#E5E9E4] bg-white p-12 text-center">
        <h2 class="text-lg font-semibold text-[#172B25]">No disputes found</h2>
        <p class="mt-2 text-sm text-[#66736D]">Disputes appear here when an inspection records damage or missing parts.</p>
      </div>

      <div v-else-if="filteredDisputes.length" class="mt-6 grid gap-4">
        <article v-for="item in filteredDisputes" :key="item.id" class="flex flex-col gap-4 rounded-2xl border border-[#E5E9E4] bg-white p-5 sm:flex-row sm:items-start">
          <img v-if="item.pictureUrl" :src="item.pictureUrl" :alt="item.toolTitle" class="h-36 w-full rounded-xl object-cover sm:h-32 sm:w-40 sm:shrink-0" />
          <div v-else class="flex h-36 w-full items-center justify-center rounded-xl bg-[#E9F4ED] text-sm text-[#176B52] sm:h-32 sm:w-40 sm:shrink-0">No image available</div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <p class="text-xs text-[#66736D]">Reservation #{{ item.id }}</p>
                <h2 class="mt-1 text-lg font-bold text-[#172B25]">{{ item.toolTitle }}</h2>
              </div>
              <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">DISPUTED · {{ item.role === 'LENDER' ? 'Lender' : 'Borrower' }}</span>
            </div>
            <dl class="mt-4 grid gap-3 text-sm text-[#66736D] sm:grid-cols-2">
              <div><dt class="font-semibold text-[#172B25]">Returned condition</dt><dd>{{ item.returnedCondition?.replace('_', ' ') || 'Not specified' }}</dd></div>
              <div><dt class="font-semibold text-[#172B25]">Rental dates</dt><dd>{{ displayDate(item.startDate) }} – {{ displayDate(item.endDate) }}</dd></div>
              <div v-if="item.role === 'LENDER'"><dt class="font-semibold text-[#172B25]">Borrower</dt><dd>{{ item.borrowerName || 'Unavailable' }}</dd></div>
            </dl>
            <p class="mt-4 text-sm text-[#66736D]">Pending follow-up. Online dispute resolution is not available yet.</p>
            <NuxtLink :to="item.detailUrl" class="mt-4 inline-flex rounded-xl border border-[#176B52] px-4 py-2 text-sm font-semibold text-[#176B52] hover:bg-[#E9F4ED]">View reservation</NuxtLink>
          </div>
        </article>
      </div>
    </UContainer>
  </div>
</template>
