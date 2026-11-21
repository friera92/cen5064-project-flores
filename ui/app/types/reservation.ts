
import type { Tool } from '~/types/tool'

export type ReturnedCondition = 'GOOD' | 'DAMAGED' | 'BROKEN' | 'MISSING_PARTS'

export interface BorrowerSummary {
  id: number
  name: string
}

export type ReservationStatus =
  | 'REQUESTED'
  | 'APPROVED'
  | 'ACTIVE'
  | 'RETURNED'
  | 'CLOSED'
  | 'DISPUTED'
  | 'CANCELED'

export interface Reservation {
  id: number
  start_date: string
  end_date: string
  total_cost: number | string
  status: ReservationStatus
  returned_condition: ReturnedCondition | null
  borrower_id?: number
  cancellation_reason?: string | null
  canceled_by_user_id?: number | null
  canceled_at?: string | null

  // Laravel reservation detail includes the related tool and borrower.
  tool: Tool
  borrower?: BorrowerSummary | null
}

export interface ReservationQuote {
  days: number
  daily_rate: number
  subtotal: number
  tax_rate: number
  tax: number
  total_cost: number
}

export interface ReservationPayload {
  tool_id: number
  start_date: string
  end_date: string
}

export interface ReservationTool {
  id: number
  title: string
  picture: string
}