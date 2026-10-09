
import type { Tool, User } from '~/data/equipment'

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
  returned_condition: string | null

  // Relaciones propuestas para la respuesta del frontend
  tool: Tool
  borrower?: User
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