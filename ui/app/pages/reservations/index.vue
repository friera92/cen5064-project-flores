
<script setup lang="ts">
import { demoReservations } from '~/data/reservations'
import type { ReservationStatus } from '~/types/reservation'

const selectedStatus = ref('ALL')

const statusOptions = [
   { label: 'All Reservations', value: 'ALL' },
  { label: 'Requested', value: 'REQUESTED' },
  { label: 'Approved', value: 'APPROVED' },
  { label: 'Active', value: 'ACTIVE' },
  { label: 'Returned', value: 'RETURNED' },
  { label: 'Closed', value: 'CLOSED' },
  { label: 'Disputed', value: 'DISPUTED' },
  { label: 'Canceled', value: 'CANCELED' }
]

const filteredReservations = computed(() =>
  selectedStatus.value === 'ALL'
    ? demoReservations
    : demoReservations.filter(
        reservation => reservation.status === selectedStatus.value
      )
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
  }
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC'
  }).format(new Date(`${value}T00:00:00Z`))
}
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />

    <UContainer class="py-10 md:py-14">
      <div
        class="flex flex-col justify-between gap-5
               sm:flex-row sm:items-end"
      >
        <div>
          <h1 class="text-3xl font-bold text-[#172B25]">
            My Reservations
          </h1>

          <p class="mt-2 text-[#66736D]">
            Track your equipment requests and rentals.
          </p>
        </div>

        <UButton
          to="/"
          label="Explore Equipment"
          icon="i-lucide-search"
          class="bg-[#176B52] text-white hover:bg-[#124D3D]"
        />
      </div>

      <div
        class="mt-8 rounded-xl border border-[#B8DCC8]
               bg-[#E9F4ED] p-4 text-sm text-[#176B52]"
      >
        Demo data: these reservations are examples and
        are not connected to your Laravel account yet.
      </div>

      <div class="mt-8 max-w-xs">
        <label
          for="reservation-status"
          class="mb-2 block text-sm font-medium text-[#172B25]"
        >
          Filter by status
        </label>

        <USelect 
          id="reservation-status"
          v-model="selectedStatus"
          :items="statusOptions"
          class="w-full rounded-xl border border-[#E5E9E4]
                 bg-white px-4 py-3 text-[#172B25]"
        >
        </USelect>
      </div>

      <div
        v-if="filteredReservations.length"
        class="mt-6 grid gap-6"
      >
        <article
          v-for="reservation in filteredReservations"
          :key="reservation.id"
          class="rounded-2xl border border-[#E5E9E4]
                 bg-white p-5 md:p-6"
        >
          <div
            class="flex flex-col gap-6
                   sm:flex-row sm:items-start"
          >
            <img
              :src="reservation.tool.picture"
              :alt="reservation.tool.title"
              class="rounded-xl object-cover
                     sm:w-48 sm:h-40 sm:shrink-0"
            >

            <div class="min-w-0 flex-1">
              <div
                class="flex flex-wrap items-start
                       justify-between gap-3"
              >
                <div>
                  <p class="text-xs text-[#66736D]">
                    Reservation #{{ reservation.id }}
                  </p>

                  <h2
                    class="mt-1 text-xl font-semibold
                           text-[#172B25]"
                  >
                    {{ reservation.tool.title }}
                  </h2>
                </div>

                <span
                  class="rounded-full px-3 py-1 text-xs
                         font-semibold"
                  :class="statusClass(reservation.status)"
                >
                  {{ reservation.status }}
                </span>
              </div>

              <div
                class="mt-5 grid gap-4 text-sm
                       sm:grid-cols-2"
              >
                <div>
                  <p class="text-[#66736D]">
                    Rental period
                  </p>

                  <p class="mt-1 font-medium text-[#172B25]">
                    {{ formatDate(reservation.start_date) }}
                    –
                    {{ formatDate(reservation.end_date) }}
                  </p>
                </div>

                <div>
                  <p class="text-[#66736D]">
                    Total cost
                  </p>

                  <p class="mt-1 font-semibold text-[#172B25]">
                    ${{ reservation.total_cost.toFixed(2) }}
                  </p>
                </div>
              </div>

              <div
                class="mt-5 border-t border-[#E5E9E4] pt-4"
              >
                <UButton
                  :to="`/equipment/${reservation.tool.id}`"
                  label="View Equipment"
                  icon="i-lucide-arrow-up-right"
                  color="neutral"
                  variant="outline"
                />
              </div>
            </div>
          </div>
        </article>
      </div>

      <div
        v-else
        class="mt-6 rounded-2xl border border-[#E5E9E4]
               bg-white px-6 py-16 text-center"
      >
        <UIcon
          name="i-lucide-calendar-x"
          class="mx-auto size-12 text-[#66736D]"
        />

        <h2 class="mt-4 text-xl font-semibold text-[#172B25]">
          No reservations found
        </h2>

        <p class="mt-2 text-[#66736D]">
          There are no reservations matching this status.
        </p>

        <UButton
          label="Show All Reservations"
          color="neutral"
          variant="outline"
          class="mt-5"
          @click="selectedStatus = 'ALL'"
        />
      </div>
    </UContainer>
  </div>
</template>