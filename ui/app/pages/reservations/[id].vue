<script setup lang="ts">
import type {
  Reservation,
  ReservationStatus
} from '~/types/reservation'

import CancelReservationModal from '~/components/reservations/CancelReservationModal.vue'
import { getReservationErrorMessage } from '~/utils/getReservationErrorMessage'

definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const { user } = useAuth()

const {
  getReservation,
  cancelReservation,
  returnReservation
} = useReservations()

const reservation = ref<Reservation | null>(null)

const loading = ref(true)
const canceling = ref(false)
const returning = ref(false)
const cancelModalOpen = ref(false)

const errorMessage = ref('')
const cancelError = ref('')
const successMessage = ref('')

const reservationId = computed(() =>
  Number(route.params.id)
)

const isBorrower = computed(() =>
  user.value !== null && reservation.value?.borrower_id === user.value.id
)

const canCancel = computed(() =>
  isBorrower.value && (
    reservation.value?.status === 'REQUESTED' ||
    reservation.value?.status === 'APPROVED'
  )
)

const canReturn = computed(() =>
  isBorrower.value && reservation.value?.status === 'ACTIVE'
)

function statusClass(status: ReservationStatus): string {
  switch (status) {
    case 'APPROVED':
    case 'CLOSED':
      return 'bg-[#E9F4ED] text-[#176B52]'

    case 'REQUESTED':
      return 'bg-amber-50 text-amber-700'

    case 'ACTIVE':
    case 'RETURNED':
      return 'bg-blue-50 text-blue-700'

    case 'DISPUTED':
    case 'CANCELED':
      return 'bg-red-50 text-red-700'

    default:
      return 'bg-gray-100 text-gray-700'
  }
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC'
  }).format(new Date(value))
}

async function loadReservation() {
  errorMessage.value = ''
  loading.value = true

  if (
    !Number.isInteger(reservationId.value) ||
    reservationId.value <= 0
  ) {
    errorMessage.value = 'Invalid reservation.'
    loading.value = false
    return
  }

  try {
    const response = await getReservation(
      reservationId.value
    )

    reservation.value = response.data
  } catch (error: any) {
    console.error(error)
    errorMessage.value = getReservationErrorMessage(error)
  } finally {
    loading.value = false
  }
}

function openCancellationModal(): void {
  if (!reservation.value || !canCancel.value) return
  cancelError.value = ''
  cancelModalOpen.value = true
}

function closeCancellationModal(): void {
  if (canceling.value) return
  cancelModalOpen.value = false
  cancelError.value = ''
}

async function handleCancel(reason: string | null): Promise<void> {
  if (!reservation.value || !canCancel.value || canceling.value) return
  cancelError.value = ''
  successMessage.value = ''
  canceling.value = true

  try {
    const response = await cancelReservation(reservation.value.id, reason)
    cancelModalOpen.value = false
    reservation.value = response.data
    successMessage.value = response.message
    await loadReservation()
  } catch (error: any) {
    cancelError.value = getReservationErrorMessage(error)
    await loadReservation()
  } finally {
    canceling.value = false
  }
}

async function handleReturn(): Promise<void> {
  if (!reservation.value || !canReturn.value || returning.value || canceling.value) return
  if (!window.confirm('Confirm the tool has been physically returned to the lender?')) return

  returning.value = true
  cancelError.value = ''
  successMessage.value = ''
  try {
    const result = await returnReservation(reservation.value.id)
    await loadReservation()
    successMessage.value = result.message
  } catch (error: any) {
    const message = getReservationErrorMessage(error)
    await loadReservation()
    cancelError.value = message
  } finally {
    returning.value = false
  }
}

onMounted(() => {
  loadReservation()
})
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <CancelReservationModal
      :open="cancelModalOpen"
      :reason-required="false"
      :submitting="canceling"
      :error="cancelError"
      @close="closeCancellationModal"
      @confirm="handleCancel"
    />

    <UContainer class="py-10 md:py-14">
      <div class="mb-6">
        <UButton
          to="/reservations"
          label="Back to My Reservations"
          icon="i-lucide-arrow-left"
          color="neutral"
          variant="ghost"
        />
      </div>

      <div
        v-if="loading"
        class="rounded-2xl border border-[#E5E9E4]
               bg-white px-6 py-16 text-center"
      >
        <h4
          class="text-xl font-semibold text-[#172B25]"
        >
          Loading reservation...
        </h4>
      </div>

      <div
        v-else-if="errorMessage"
        class="rounded-2xl border border-[#E5E9E4]
               bg-white px-6 py-16 text-center"
      >
        <h2
          class="text-xl font-semibold text-[#172B25]"
        >
          Unable to display reservation
        </h2>

        <p class="mt-2 text-[#66736D]">
          {{ errorMessage }}
        </p>

        <UButton
          to="/reservations"
          label="My Reservations"
          class="mt-6 bg-[#176B52] text-white
                 hover:bg-[#124D3D]"
        />
      </div>

      <div
        v-else-if="reservation"
        class="mx-auto max-w-4xl"
      >
        <div
          class="rounded-2xl border border-[#E5E9E4]
                 bg-white p-6 md:p-8"
        >
          <div
            class="flex flex-col gap-6
                   md:flex-row md:items-start"
          >
            <img
              v-if="reservation.tool?.picture_url"
              :src="reservation.tool.picture_url"
              :alt="reservation.tool.title"
              class="h-56 w-full rounded-xl object-cover
                     md:h-52 md:w-64 md:shrink-0"
            >
            <div v-else class="flex h-56 w-full items-center justify-center rounded-xl bg-[#E9F4ED] text-[#176B52] md:h-52 md:w-64 md:shrink-0">
              No image available
            </div>

            <div class="min-w-0 flex-1">
              <div
                class="flex flex-wrap items-start
                       justify-between gap-4"
              >
                <div>
                  <p class="text-sm text-[#66736D]">
                    Reservation #{{ reservation.id }}
                  </p>

                  <h1
                    class="mt-1 text-2xl font-bold
                           text-[#172B25]"
                  >
                    {{ reservation.tool.title }}
                  </h1>
                </div>

                <span
                  class="rounded-full px-3 py-1
                         text-xs font-semibold"
                  :class="statusClass(reservation.status)"
                >
                  {{ reservation.status }}
                </span>
              </div>

              <p
                class="mt-4 text-sm leading-6
                       text-[#66736D]"
              >
                {{ reservation.tool.description }}
              </p>

              <div
                class="mt-6 grid gap-5
                       sm:grid-cols-2"
              >
                <div>
                  <p class="text-sm text-[#66736D]">
                    Start date
                  </p>

                  <p
                    class="mt-1 font-medium
                           text-[#172B25]"
                  >
                    {{ formatDate(reservation.start_date) }}
                  </p>
                </div>

                <div>
                  <p class="text-sm text-[#66736D]">
                    End date
                  </p>

                  <p
                    class="mt-1 font-medium
                           text-[#172B25]"
                  >
                    {{ formatDate(reservation.end_date) }}
                  </p>
                </div>

                <div>
                  <p class="text-sm text-[#66736D]">
                    Daily rate
                  </p>

                  <p
                    class="mt-1 font-medium
                           text-[#172B25]"
                  >
                    ${{ Number(
                      reservation.tool.daily_rate
                    ).toFixed(2) }}
                  </p>
                </div>

                <div>
                  <p class="text-sm text-[#66736D]">
                    Total cost
                  </p>

                  <p
                    class="mt-1 text-lg font-semibold
                           text-[#172B25]"
                  >
                    ${{ Number(
                      reservation.total_cost
                    ).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="successMessage"
            class="mt-6 rounded-xl border
                   border-[#CDE4D7] bg-[#E9F4ED]
                   p-4 text-sm text-[#176B52]"
          >
            {{ successMessage }}
          </div>

          <div
            v-if="cancelError"
            class="mt-6 rounded-xl border
                   border-red-200 bg-red-50
                   p-4 text-sm text-red-700"
          >
            {{ cancelError }}
          </div>

          <section v-if="reservation.status === 'CANCELED'" class="mt-7 rounded-xl border border-[#E5E9E4] bg-[#FAF9F6] p-5">
            <h2 class="font-semibold text-[#172B25]">Cancellation details</h2>
            <p class="mt-2 text-sm text-[#66736D]">Canceled by {{ reservation.canceled_by_user_id == null ? 'unknown party' : reservation.canceled_by_user_id === reservation.borrower_id ? 'borrower' : 'lender' }}.</p>
            <p class="mt-2 whitespace-pre-wrap text-sm text-[#172B25]">{{ reservation.cancellation_reason || 'No reason was provided.' }}</p>
          </section>

          <section v-if="reservation.returned_condition" class="mt-7 rounded-xl border border-[#E5E9E4] bg-[#FAF9F6] p-5">
            <h2 class="font-semibold text-[#172B25]">Inspection result</h2>
            <p class="mt-2 text-sm text-[#66736D]">Returned condition: {{ reservation.returned_condition }}</p>
          </section>
          <p v-if="reservation.status === 'RETURNED'" class="mt-6 text-sm text-[#66736D]" role="status">
            The lender has been notified of the return. Awaiting inspection and closure.
          </p>
          <p v-if="reservation.status === 'DISPUTED'" class="mt-6 text-sm text-red-700" role="status">
            The lender reported an issue with the returned equipment. This reservation requires follow-up.
          </p>

          <div
            class="mt-8 flex flex-wrap gap-3
                   border-t border-[#E5E9E4] pt-6"
          >
            <UButton
              :to="`/equipment/${reservation.tool.id}`"
              label="View Equipment"
              icon="i-lucide-arrow-up-right"
              color="neutral"
              variant="outline"
            />

            <UButton
              v-if="canCancel"
              label="Cancel Reservation"
              icon="i-lucide-x"
              color="error"
              variant="outline"
              :loading="canceling"
              :disabled="canceling || returning"
              @click="openCancellationModal"
            />
            <UButton
              v-if="canReturn"
              label="Mark as Returned"
              icon="i-lucide-package-check"
              class="bg-[#176B52] text-white hover:bg-[#124D3D]"
              :loading="returning"
              :disabled="returning || canceling"
              @click="handleReturn"
            />
          </div>
        </div>
      </div>
    </UContainer>
  </div>
</template>