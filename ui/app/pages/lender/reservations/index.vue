<script setup lang="ts">
import type { LenderReservation } from '~/types/lender-reservation'
import type { ReservationStatus } from '~/types/reservation'

definePageMeta({ middleware: 'auth' })

const { getReceivedReservations, approveReservation, cancelReservation, handOffReservation } = useReservations()

const reservations = ref<LenderReservation[]>([])
const selectedStatus = ref('ALL')
const loading = ref(true)
const pendingId = ref<number | null>(null)
const errorMessage = ref('')
const successMessage = ref('')

const statuses: Array<{ label: string; value: string }> = [
  { label: 'All statuses', value: 'ALL' },
  { label: 'Requested', value: 'REQUESTED' },
  { label: 'Approved', value: 'APPROVED' },
  { label: 'Active', value: 'ACTIVE' },
  { label: 'Returned', value: 'RETURNED' },
  { label: 'Closed', value: 'CLOSED' },
  { label: 'Disputed', value: 'DISPUTED' },
  { label: 'Canceled', value: 'CANCELED' },
]

const filteredReservations = computed(() => selectedStatus.value === 'ALL'
  ? reservations.value
  : reservations.value.filter(item => item.status === selectedStatus.value))

const pendingCount = computed(() => reservations.value.filter(item => item.status === 'REQUESTED').length)

function displayDate(value: string): string {
  const day = value.slice(0, 10)
  const parsed = new Date(`${day}T00:00:00Z`)
  return Number.isNaN(parsed.getTime()) ? day : new Intl.DateTimeFormat('en-US', {
    month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC',
  }).format(parsed)
}

function money(value: number | string): string {
  return Number(value).toLocaleString('en-US', { style: 'currency', currency: 'USD' })
}

function statusClass(status: ReservationStatus): string {
  switch (status) {
    case 'REQUESTED': return 'bg-amber-50 text-amber-800'
    case 'APPROVED': return 'bg-sky-50 text-sky-800'
    case 'ACTIVE': return 'bg-blue-50 text-blue-800'
    case 'RETURNED': return 'bg-violet-50 text-violet-800'
    case 'CLOSED': return 'bg-[#E9F4ED] text-[#176B52]'
    case 'DISPUTED': return 'bg-red-50 text-red-800'
    case 'CANCELED': return 'bg-gray-100 text-gray-600'
  }
}

async function loadReservations(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await getReceivedReservations()
    reservations.value = response.data
  } catch (error: any) {
    errorMessage.value = error?.data?.message ?? 'Unable to load incoming reservations. Please try again.'
  } finally {
    loading.value = false
  }
}

type LenderAction = 'approve' | 'cancel' | 'handoff'

async function performAction(item: LenderReservation, action: LenderAction): Promise<void> {
  const valid = (action === 'approve' || action === 'cancel') && item.status === 'REQUESTED'
    || action === 'handoff' && item.status === 'APPROVED'
  if (!valid || pendingId.value !== null) return

  const confirmation = action === 'approve'
    ? 'Approve this equipment reservation?'
    : action === 'cancel'
      ? 'Cancel this reservation request?'
      : 'Confirm the tool was physically handed over to the borrower? This starts the active loan.'
  if (!window.confirm(confirmation)) return

  pendingId.value = item.id
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const result = action === 'approve'
      ? await approveReservation(item.id)
      : action === 'cancel'
        ? await cancelReservation(item.id)
        : await handOffReservation(item.id)
    successMessage.value = result.message
    await loadReservations()
  } catch (error: any) {
    const mutationError = error?.data?.message ?? 'Unable to update the reservation. Please refresh and try again.'
    // The server is authoritative; refresh even after a failed mutation.
    await loadReservations()
    errorMessage.value = mutationError
  } finally {
    pendingId.value = null
  }
}

onMounted(loadReservations)
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="py-10 md:py-14">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-[#172B25]">Incoming Reservations</h1>
          <p class="mt-2 text-[#66736D]">Review and manage requests for equipment you own.</p>
          <p v-if="!loading" class="mt-2 text-sm font-medium text-[#176B52]">
            {{ pendingCount }} {{ pendingCount === 1 ? 'request awaiting review' : 'requests awaiting review' }}
          </p>
        </div>
        <NuxtLink to="/my-equipment" class="rounded-xl border border-[#E5E9E4] bg-white px-4 py-2 text-sm font-medium text-[#172B25]">
          My Listings
        </NuxtLink>
      </div>

      <div class="mt-8 flex flex-wrap items-end gap-3">
        <label class="flex min-w-48 flex-col gap-2 text-sm font-medium text-[#172B25]">
          Filter by status
          <select v-model="selectedStatus" class="rounded-xl border border-[#E5E9E4] bg-white px-4 py-3">
            <option v-for="option in statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </label>
        <button type="button" :disabled="loading || pendingId !== null" class="rounded-xl border border-[#E5E9E4] bg-white px-4 py-3 text-sm disabled:opacity-50" @click="loadReservations">
          Refresh
        </button>
      </div>

      <p v-if="successMessage" role="status" class="mt-5 rounded-xl border border-[#CDE4D7] bg-[#E9F4ED] p-4 text-sm text-[#176B52]">{{ successMessage }}</p>
      <p v-if="errorMessage" role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ errorMessage }}</p>
      <p v-if="loading" role="status" class="mt-6 rounded-2xl border bg-white p-10 text-center text-[#66736D]">Loading incoming reservations...</p>
      <div v-else-if="!filteredReservations.length" class="mt-6 rounded-2xl border border-[#E5E9E4] bg-white px-6 py-14 text-center">
        <h2 class="text-xl font-semibold text-[#172B25]">No reservations found</h2>
        <p class="mt-2 text-[#66736D]">{{ selectedStatus === 'ALL' ? 'No one has requested your equipment yet.' : 'There are no reservations with this status.' }}</p>
        <button v-if="selectedStatus !== 'ALL'" type="button" class="mt-4 rounded-xl border px-4 py-2" @click="selectedStatus = 'ALL'">Show all</button>
      </div>

      <div v-else class="mt-6 overflow-hidden rounded-2xl border border-[#E5E9E4] bg-white">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-[#F5F7F4] text-[#66736D]">
              <tr>
                <th scope="col" class="px-5 py-4 font-semibold">Equipment</th>
                <th scope="col" class="px-5 py-4 font-semibold">Borrower</th>
                <th scope="col" class="px-5 py-4 font-semibold">Dates</th>
                <th scope="col" class="px-5 py-4 font-semibold">Total</th>
                <th scope="col" class="px-5 py-4 font-semibold">Status</th>
                <th scope="col" class="px-5 py-4 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#E5E9E4]">
              <tr v-for="item in filteredReservations" :key="item.id" class="align-middle">
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <img v-if="item.tool?.picture_url" :src="item.tool.picture_url" :alt="item.tool.title" class="h-14 w-16 shrink-0 rounded-lg object-cover" />
                    <div v-else class="flex h-14 w-16 shrink-0 items-center justify-center rounded-lg bg-[#E9F4ED] text-xs text-[#176B52]">No image</div>
                    <div>
                      <p class="font-semibold text-[#172B25]">{{ item.tool?.title ?? 'Equipment unavailable' }}</p>
                      <p class="mt-1 text-xs text-[#66736D]">Reservation #{{ item.id }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 text-[#172B25]">{{ item.borrower?.name ?? 'Borrower unavailable' }}</td>
                <td class="px-5 py-4 text-[#172B25]">{{ displayDate(item.start_date) }} – {{ displayDate(item.end_date) }}</td>
                <td class="px-5 py-4 font-medium text-[#172B25]">{{ money(item.total_cost) }}</td>
                <td class="px-5 py-4">
                  <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
                <td class="px-5 py-4">
                  <div class="flex flex-wrap gap-2">
                    <NuxtLink :to="`/lender/reservations/${item.id}`" class="rounded-lg border border-[#E5E9E4] px-3 py-2 font-medium text-[#172B25]">View</NuxtLink>
                    <button v-if="item.status === 'REQUESTED'" type="button" :disabled="pendingId !== null" class="rounded-lg bg-[#176B52] px-3 py-2 font-medium text-white disabled:opacity-50" @click="performAction(item, 'approve')">Approve</button>
                    <button v-if="item.status === 'REQUESTED'" type="button" :disabled="pendingId !== null" class="rounded-lg border border-red-200 px-3 py-2 font-medium text-red-700 disabled:opacity-50" @click="performAction(item, 'cancel')">Cancel</button>
                    <button v-if="item.status === 'APPROVED'" type="button" :disabled="pendingId !== null" class="rounded-lg bg-[#176B52] px-3 py-2 font-medium text-white disabled:opacity-50" @click="performAction(item, 'handoff')">Confirm handoff</button>
                    <span v-if="item.status === 'ACTIVE'" class="self-center text-xs text-[#66736D]">Awaiting borrower return</span>
                    <span v-if="item.status === 'RETURNED'" class="self-center text-xs text-[#66736D]">Inspection in next phase</span>
                    <span v-if="item.status === 'DISPUTED'" class="self-center text-xs text-red-700">Pending resolution</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </UContainer>
  </div>
</template>
