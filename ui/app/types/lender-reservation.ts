import type { Tool } from '~/types/tool'
import type { ReservationStatus, ReturnedCondition } from '~/types/reservation'

/** Lender-facing reservation contract returned by the Laravel API. */
export interface BorrowerSummary {
  id: number
  name: string
}

// Re-export so existing lender imports keep working.
export type { ReturnedCondition } from '~/types/reservation'

export interface LenderReservation {
  id: number
  tool_id: number
  borrower_id: number
  start_date: string
  end_date: string
  total_cost: number | string
  status: ReservationStatus
  returned_condition: ReturnedCondition | null
  cancellation_reason?: string | null
  canceled_by_user_id?: number | null
  canceled_at?: string | null
  tool: Tool | null
  borrower?: BorrowerSummary | null
}
