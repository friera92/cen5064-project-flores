
<script setup lang="ts">
import { equipment } from '~/data/equipment'

const route = useRoute()

const tool = equipment.find(
  item => item.id === Number(route.params.id)
)

if (!tool) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Equipment not found'
  })
}

useSeoMeta({
  title: `${tool.title} | NeighbourLend`,
  description: tool.description
})

const startDate = ref('')
const endDate = ref('')

// Generate today's date in the browser's local timezone.
function toLocalDateString(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

const today = ref(toLocalDateString(new Date()))

onMounted(() => {
  today.value = toLocalDateString(new Date())
})

const invalidDateRange = computed(() => {
  return Boolean(
    startDate.value
    && endDate.value
    && (
      startDate.value < today.value
      || endDate.value < startDate.value
    )
  )
})

// Calendar days, including both the start and end dates.
const rentalDays = computed(() => {
  if (
    !startDate.value
    || !endDate.value
    || invalidDateRange.value
  ) {
    return 0
  }

  const start = Date.parse(`${startDate.value}T00:00:00Z`)
  const end = Date.parse(`${endDate.value}T00:00:00Z`)

  return Math.round((end - start) / 86_400_000) + 1
})

const estimatedCost = computed(
  () => rentalDays.value * tool.daily_rate
)

watch(startDate, (value) => {
  if (value && endDate.value && endDate.value < value) {
    endDate.value = ''
  }
})
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />

    <main>
      <UContainer class="py-8 md:py-12">
        <!-- Breadcrumb -->
        <nav
          aria-label="Breadcrumb"
          class="mb-8 flex flex-wrap items-center gap-2
                 text-sm text-[#66736D]"
        >
          <NuxtLink to="/" class="hover:text-[#176B52]">
            Explore Equipment
          </NuxtLink>

          <UIcon name="i-lucide-chevron-right" class="size-4" />

          <span>{{ tool.category.name }}</span>

          <UIcon name="i-lucide-chevron-right" class="size-4" />

          <span class="font-medium text-[#172B25]">
            {{ tool.title }}
          </span>
        </nav>

        <!-- Heading -->
        <div class="mb-8">
          <div class="mb-3 flex flex-wrap items-center gap-3">
            <span
              class="rounded-full bg-[#E9F4ED] px-3 py-1
                     text-xs font-semibold text-[#176B52]"
            >
              {{ tool.category.name }}
            </span>

            <span
              class="rounded-full bg-white px-3 py-1
                     text-xs text-[#66736D]"
            >
              {{ tool.condition }}
            </span>
          </div>

          <h1
            class="text-3xl font-bold tracking-tight
                   text-[#172B25] md:text-4xl"
          >
            {{ tool.title }}
          </h1>

          <p
            class="mt-3 flex items-center gap-1
                   text-sm text-[#66736D]"
          >
            <UIcon name="i-lucide-map-pin" class="size-4" />
            {{ tool.owner.address }}
          </p>
        </div>

        <div
          class="grid grid-cols-1 items-start gap-8
                 lg:grid-cols-[minmax(0,1fr)_360px]"
        >
          <!-- Left column -->
          <div class="min-w-0 space-y-8">
            <!-- Image -->
            <div
              class="aspect-[4/3] overflow-hidden rounded-3xl
                     bg-[#E9F4ED] sm:aspect-[16/10]"
            >
              <img
                :src="tool.picture"
                :alt="tool.title"
                class="h-full w-full object-cover"
              >
            </div>

            <!-- Description -->
            <section
              class="rounded-2xl border border-[#E5E9E4]
                     bg-white p-6"
            >
              <h2 class="text-xl font-semibold text-[#172B25]">
                About this equipment
              </h2>

              <p class="mt-4 leading-7 text-[#66736D]">
                {{ tool.description }}
              </p>

              <div
                class="mt-6 grid grid-cols-1 gap-4
                       border-t border-[#E5E9E4] pt-6 sm:grid-cols-2"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="flex size-11 items-center justify-center
                           rounded-xl bg-[#E9F4ED]"
                  >
                    <UIcon
                      name="i-lucide-wrench"
                      class="size-5 text-[#176B52]"
                    />
                  </div>

                  <div>
                    <p class="text-xs text-[#66736D]">
                      Condition
                    </p>

                    <p class="font-medium text-[#172B25]">
                      {{ tool.condition }}
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <div
                    class="flex size-11 items-center justify-center
                           rounded-xl bg-[#E9F4ED]"
                  >
                    <UIcon
                      name="i-lucide-tag"
                      class="size-5 text-[#176B52]"
                    />
                  </div>

                  <div>
                    <p class="text-xs text-[#66736D]">
                      Category
                    </p>

                    <p class="font-medium text-[#172B25]">
                      {{ tool.category.name }}
                    </p>
                  </div>
                </div>
              </div>
            </section>

            <!-- Owner -->
            <section
              class="rounded-2xl border border-[#E5E9E4]
                     bg-white p-6"
            >
              <h2 class="text-xl font-semibold text-[#172B25]">
                Meet the owner
              </h2>

              <div class="mt-5 flex items-center gap-4">
                <div
                  class="flex size-14 shrink-0 items-center
                         justify-center rounded-full bg-[#E9F4ED]"
                >
                  <UIcon
                    name="i-lucide-user-round"
                    class="size-7 text-[#176B52]"
                  />
                </div>

                <div>
                  <p class="font-semibold text-[#172B25]">
                    {{ tool.owner.name }}
                  </p>

                  <p class="mt-1 text-sm text-[#66736D]">
                    {{ tool.owner.address }}
                  </p>
                </div>
              </div>

              <p class="mt-5 text-sm text-[#66736D]">
                Connect with community members and share
                equipment for your next project.
              </p>
            </section>
          </div>

          <!-- Right column: reservation preview -->
          <aside
            class="rounded-2xl border border-[#E5E9E4]
                   bg-white p-6 shadow-sm lg:sticky lg:top-6"
          >
            <div class="flex items-end gap-1">
              <span class="text-3xl font-bold text-[#172B25]">
                ${{ tool.daily_rate }}
              </span>

              <span class="pb-1 text-[#66736D]">
                / day
              </span>
            </div>

            <p
              class="mt-2 flex items-center gap-2 text-sm
                     text-[#66736D]"
            >
              <span
                class="size-2 rounded-full"
                :class="
                  tool.availability_status === 'available'
                    ? 'bg-green-500'
                    : 'bg-amber-500'
                "
              />

              {{ tool.availability_status === 'available'
                ? 'Listed as available'
                : 'Availability status: ' + tool.availability_status
              }}
            </p>

            <UButton
  :to="`/reservations/new?tool=${tool.id}`"
  size="xl"
  block
  color="success" class="mt-2"
>
  Reserve This Tool
</UButton>
          </aside>
        </div>
      </UContainer>
    </main>
  </div>
</template>