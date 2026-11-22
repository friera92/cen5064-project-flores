<script setup lang="ts">
import { getAdminErrorMessage } from '~/utils/getAdminErrorMessage'

definePageMeta({ middleware: 'admin' })

type Category = { id: number; name: string; description: string; tools_count: number }

const { request } = useApi()
const categories = ref<Category[]>([])
const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const editingId = ref<number | null>(null)
const name = ref('')
const description = ref('')

function resetForm() {
  editingId.value = null
  name.value = ''
  description.value = ''
}

function edit(category: Category) {
  editingId.value = category.id
  name.value = category.name
  description.value = category.description
  successMessage.value = ''
  errorMessage.value = ''
}

async function loadCategories() {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await request<{ data: Category[] }>('/api/admin/categories')
    categories.value = result.data
  } catch (error) {
    errorMessage.value = getAdminErrorMessage(error)
  } finally {
    loading.value = false
  }
}

async function saveCategory() {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  const updating = editingId.value !== null
  try {
    await request(`/api/admin/categories${updating ? `/${editingId.value}` : ''}`, {
      method: updating ? 'PATCH' : 'POST',
      body: { name: name.value.trim(), description: description.value.trim() },
    })
    resetForm()
    await loadCategories()
    successMessage.value = updating ? 'Category updated.' : 'Category created.'
  } catch (error) {
    errorMessage.value = getAdminErrorMessage(error)
  } finally {
    saving.value = false
  }
}

async function deleteCategory(category: Category) {
  if (saving.value || category.tools_count > 0) return
  if (!window.confirm(`Delete category "${category.name}"?`)) return
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await request(`/api/admin/categories/${category.id}`, { method: 'DELETE' })
    if (editingId.value === category.id) resetForm()
    await loadCategories()
    successMessage.value = 'Category deleted.'
  } catch (error) {
    errorMessage.value = getAdminErrorMessage(error)
  } finally {
    saving.value = false
  }
}

onMounted(loadCategories)
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6]">
    <AppHeader />
    <UContainer class="py-10 md:py-14">
      <NuxtLink to="/admin" class="text-sm font-semibold text-[#176B52]">← Administration</NuxtLink>
      <h1 class="mt-5 text-3xl font-bold text-[#172B25]">Category Management</h1>
      <p class="mt-2 text-[#66736D]">Categories used by any listing, including deleted listings, cannot be removed.</p>

      <p v-if="errorMessage" role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ errorMessage }}</p>
      <p v-if="successMessage" role="status" class="mt-5 rounded-xl border border-[#CDE4D7] bg-[#E9F4ED] p-4 text-sm text-[#176B52]">{{ successMessage }}</p>

      <form class="mt-7 rounded-2xl border border-[#E5E9E4] bg-white p-6" @submit.prevent="saveCategory">
        <h2 class="text-lg font-bold text-[#172B25]">{{ editingId ? 'Edit category' : 'Add category' }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <label class="text-sm font-semibold text-[#172B25]">Name
            <input v-model="name" required maxlength="100" :disabled="saving" class="mt-2 block w-full rounded-xl border border-[#E5E9E4] px-4 py-3 font-normal" />
          </label>
          <label class="text-sm font-semibold text-[#172B25]">Description
            <textarea v-model="description" required maxlength="255" rows="2" :disabled="saving" class="mt-2 block w-full rounded-xl border border-[#E5E9E4] px-4 py-3 font-normal" />
          </label>
        </div>
        <div class="mt-4 flex gap-3">
          <button type="submit" :disabled="saving" class="rounded-xl bg-[#176B52] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">{{ saving ? 'Saving...' : editingId ? 'Save changes' : 'Create category' }}</button>
          <button v-if="editingId" type="button" :disabled="saving" class="rounded-xl border px-5 py-3 text-sm font-semibold" @click="resetForm">Cancel</button>
        </div>
      </form>

      <section class="mt-7 rounded-2xl border border-[#E5E9E4] bg-white p-6">
        <h2 class="text-lg font-bold text-[#172B25]">Categories ({{ categories.length }})</h2>
        <p v-if="loading" class="mt-4 text-sm text-[#66736D]">Loading categories...</p>
        <p v-else-if="!categories.length" class="mt-4 text-sm text-[#66736D]">No categories found.</p>
        <div v-else class="mt-4 space-y-3">
          <article v-for="category in categories" :key="category.id" class="flex flex-col justify-between gap-3 rounded-xl border border-[#E5E9E4] p-4 sm:flex-row sm:items-center">
            <div>
              <h3 class="font-semibold text-[#172B25]">{{ category.name }}</h3>
              <p class="mt-1 whitespace-pre-wrap text-sm text-[#66736D]">{{ category.description }}</p>
              <p class="mt-1 text-xs text-[#66736D]">{{ category.tools_count }} listing(s), including historical</p>
            </div>
            <div class="flex gap-2">
              <button type="button" :disabled="saving" class="rounded-xl border px-4 py-2 text-sm font-semibold disabled:opacity-50" @click="edit(category)">Edit</button>
              <button type="button" :disabled="saving || category.tools_count > 0" :title="category.tools_count > 0 ? 'Cannot delete a category used by equipment' : 'Delete category'" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 disabled:opacity-40" @click="deleteCategory(category)">Delete</button>
            </div>
          </article>
        </div>
      </section>
    </UContainer>
  </div>
</template>
