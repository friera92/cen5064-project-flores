export interface ReservationQuote {
  days: number
  daily_rate: number
  subtotal: number
  fee_rate: number
  fee: number
  total_cost: number
}

export interface ReservationPayload {
  tool_id: number
  start_date: string
  end_date: string
}

export function useReservations() {
  const { request } = useApi()

  function getQuote(data: ReservationPayload) {
    return request<{ data: ReservationQuote }>(
      '/api/reservation/quote',
      {
        method: 'POST',
        body: data
      }
    )
  }

  function createReservation(data: ReservationPayload) {
    return request<{
      message: string
      data: { id: number }
    }>(
      '/api/reservation/new',
      {
        method: 'POST',
        body: data
      }
    )
  }

  return {
    getQuote,
    createReservation
  }
}