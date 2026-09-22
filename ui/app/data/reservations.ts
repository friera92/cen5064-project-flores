
import { equipment, users } from '~/data/equipment'
import type { Reservation } from '~/types/reservation'

export const demoReservations: Reservation[] = [
  {
    id: 101,
    tool: equipment[0]!,
    borrower: users[0]!,
    start_date: '2026-10-05',
    end_date: '2026-10-07',
    total_cost: equipment[0]!.daily_rate * 3,
    status: 'REQUESTED',
    returned_condition: null
  },
  {
    id: 102,
    tool: equipment[1]!,
    borrower: users[0]!,
    start_date: '2026-10-12',
    end_date: '2026-10-13',
    total_cost: equipment[1]!.daily_rate * 2,
    status: 'APPROVED',
    returned_condition: null
  }
]