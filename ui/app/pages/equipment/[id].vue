<script setup lang="ts">
import type { Tool } from '~/types/tool'

const route = useRoute()
const { getTool } = useTools()
const tool = ref<Tool | null>(null)
const loading = ref(true)
const error = ref('')

const { user, isAuthenticated } = useAuth()

const isOwner = computed(() =>
  Boolean(
    tool.value &&
    user.value &&
    Number(tool.value.owner_id) === Number(user.value.id)
  )
)

const canRequestReservation = computed(() =>
  tool.value?.availability_status === 'AVAILABLE' &&
  (
    !isAuthenticated.value ||
    (user.value !== null && !isOwner.value)
  )
)

onMounted(async () => {
  try { tool.value = await getTool(Number(route.params.id)); console.log('Tool data:', tool.value); }
  catch (e: any) {
    const status =
      e?.response?.status ??
      e?.statusCode ??
      e?.status

    error.value = status === 404
      ? 'Equipment not found.'
      : 'Unable to load equipment. Please try again.'
  }
  finally { loading.value = false }
})
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="max-w-5xl py-10 md:py-14">
      <NuxtLink to="/" class="text-sm font-semibold text-[#176B52]">← Explore Equipment</NuxtLink>
      <p v-if="loading" class="mt-8">Loading equipment...</p>
      <p v-else-if="error" role="alert" class="mt-8 text-red-700">{{ error }}</p>
      <article v-else-if="tool" class="mt-8 grid gap-8 rounded-2xl border bg-white p-6 md:grid-cols-2">
        <img v-if="tool.picture" :src="tool.picture_url" :alt="tool.title" class="w-full rounded-xl object-cover" />
        <div v-else class="flex min-h-64 items-center justify-center rounded-xl bg-[#E9F4ED] text-[#176B52]">No image available</div>
        <div>
          <p class="text-sm text-[#66736D]">{{ tool.category?.name }}</p>
          <h1 class="mt-3 text-3xl font-bold text-[#172B25]">{{ tool.title }}</h1>
          <p class="mt-4 text-[#66736D]">{{ tool.description }}</p>
          <p class="mt-5 text-lg font-semibold text-[#176B52]">${{ Number(tool.daily_rate).toFixed(2) }}/day</p>
          <p class="mt-4 text-sm">Condition: {{ tool.condition }} · Status: {{ tool.availability_status }}</p>
          
          <NuxtLink
            v-if="canRequestReservation"
            :to="`/reservations/new?tool=${tool.id}`"
            class="mt-6 inline-flex rounded-xl bg-[#176B52]
                  px-5 py-3 font-semibold text-white"
          >
            Request Reservation
          </NuxtLink>

          <div v-else-if="isOwner" class="mt-6">
            <p class="text-sm text-[#66736D]">
              This equipment belongs to you.
            </p>

            <NuxtLink
              :to="`/my-equipment/${tool.id}/edit`"
              class="mt-4 inline-flex rounded-xl border
                    border-[#176B52] px-5 py-3
                    font-semibold text-[#176B52]"
            >
              Manage Equipment
            </NuxtLink>
          </div>

          <p
            v-else-if="tool.availability_status !== 'AVAILABLE'"
            class="mt-6 text-sm text-[#66736D]"
          >
            This equipment is currently unavailable
            for new reservations.
          </p>
        </div>
      </article>
    </UContainer>
  </div>
</template>
