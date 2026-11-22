import { getReservationErrorMessage } from '~/utils/getReservationErrorMessage'

export function getAdminErrorMessage(error: unknown): string {
  const payload = (error as { data?: { message?: string; errors?: Record<string, string[]> } })?.data
  const firstValidationError = Object.values(payload?.errors ?? {})[0]?.[0]
  if (firstValidationError) return firstValidationError
  if (payload?.message) return payload.message
  return getReservationErrorMessage(error)
}
