
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
  total_cost: number
  status: ReservationStatus
  returned_condition: string | null

  // Relaciones propuestas para la respuesta del frontend
  tool: Tool
  borrower: User
}