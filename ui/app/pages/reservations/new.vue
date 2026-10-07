
<script setup lang="ts">
import { equipment } from '~/data/equipment'
import type { ReservationQuote } from '~/composables/useReservations'

const route = useRoute()

const { getQuote } = useReservations()

const quote = ref<ReservationQuote | null>(null)
const quoteLoading = ref(false)
const quoteError = ref('')

const toolId = Number(queryString(route.query.tool))
const tool = equipment.find(item => item.id === toolId)

const startDate = ref(queryString(route.query.start))
const endDate = ref(queryString(route.query.end))

const today = ref('')

const validDates = computed(() =>
  Boolean(
    startDate.value
    && endDate.value
    && startDate.value >= today.value
    && endDate.value >= startDate.value
  )
)

const submitted = ref(false)

if (!tool) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Equipment not found'
  })
}

onMounted(() => {
  today.value = localToday()
})

watch(
  [startDate, endDate],
  async () => {
    submitted.value = false
    quote.value = null
    quoteError.value = ''

    if (
      startDate.value
      && endDate.value
      && endDate.value < startDate.value
    ) {
      endDate.value = ''
      return
    }

    if (!validDates.value) {
      return
    }

    quoteLoading.value = true

    try {
      const response = await getQuote({
        tool_id: tool.id,
        start_date: startDate.value,
        end_date: endDate.value
      })

      quote.value = response.data
      console.log(quote.value)
    } catch (error) {
      console.error(error)

      quoteError.value =
        'Unable to calculate the estimated cost.'
    } finally {
      quoteLoading.value = false
    }
  }
)

function submitDemoRequest() {
  if (!validDates.value) return

  // Demo only: no API call and no persisted reservation.
  submitted.value = true
}

function queryString(value: unknown): string {
  return typeof value === 'string' ? value : ''
}

function localToday(): string {
  const date = new Date()
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />

    <UContainer class="max-w-5xl py-10 md:py-14">
      <NuxtLink
        :to="`/equipment/${tool.id}`"
        class="inline-flex items-center gap-2 text-sm
               font-medium text-[#176B52]"
      >
        <UIcon name="i-lucide-arrow-left" class="size-4" />
        Back to equipment
      </NuxtLink>

      <div class="mt-8">
        <h1 class="text-3xl font-bold text-[#172B25]">
          Request Reservation
        </h1>

        <p class="mt-2 text-[#66736D]">
          Review your dates and estimated cost.
        </p>
      </div>

      <div
        v-if="submitted"
        role="status"
        class="mt-8 rounded-2xl border border-[#B8DCC8]
               bg-[#E9F4ED] p-6"
      >
        <div class="flex items-start gap-3">
          <UIcon
            name="i-lucide-check-circle-2"
            class="mt-1 size-6 shrink-0 text-[#176B52]"
          />

          <div>
            <h2 class="text-xl font-semibold text-[#172B25]">
              Demo request reviewed
            </h2>

            <p class="mt-2 text-[#456456]">
              Your reservation details are ready.
              No request has been sent or saved yet.
              We'll enable submission when the
              Laravel reservation API is connected.
            </p>

            <UButton
              to="/"
              label="Explore more equipment"
              variant="outline"
              color="neutral"
              class="mt-5"
            />
          </div>
        </div>
      </div>

      <div
        v-else
        class="mt-8 grid grid-cols-1 items-start gap-8
               lg:grid-cols-[minmax(0,1fr)_340px]"
      >
        <!-- Reservation form -->
        <section
          class="rounded-2xl border border-[#E5E9E4]
                 bg-white p-6"
        >
          <h2 class="text-xl font-semibold text-[#172B25]">
            Rental dates
          </h2>

          <p class="mt-2 text-sm text-[#66736D]">
            Choose when you would like to borrow this item.
          </p>

          <form
            id="reservation-form"
            class="mt-6 space-y-5"
            @submit.prevent="submitDemoRequest"
          >
            <label class="block">
  <span class="mb-2 block text-sm font-medium text-[#172B25]">
    Start date
  </span>

  <input
    v-model="startDate"
    type="date"
    required
    class="w-full rounded-xl border border-[#E5E9E4]
           bg-white px-4 py-3 text-[#172B25]"
  >
</label>

           <label class="block">
  <span class="mb-2 block text-sm font-medium text-[#172B25]">
    End date
  </span>

  <input
    v-model="endDate"
    type="date"
    :min="startDate || undefined"
    required
    class="w-full rounded-xl border border-[#E5E9E4]
           bg-white px-4 py-3 text-[#172B25]"
  >
</label>

            <p
              v-if="startDate && endDate && !validDates"
              class="text-sm text-red-600"
            >
              Please select a valid date range.
            </p>

            <div
              class="rounded-xl bg-[#FAF9F6] p-4
                     text-sm text-[#66736D]"
            >
              Availability for these dates has not been checked.
              Your request will require approval and
              validation by the reservation service.
            </div>

            <UButton
              type="submit"
              label="Review Demo Request"
              icon="i-lucide-calendar-check"
              block
              size="xl"
              :disabled="!validDates"
              class="bg-[#176B52] text-white hover:bg-[#124D3D]"
            />
          </form>
        </section>

        <!-- Summary -->
        <aside
          class="rounded-2xl border border-[#E5E9E4]
                 bg-white p-5"
        >
          <div class="flex gap-4">
            <img
              :src="tool.picture"
              :alt="tool.title"
              class="size-24 rounded-xl bg-[#E9F4ED]
                     object-cover"
            >

            <div class="min-w-0">
              <p class="text-xs text-[#176B52]">
                {{ tool.category.name }}
              </p>

              <h3 class="mt-1 font-semibold text-[#172B25]">
                {{ tool.title }}
              </h3>

              <p class="mt-2 text-sm text-[#66736D]">
                {{ tool.owner.address }}
              </p>
            </div>
          </div>

          <div class="mt-6 space-y-3 border-t border-[#E5E9E4]
                  pt-5 text-sm">
            <div class="flex justify-between text-[#66736D]">
              <span>Daily rate</span>
              <span>${{ tool.daily_rate.toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-[#66736D]">
              <span>Rental days</span>
              <span>{{ quote?.days ?? '—' }}</span>
            </div>

            <div class="flex justify-between text-[#66736D]">
              <span>Subtotal</span>
              <span>
                {{ quote ? `$${quote.subtotal.toFixed(2)}` : '—' }}
              </span>
            </div>

            <div class="flex justify-between text-[#66736D]">
              <span>Estimated fee</span>
              <span>
                {{ quote ? `$${quote.tax.toFixed(2)}` : '—' }}
              </span>
            </div>

            <p
              v-if="quoteLoading"
              class="text-sm text-[#66736D]"
            >
              Calculating estimated cost...
            </p>

            <p
              v-if="quoteError"
              class="text-sm text-red-600"
            >
              {{ quoteError }}
            </p>

            <div
              class="flex justify-between border-t
                    border-[#E5E9E4] pt-4 text-base
                    font-semibold text-[#172B25]"
            >
              <span>Estimated total</span>

              <span>
                {{
                  quote
                    ? `$${quote.total_cost.toFixed(2)}`
                    : '—'
                }}
              </span>
            </div>
          </div>
        </aside>
      </div>
    </UContainer>
  </div>
</template>