
<script setup lang="ts">
import type { Tool } from '~/data/equipment'

defineProps<{
  item: Tool
}>()
</script>

<template>
  <UCard
    :ui="{
      root: 'overflow-hidden rounded-2xl border border-[#E5E9E4] bg-white shadow-sm',
      body: 'p-0 sm:p-0'
    }"
  >
    <!-- Equipment image -->
    <div class="relative aspect-[4/3] overflow-hidden bg-[#F2F4EF]">
      <img
        :src="item.picture"
        :alt="item.title"
        class="h-full w-full object-cover transition-transform
               duration-300 hover:scale-105"
      >

      <UBadge
        v-if="item.availability_status"
        label="Available"
        color="success"
        variant="solid"
        class="absolute left-3 top-3"
      />
    </div>

    <!-- Equipment information -->
    <div class="space-y-3 p-5">
      <div class="flex items-center justify-between gap-2">
        <span class="text-xs font-medium text-[#176B52]">
          {{ item.category }}
        </span>

        <!-- <div class="flex items-center gap-1 text-sm">
          <UIcon
            name="i-lucide-star"
            class="size-4 fill-amber-400 text-amber-400"
          />

          <span class="font-medium">
            {{ item.rating.toFixed(1) }}
          </span>
        </div> -->
      </div>

      <div>
        <h3 class="text-lg font-semibold text-[#172B25]">
          {{ item.title }}
        </h3>

        <p class="mt-1 line-clamp-2 text-sm text-[#66736D]">
          {{ item.description }}
        </p>
      </div>

      <div class="flex items-center gap-1 text-sm text-[#66736D]">
        <UIcon name="i-lucide-map-pin" class="size-4" />
        <span>{{ item.owner.address }}</span>
      </div>

      <div class="border-t border-[#E5E9E4] pt-3">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-xl font-bold text-[#172B25]">
              ${{ item.daily_rate }}
            </span>

            <span class="text-sm text-[#66736D]">
              / day
            </span>
          </div>

          <UButton
            :to="`/equipment/${item.id}`"
            label="View Details"
            trailing-icon="i-lucide-arrow-right"
            variant="outline"
            color="neutral"
            size="sm"
          />
        </div>
      </div>
    </div>
  </UCard>
</template>