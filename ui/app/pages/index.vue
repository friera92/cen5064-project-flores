
<script setup lang="ts">
import { equipment, categories } from '~/data/equipment'

const search = ref('')
const selectedCategory = ref('All Equipment')
const maxPrice = ref(50)
const selectedCondition = ref('Any Condition')
const sortBy = ref('recommended')

const startDate = ref('')
const endDate = ref('')

const showMobileFilters = ref(false)

const categoryNames = [
  'All Equipment',
  ...categories.map(category => category.name)
]

const conditions = [
  'Any Condition',
  'Like New',
  'Good'
]

const sortOptions = [
  { label: 'Recommended', value: 'recommended' },
  { label: 'Price: Low to High', value: 'price-asc' },
  { label: 'Price: High to Low', value: 'price-desc' },
  { label: 'Name: A to Z', value: 'name-asc' }
]

const filteredEquipment = computed(() => {
  const query = search.value.trim().toLowerCase()

  const results = equipment.filter((item) => {
    const matchesSearch =
      item.title.toLowerCase().includes(query)
      || item.description.toLowerCase().includes(query)

    const matchesCategory =
      selectedCategory.value === 'All Equipment'
      || item.category.name === selectedCategory.value

    const matchesPrice = item.daily_rate <= maxPrice.value

    const matchesCondition =
      selectedCondition.value === 'Any Condition'
      || item.condition === selectedCondition.value

    return (
      matchesSearch
      && matchesCategory
      && matchesPrice
      && matchesCondition
    )
  })

  if (sortBy.value === 'price-asc') {
    return results.sort((a, b) => a.daily_rate - b.daily_rate)
  }

  if (sortBy.value === 'price-desc') {
    return results.sort((a, b) => b.daily_rate - a.daily_rate)
  }

  if (sortBy.value === 'name-asc') {
    return results.sort((a, b) => a.title.localeCompare(b.title))
  }

  return results
})

const invalidDateRange = computed(() => {
  return Boolean(
    startDate.value
    && endDate.value
    && endDate.value < startDate.value
  )
})

function clearFilters() {
  search.value = ''
  selectedCategory.value = 'All Equipment'
  maxPrice.value = 50
  selectedCondition.value = 'Any Condition'
  sortBy.value = 'recommended'
  startDate.value = ''
  endDate.value = ''
}

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
      <!-- Hero -->
      <section class="bg-[#E9F4ED] py-14 md:py-20">
        <UContainer>
          <div class="mx-auto max-w-3xl text-center">
            <span
              class="inline-flex rounded-full bg-white/70
                     px-4 py-2 text-xs font-bold tracking-widest
                     text-[#176B52]"
            >
              SHARE MORE. BUY LESS.
            </span>

            <h1
              class="mt-6 text-4xl font-bold tracking-tight
                     text-[#172B25] md:text-6xl"
            >
              Borrow. Build.
              <span class="text-[#176B52]">Belong.</span>
            </h1>

            <p class="mx-auto mt-5 max-w-xl text-lg text-[#66736D]">
              Find the tools and equipment you need,
              shared by people in your community.
            </p>

            <div
              class="mx-auto mt-9 max-w-2xl rounded-2xl
                     bg-white p-3 shadow-lg"
            >
              <UInput
                v-model="search"
                icon="i-lucide-search"
                placeholder="Search tools and equipment..."
                size="xl"
                class="w-full"
                :ui="{ base: 'w-full bg-white text-[#172B25]' }"
              />
            </div>
          </div>
        </UContainer>
      </section>

      <!-- Catalog -->
      <section class="py-10 md:py-14">
        <UContainer>
          <div
            class="mb-8 flex flex-col justify-between gap-4
                   sm:flex-row sm:items-end"
          >
            <div>
              <h2 class="text-3xl font-bold text-[#172B25]">
                Explore Equipment
              </h2>

              <p class="mt-2 text-[#66736D]">
                Discover what's available in your community.
              </p>
            </div>

            <span class="text-sm text-[#66736D]">
              {{ filteredEquipment.length }} items found
            </span>
          </div>

          <div class="grid grid-cols-1 gap-8 lg:grid-cols-[260px_minmax(0,1fr)]">
            <!-- Filters -->
            <aside>
              <UButton
                label="Filters"
                icon="i-lucide-sliders-horizontal"
                color="neutral"
                variant="outline"
                block
                class="mb-4 lg:hidden"
                @click="showMobileFilters = !showMobileFilters"
              />

              <div
                :class="showMobileFilters ? 'block' : 'hidden lg:block'"
                class="rounded-2xl border border-[#E5E9E4]
                       bg-white p-5"
              >
                <div class="mb-6 flex items-center justify-between">
                  <h3 class="font-semibold text-[#172B25]">
                    Filters
                  </h3>

                  <button
                    type="button"
                    class="text-sm font-medium text-[#176B52]
                           hover:underline"
                    @click="clearFilters"
                  >
                    Clear all
                  </button>
                </div>

                <!-- Category -->
                <div class="border-b border-[#E5E9E4] pb-6">
                  <h4 class="mb-4 text-sm font-semibold text-[#172B25]">
                    Category
                  </h4>

                  <div class="space-y-3">
                    <label
                      v-for="category in categoryNames"
                      :key="category"
                      class="flex cursor-pointer items-center gap-3
                             text-sm text-[#66736D]"
                    >
                      <input
                        v-model="selectedCategory"
                        type="radio"
                        name="category"
                        :value="category"
                        class="accent-[#176B52]"
                      >

                      {{ category }}
                    </label>
                  </div>
                </div>

                <!-- Price -->
                <div class="border-b border-[#E5E9E4] py-6">
                  <div class="mb-4 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-[#172B25]">
                      Maximum daily rate
                    </h4>

                    <span class="text-sm font-semibold text-[#176B52]">
                      ${{ maxPrice }}
                    </span>
                  </div>

                  <input
                    v-model.number="maxPrice"
                    type="range"
                    min="0"
                    max="50"
                    step="5"
                    aria-label="Maximum daily rate"
                    class="w-full accent-[#176B52]"
                  >

                  <div
                    class="mt-1 flex justify-between text-xs
                           text-[#66736D]"
                  >
                    <span>$0</span>
                    <span>$50+</span>
                  </div>
                </div>

                <!-- Condition -->
                <div class="pt-6">
                  <h4 class="mb-4 text-sm font-semibold text-[#172B25]">
                    Condition
                  </h4>

                  <div class="space-y-3">
                    <label
                      v-for="condition in conditions"
                      :key="condition"
                      class="flex cursor-pointer items-center gap-3
                             text-sm text-[#66736D]"
                    >
                      <input
                        v-model="selectedCondition"
                        type="radio"
                        name="condition"
                        :value="condition"
                        class="accent-[#176B52]"
                      >

                      {{ condition }}
                    </label>
                  </div>
                </div>
              </div>
            </aside>

            <!-- Results -->
            <div class="min-w-0">
              <!-- Date and sorting toolbar -->
              <div
                class="mb-6 rounded-2xl border border-[#E5E9E4]
                       bg-white p-4"
              >
                <div
                  class="flex flex-col gap-4
                         xl:flex-row xl:items-end xl:justify-between"
                >
                  <div class="flex flex-wrap gap-3">
                    <label class="flex flex-col gap-1">
                      <span class="text-xs font-medium text-[#66736D]">
                        Start date
                      </span>

                      <input
                        v-model="startDate"
                        type="date"
                        :min="new Date().toISOString().slice(0, 10)"
                        class="rounded-lg border border-[#E5E9E4]
                               bg-white px-3 py-2 text-sm
                               text-[#172B25]"
                      >
                    </label>

                    <label class="flex flex-col gap-1">
                      <span class="text-xs font-medium text-[#66736D]">
                        End date
                      </span>

                      <input
                        v-model="endDate"
                        type="date"
                        :min="startDate || new Date().toISOString().slice(0, 10)"
                        class="rounded-lg border border-[#E5E9E4]
                               bg-white px-3 py-2 text-sm
                               text-[#172B25]"
                      >
                    </label>
                  </div>

                  <label class="flex flex-col gap-1">
                    <span class="text-xs font-medium text-[#66736D]">
                      Sort by
                    </span>

                    <select
                      v-model="sortBy"
                      class="rounded-lg border border-[#E5E9E4]
                             bg-white px-3 py-2 text-sm
                             text-[#172B25]"
                    >
                      <option
                        v-for="option in sortOptions"
                        :key="option.value"
                        :value="option.value"
                      >
                        {{ option.label }}
                      </option>
                    </select>
                  </label>
                </div>

                <p
                  v-if="invalidDateRange"
                  class="mt-3 text-sm text-red-600"
                >
                  End date must be on or after start date.
                </p>

                <p
                  v-else-if="startDate || endDate"
                  class="mt-3 text-xs text-[#66736D]"
                >
                  Date availability will be checked when
                  reservations are connected to the backend.
                </p>
              </div>

              <!-- Cards -->
              <div
                v-if="filteredEquipment.length"
                class="grid grid-cols-1 gap-6
                       sm:grid-cols-2 xl:grid-cols-3"
              >
                <EquipmentCard
                  v-for="item in filteredEquipment"
                  :key="item.id"
                  :item="item"
                />
              </div>

              <!-- Empty state -->
              <div
                v-else
                class="rounded-2xl border border-[#E5E9E4]
                       bg-white px-6 py-20 text-center"
              >
                <UIcon
                  name="i-lucide-search-x"
                  class="mx-auto size-12 text-[#66736D]"
                />

                <h3 class="mt-4 text-xl font-semibold text-[#172B25]">
                  No equipment found
                </h3>

                <p class="mt-2 text-[#66736D]">
                  Try adjusting your search or filters.
                </p>

                <UButton
                  label="Clear Filters"
                  color="neutral"
                  variant="outline"
                  class="mt-6"
                  @click="clearFilters"
                />
              </div>
            </div>
          </div>
        </UContainer>
      </section>
    </main>
  </div>
</template>