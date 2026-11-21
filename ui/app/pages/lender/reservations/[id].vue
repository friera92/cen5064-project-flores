<script setup lang="ts">
import type { LenderReservation } from '~/types/lender-reservation'

// The backend authorizes access; this page additionally verifies owner identity.
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const { user } = useAuth()
const { getLenderReservation, approveReservation, cancelReservation, handOffReservation } = useReservations()
const reservation = ref<LenderReservation | null>(null)
const loading = ref(true)
const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const reservationId = computed(() => Number(route.params.id))
const isOwner = computed(() => user.value !== null && reservation.value?.tool?.owner_id === user.value.id)

function displayDate(value: string): string {
  const date = value.slice(0, 10)
  const parsed = new Date(`${date}T00:00:00Z`)
  return Number.isNaN(parsed.getTime()) ? date : new Intl.DateTimeFormat('en-US', {
    month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC',
  }).format(parsed)
}

async function loadReservation(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  reservation.value = null
  if (!Number.isInteger(reservationId.value) || reservationId.value <= 0) {
    errorMessage.value = 'Invalid reservation ID.'
    loading.value = false
    return
  }
  try {
    const response = await getLenderReservation(reservationId.value)
    reservation.value = response.data
    if (!user.value || response.data.tool?.owner_id !== user.value.id) {
      reservation.value = null
      errorMessage.value = 'You are not authorized to manage this reservation.'
    }
  } catch (error: any) {
    const status = error?.statusCode ?? error?.status
    errorMessage.value = status === 403
      ? 'You are not authorized to view this reservation.'
      : status === 404 ? 'Reservation not found.' : 'Unable to load the reservation.'
  } finally {
    loading.value = false
  }
}

type Action = 'approve' | 'cancel' | 'handoff'
async function performAction(action: Action): Promise<void> {
  if (!reservation.value || !isOwner.value || submitting.value) return
  const status = reservation.value.status
  if (!((status === 'REQUESTED' && ['approve', 'cancel'].includes(action)) ||
    (status === 'APPROVED' && action === 'handoff'))) return

  const message = action === 'approve'
    ? 'Approve this equipment reservation?'
    : action === 'cancel'
      ? 'Cancel this reservation request?'
      : 'Confirm this tool was physically handed over to the borrower?'
  if (!window.confirm(message)) return

  submitting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const result = action === 'approve'
      ? await approveReservation(reservation.value.id)
      : action === 'cancel'
        ? await cancelReservation(reservation.value.id)
        : await handOffReservation(reservation.value.id)
    successMessage.value = result.message
    await loadReservation()
  } catch (error: any) {
    const message = error?.data?.message ?? 'Unable to update the reservation.'
    await loadReservation()
    errorMessage.value = message
  } finally {
    submitting.value = false
  }
}

onMounted(loadReservation)
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="py-10 md:py-14">
      <NuxtLink to="/lender/reservations" class="text-sm font-semibold text-[#176B52]">← Incoming Reservations</NuxtLink>

      <p v-if="successMessage" role="status" class="mt-6 rounded-xl border border-[#CDE4D7] bg-[#E9F4ED] p-4 text-sm text-[#176B52]">{{ successMessage }}</p>
      <p v-if="errorMessage" role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ errorMessage }}</p>
      <div v-if="loading" class="mt-6 rounded-2xl border bg-white p-12 text-center text-[#66736D]">Loading reservation...</div>

      <article v-else-if="reservation && isOwner" class="mt-6 rounded-2xl border border-[#E5E9E4] bg-white p-6 md:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="text-sm text-[#66736D]">Reservation #{{ reservation.id }}</p>
            <h1 class="mt-1 text-3xl font-bold text-[#172B25]">{{ reservation.tool?.title ?? 'Equipment unavailable' }}</h1>
          </div>
          <span class="rounded-full bg-[#E9F4ED] px-3 py-2 text-xs font-semibold text-[#176B52]">{{ reservation.status }}</span>
        </div>

        <div class="mt-7 grid gap-7 md:grid-cols-[220px_1fr]">
          <img v-if="reservation.tool?.picture_url" :src="reservation.tool.picture_url" :alt="reservation.tool.title" class="h-48 w-full rounded-xl object-cover" />
          <div v-else class="flex h-48 items-center justify-center rounded-xl bg-[#E9F4ED] text-[#176B52]">No image available</div>

          <dl class="grid gap-5 sm:grid-cols-2">
            <div><dt class="text-sm text-[#66736D]">Borrower</dt><dd class="mt-1 font-medium text-[#172B25]">{{ reservation.borrower?.name ?? 'Borrower unavailable' }}</dd></div>
            <div><dt class="text-sm text-[#66736D]">Total cost</dt><dd class="mt-1 font-medium text-[#172B25]">{{ Number(reservation.total_cost).toLocaleString('en-US', { style: 'currency', currency: 'USD' }) }}</dd></div>
            <div><dt class="text-sm text-[#66736D]">Start date</dt><dd class="mt-1 font-medium text-[#172B25]">{{ displayDate(reservation.start_date) }}</dd></div>
            <div><dt class="text-sm text-[#66736D]">End date</dt><dd class="mt-1 font-medium text-[#172B25]">{{ displayDate(reservation.end_date) }}</dd></div>
            <div v-if="reservation.returned_condition"><dt class="text-sm text-[#66736D]">Returned condition</dt><dd class="mt-1 font-medium text-[#172B25]">{{ reservation.returned_condition }}</dd></div>
          </dl>
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-[#E5E9E4] pt-6">
          <button v-if="reservation.status === 'REQUESTED'" type="button" :disabled="submitting" class="rounded-xl bg-[#176B52] px-5 py-3 font-semibold text-white disabled:opacity-50" @click="performAction('approve')">Approve request</button>
          <button v-if="reservation.status === 'REQUESTED'" type="button" :disabled="submitting" class="rounded-xl border border-red-200 px-5 py-3 font-semibold text-red-700 disabled:opacity-50" @click="performAction('cancel')">Cancel request</button>
          <button v-if="reservation.status === 'APPROVED'" type="button" :disabled="submitting" class="rounded-xl bg-[#176B52] px-5 py-3 font-semibold text-white disabled:opacity-50" @click="performAction('handoff')">Confirm tool handoff</button>
          <p v-if="reservation.status === 'ACTIVE'" class="text-sm text-[#66736D]">The borrower must mark the equipment as returned before inspection.</p>
          <p v-if="reservation.status === 'RETURNED'" class="text-sm text-[#66736D]">Inspection and closure will be available in the next implementation phase (#38).</p>
          <p v-if="reservation.status === 'DISPUTED'" class="text-sm text-red-700">This reservation is disputed and requires follow-up.</p>
          <p v-if="reservation.status === 'CLOSED' || reservation.status === 'CANCELED'" class="text-sm text-[#66736D]">No further lender action is available for this reservation.</p>
          <NuxtLink v-if="reservation.tool" :to="`/equipment/${reservation.tool.id}`" class="rounded-xl border border-[#E5E9E4] px-5 py-3 text-sm text-[#172B25]">View equipment</NuxtLink>
        </div>
      </article>
    </UContainer>
  </div>
</template>
