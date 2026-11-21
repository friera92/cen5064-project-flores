<script setup lang="ts">
const props = defineProps<{
  open: boolean
  reasonRequired: boolean
  submitting: boolean
  error?: string
}>()

const emit = defineEmits<{
  close: []
  confirm: [reason: string | null]
}>()

const dialog = ref<HTMLDialogElement | null>(null)
const reason = ref('')

watch(() => props.open, async (open) => {
  if (open) {
    reason.value = ''
    await nextTick()
    if (dialog.value && !dialog.value.open) dialog.value.showModal()
  } else if (dialog.value?.open) {
    dialog.value.close()
  }
}, { immediate: true })

function requestClose(): void {
  if (!props.submitting) emit('close')
}

function submit(): void {
  const value = reason.value.trim()
  if (props.submitting || value.length > 500 || (props.reasonRequired && !value)) return
  emit('confirm', value || null)
}
</script>

<template>
  <dialog
    ref="dialog"
    aria-labelledby="cancel-reservation-title"
    aria-describedby="cancel-reservation-description"
    class="m-auto w-[min(92vw,520px)] rounded-2xl border border-[#E5E9E4] bg-white p-6 text-[#172B25] shadow-xl backdrop:bg-black/50"
    @cancel="props.submitting ? $event.preventDefault() : emit('close')"
    @close="requestClose"
  >
    <form @submit.prevent="submit">
      <h2 id="cancel-reservation-title" class="text-xl font-bold">Cancel Reservation</h2>
      <p id="cancel-reservation-description" class="mt-2 text-sm text-[#66736D]">
        The other participant will be able to read the reason you provide.
      </p>

      <label for="cancellation-reason" class="mt-5 block text-sm font-semibold">
        Cancellation reason {{ reasonRequired ? '(required)' : '(optional)' }}
      </label>
      <textarea
        id="cancellation-reason"
        v-model="reason"
        :required="reasonRequired"
        :disabled="submitting"
        maxlength="500"
        rows="4"
        placeholder="Briefly explain the cancellation..."
        class="mt-2 w-full resize-y rounded-xl border border-[#E5E9E4] p-3 text-sm"
      />
      <p class="mt-1 text-right text-xs text-[#66736D]">{{ reason.length }}/500</p>
      <p v-if="error" role="alert" class="mt-2 text-sm text-red-700">{{ error }}</p>

      <div class="mt-6 flex justify-end gap-3">
        <button type="button" :disabled="submitting" class="rounded-xl border px-4 py-2 disabled:opacity-50" @click="requestClose">
          Keep reservation
        </button>
        <button type="submit" :disabled="submitting || (reasonRequired && !reason.trim())" class="rounded-xl bg-red-700 px-4 py-2 font-semibold text-white disabled:opacity-50">
          {{ submitting ? 'Canceling...' : 'Confirm cancellation' }}
        </button>
      </div>
    </form>
  </dialog>
</template>
