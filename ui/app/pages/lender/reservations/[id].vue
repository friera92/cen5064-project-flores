<script setup lang="ts">
import type { LenderReservation } from '~/types/lender-reservation'
import type { ReturnedCondition } from '~/types/reservation'
import CancelReservationModal from '~/components/reservations/CancelReservationModal.vue'
import { getReservationErrorMessage } from '~/utils/getReservationErrorMessage'

// The backend authorizes access; this page additionally verifies owner identity.
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const { user } = useAuth()
const { getLenderReservation, approveReservation, cancelReservation, handOffReservation, inspectReservation, closeReservation } = useReservations()
const reservation = ref<LenderReservation | null>(null)
const loading = ref(true)
const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const cancelModalOpen = ref(false)
const cancellationError = ref('')
const inspectionCondition = ref<ReturnedCondition>('GOOD')

const reservationId = computed(() => Number(route.params.id))
const isOwner = computed(() => user.value !== null && reservation.value?.tool?.owner_id === user.value.id)
const canInspect = computed(() => Boolean(
  isOwner.value && reservation.value && reservation.value.status === 'RETURNED' && !reservation.value.returned_condition
))
const canClose = computed(() => Boolean(
  isOwner.value && reservation.value && reservation.value.status === 'RETURNED' && reservation.value.returned_condition === 'GOOD'
))

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
    errorMessage.value = getReservationErrorMessage(error)
  } finally {
    loading.value = false
  }
}

type Action = 'approve' | 'handoff'

function openCancelModal(): void {
  if (!reservation.value || !isOwner.value || reservation.value.status !== 'REQUESTED') return
  cancellationError.value = ''
  cancelModalOpen.value = true
}

function closeCancelModal(): void {
  if (submitting.value) return
  cancelModalOpen.value = false
  cancellationError.value = ''
}

async function confirmCancellation(reason: string | null): Promise<void> {
  if (!reservation.value || submitting.value || !isOwner.value) return
  submitting.value = true
  cancellationError.value = ''
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await cancelReservation(reservation.value.id, reason)
    cancelModalOpen.value = false
    await loadReservation()
    successMessage.value = response.message
  } catch (error: any) {
    cancellationError.value = getReservationErrorMessage(error)
    await loadReservation()
  } finally {
    submitting.value = false
  }
}

async function performAction(action: Action): Promise<void> {
  if (!reservation.value || !isOwner.value || submitting.value) return
  const status = reservation.value.status
  if (!((status === 'REQUESTED' && action === 'approve') ||
    (status === 'APPROVED' && action === 'handoff'))) return

  const message = action === 'approve'
    ? 'Approve this equipment reservation?'
    : 'Confirm this tool was physically handed over to the borrower?'
  if (!window.confirm(message)) return

  submitting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const result = action === 'approve'
      ? await approveReservation(reservation.value.id)
      : await handOffReservation(reservation.value.id)
    await loadReservation()
    successMessage.value = result.message
  } catch (error: any) {
    const message = getReservationErrorMessage(error)
    await loadReservation()
    errorMessage.value = message
  } finally {
    submitting.value = false
  }
}

async function submitInspection(): Promise<void> {
  if (!reservation.value || !canInspect.value || submitting.value) return
  const condition = inspectionCondition.value
  const warning = condition === 'GOOD'
    ? 'Confirm the tool was returned in good condition?'
    : 'This condition will mark the reservation DISPUTED. Confirm the inspection result?'
  if (!window.confirm(warning)) return

  submitting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const result = await inspectReservation(reservation.value.id, condition)
    await loadReservation()
    successMessage.value = result.message
  } catch (error: any) {
    const message = getReservationErrorMessage(error)
    await loadReservation()
    errorMessage.value = message
  } finally {
    submitting.value = false
  }
}

async function confirmClosure(): Promise<void> {
  if (!reservation.value || !canClose.value || submitting.value) return
  if (!window.confirm('Close this reservation and make the equipment available again?')) return

  submitting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const result = await closeReservation(reservation.value.id)
    await loadReservation()
    successMessage.value = result.message
  } catch (error: any) {
    const message = getReservationErrorMessage(error)
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
    <CancelReservationModal
      :open="cancelModalOpen"
      :reason-required="true"
      :submitting="submitting"
      :error="cancellationError"
      @close="closeCancelModal"
      @confirm="confirmCancellation"
    />
    <UContainer class="py-10 md:py-14">
      <NuxtLink
        :to="reservation?.status === 'DISPUTED' ? '/lender/reservations?tab=disputed' : '/lender/reservations'"
        class="text-sm font-semibold text-[#176B52]"
      >← {{ reservation?.status === 'DISPUTED' ? 'Disputes' : 'Incoming Reservations' }}</NuxtLink>

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

        <section v-if="reservation.status === 'CANCELED'" class="mt-7 rounded-xl border border-[#E5E9E4] bg-[#FAF9F6] p-5">
          <h2 class="font-semibold text-[#172B25]">Cancellation details</h2>
          <p class="mt-2 text-sm text-[#66736D]">Canceled by {{ reservation.canceled_by_user_id == null ? 'unknown party' : reservation.canceled_by_user_id === reservation.borrower_id ? 'borrower' : 'lender' }}.</p>
          <p class="mt-2 whitespace-pre-wrap text-sm text-[#172B25]">{{ reservation.cancellation_reason || 'No reason was provided.' }}</p>
        </section>
        <section v-if="canInspect" class="mt-8 rounded-xl border border-[#E5E9E4] bg-[#FAF9F6] p-5">
          <h2 class="text-lg font-semibold text-[#172B25]">Inspect returned equipment</h2>
          <p class="mt-2 text-sm text-[#66736D]">Confirm the condition after physically inspecting the item. Damage or missing parts will open a dispute.</p>
          <form class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end" @submit.prevent="submitInspection">
            <label class="flex-1 text-sm font-semibold text-[#172B25]" for="returned-condition">
              Returned condition
              <select id="returned-condition" v-model="inspectionCondition" required :disabled="submitting" class="mt-2 block w-full rounded-xl border border-[#E5E9E4] bg-white px-4 py-3 font-normal">
                <option value="GOOD">Good</option>
                <option value="DAMAGED">Damaged</option>
                <option value="BROKEN">Broken</option>
                <option value="MISSING_PARTS">Missing parts</option>
              </select>
            </label>
            <button type="submit" :disabled="submitting" class="rounded-xl bg-[#176B52] px-5 py-3 font-semibold text-white disabled:opacity-50">
              {{ submitting ? 'Submitting...' : 'Submit inspection' }}
            </button>
          </form>
        </section>
        <p v-if="canClose" class="mt-6 text-sm text-[#66736D]" role="status">Inspection passed. You can now close the reservation.</p>
        <p v-if="reservation.status === 'DISPUTED'" class="mt-6 text-sm text-red-700" role="status">
          Inspection reported {{ reservation.returned_condition }}. This dispute is pending follow-up; resolution is not available yet.
        </p>

        <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-[#E5E9E4] pt-6">
          <button v-if="reservation.status === 'REQUESTED'" type="button" :disabled="submitting" class="rounded-xl bg-[#176B52] px-5 py-3 font-semibold text-white disabled:opacity-50" @click="performAction('approve')">Approve request</button>
          <button v-if="reservation.status === 'REQUESTED'" type="button" :disabled="submitting" class="rounded-xl border border-red-200 px-5 py-3 font-semibold text-red-700 disabled:opacity-50" @click="openCancelModal">Cancel request</button>
          <button v-if="reservation.status === 'APPROVED'" type="button" :disabled="submitting" class="rounded-xl bg-[#176B52] px-5 py-3 font-semibold text-white disabled:opacity-50" @click="performAction('handoff')">Confirm tool handoff</button>
          <p v-if="reservation.status === 'ACTIVE'" class="text-sm text-[#66736D]">The borrower must mark the equipment as returned before inspection.</p>
          <button v-if="canClose" type="button" :disabled="submitting" class="rounded-xl bg-[#176B52] px-5 py-3 font-semibold text-white disabled:opacity-50" @click="confirmClosure">Close reservation</button>
          <p v-if="reservation.status === 'CLOSED' || reservation.status === 'CANCELED'" class="text-sm text-[#66736D]">No further lender action is available for this reservation.</p>
          <NuxtLink v-if="reservation.tool" :to="`/equipment/${reservation.tool.id}`" class="rounded-xl border border-[#E5E9E4] px-5 py-3 text-sm text-[#172B25]">View equipment</NuxtLink>
        </div>
      </article>
    </UContainer>
  </div>
</template>
