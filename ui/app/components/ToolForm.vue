<script setup lang="ts">
import type { Category, Tool, ToolInput, ToolCondition } from '~/types/tool'

const props = defineProps<{
  categories: Category[]
  initial?: Partial<Tool>
  busy?: boolean
}>()
const emit = defineEmits<{ submit: [payload: ToolInput] }>()

// Existing image already stored in Laravel
const existingImageUrl = ref<string | null>(null)

// New file selected by the user
const selectedImage = ref<File | null>(null)

// Temporary preview for the selected file
const newImagePreview = ref<string | null>(null)

const imagePreview = computed(() =>
  newImagePreview.value || existingImageUrl.value
)

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

  // Release previous preview
  if (newImagePreview.value) {
    URL.revokeObjectURL(newImagePreview.value)
    newImagePreview.value = null
  }

  selectedImage.value = input.files?.[0] ?? null

  if (selectedImage.value) {
    newImagePreview.value = URL.createObjectURL(
      selectedImage.value
    )
    console.log('New image selected:', newImagePreview.value)
  }
}

onBeforeUnmount(() => {
  if (newImagePreview.value) {
    URL.revokeObjectURL(newImagePreview.value)
  }
})

watch(() => props.initial, value => {
  if (!value) return
  form.title = value.title ?? ''
  form.description = value.description ?? ''
  form.category_id = value.category_id ?? 0
  form.daily_rate = value.daily_rate ?? 1
  form.condition = value.condition ?? 'GOOD'

  // Existing image from Laravel
    existingImageUrl.value = value.picture_url ?? null
}, { immediate: true })

function submit() {
  if (!form.category_id || !form.title.trim() || !form.description.trim()) return
  console.log('Submitting form with payload:', {
    title: form.title.trim(),
    description: form.description.trim(),
    category_id: Number(form.category_id),
    daily_rate: Number(form.daily_rate),
    condition: form.condition,
    picture: selectedImage.value || null,
  })
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
    <div class="space-y-3">
    <label
      for="picture"
      class="flex items-center gap-2 text-sm font-medium text-gray-700"
    >
      Tool Photo

      <UIcon
        v-if="selectedImage || existingImageUrl"
        name="i-lucide-circle-check"
        class="size-5 text-green-600"
      />

      <span
        v-if="selectedImage"
        class="text-xs font-normal text-green-700"
      >
        New image selected
      </span>

      <span
        v-else-if="existingImageUrl"
        class="text-xs font-normal text-green-700"
      >
        Current photo
      </span>
    </label>

    <!-- Image preview -->
    <div v-if="imagePreview" class="flex items-start gap-4">
      <img
        :src="imagePreview"
        alt="Tool photo preview"
        class="h-28 w-28 rounded-xl border object-cover"
      />

      <div class="text-sm text-gray-500">
        <p v-if="selectedImage">
          {{ selectedImage.name }}
        </p>

        <p v-else>
          Current tool photo
        </p>
      </div>
    </div>

    <!-- File input -->
    <input
      id="picture"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="w-full rounded-xl border p-3"
      @change="onImageSelected"
    />

    <p class="text-xs text-gray-500">
      Leave empty to keep the current image.
    </p>
  </div>
    <p v-if="!categories.length" class="text-sm text-amber-700">No categories are available in the database yet. Create or seed categories first.</p>
    <button :disabled="busy || !categories.length" type="submit" class="rounded-xl bg-[#176B52] px-6 py-3 font-semibold text-white disabled:opacity-50">
      {{ busy ? 'Saving...' : 'Save tool' }}
    </button>
  </form>
</template>
