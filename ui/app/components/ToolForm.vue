<script setup lang="ts">
import type { Category, ToolInput, ToolCondition } from '~/types/tool'

const props = defineProps<{
  categories: Category[]
  initial?: Partial<ToolInput>
  busy?: boolean
}>()
const emit = defineEmits<{ submit: [payload: ToolInput] }>()

const selectedImage = ref<File | null>(null)

const form = reactive({
  title: '',
  description: '',
  category_id: 0,
  daily_rate: 1,
  condition: 'GOOD' as ToolCondition,
  picture: '',
})

function onImageSelected(event: Event) {
  const input = event.target as HTMLInputElement
  selectedImage.value = input.files?.[0] ?? null
}

watch(() => props.initial, value => {
  if (!value) return
  form.title = value.title ?? ''
  form.description = value.description ?? ''
  form.category_id = value.category_id ?? 0
  form.daily_rate = value.daily_rate ?? 1
  form.condition = value.condition ?? 'GOOD'
}, { immediate: true })

function submit() {
  if (!form.category_id || !form.title.trim() || !form.description.trim()) return
  emit('submit', {
    title: form.title.trim(),
    description: form.description.trim(),
    category_id: Number(form.category_id),
    daily_rate: Number(form.daily_rate),
    condition: form.condition,
    picture: selectedImage.value || null,
  })
}
</script>

<template>
  <form class="space-y-5 rounded-2xl border border-[#E5E9E4] bg-white p-6" @submit.prevent="submit">
    <label class="block text-sm font-semibold text-[#172B25]">
      Title
      <input v-model="form.title" required maxlength="255" class="mt-2 w-full rounded-xl border p-3 font-normal" />
    </label>
    <label class="block text-sm font-semibold text-[#172B25]">
      Description
      <textarea v-model="form.description" required maxlength="5000" rows="5" class="mt-2 w-full rounded-xl border p-3 font-normal" />
    </label>
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block text-sm font-semibold text-[#172B25]">
        Category
        <select v-model.number="form.category_id" required class="mt-2 w-full rounded-xl border p-3 font-normal">
          <option :value="0" disabled>Select a category</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </select>
      </label>
      <label class="block text-sm font-semibold text-[#172B25]">
        Daily rate ($)
        <input v-model.number="form.daily_rate" type="number" min="0.01" step="0.01" required class="mt-2 w-full rounded-xl border p-3 font-normal" />
      </label>
    </div>
    <label class="block text-sm font-semibold text-[#172B25]">
      Condition
      <select v-model="form.condition" class="mt-2 w-full rounded-xl border p-3 font-normal">
        <option value="CREATED">Created</option>
        <option value="NEW">New</option>
        <option value="GOOD">Good</option>
        <option value="FAIR">Fair</option>
        <option value="POOR">Poor</option>
      </select>
    </label>
    <label
      for="picture"
      class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700"
    >
      Tool Photo

      <UIcon
        v-if="selectedImage"
        name="i-lucide-circle-check"
        class="size-5 text-green-600"
      />

      <span
        v-if="selectedImage"
        class="text-xs font-normal text-green-700"
      >
        Image selected
      </span>
      <input
        id="picture"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        @change="onImageSelected"
      />
    </label>
    <p v-if="!categories.length" class="text-sm text-amber-700">No categories are available in the database yet. Create or seed categories first.</p>
    <button :disabled="busy || !categories.length" type="submit" class="rounded-xl bg-[#176B52] px-6 py-3 font-semibold text-white disabled:opacity-50">
      {{ busy ? 'Saving...' : 'Save tool' }}
    </button>
  </form>
</template>
