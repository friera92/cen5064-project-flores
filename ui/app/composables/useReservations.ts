import type {
  Reservation,
  ReservationPayload,
  ReservationQuote,
} from "~/types/reservation";

import type { LenderReservation } from '~/types/lender-reservation'

interface ReservationMutationResponse {
  message: string
  data: Reservation
}

export function useReservations() {
  const { request } = useApi();

  function getQuote(data: ReservationPayload) {
    return request<{ data: ReservationQuote }>("/api/reservation/quote", {
      method: "POST",
      body: data,
    });
  }

  function createReservation(data: ReservationPayload) {
    return request<{
      message: string;
      data: { id: number };
    }>("/api/reservation/new", {
      method: "POST",
      body: data,
    });
  }

  function getReservations() {
    return request<{ data: Reservation[] }>("/api/reservation/all");
  }

  function getReservation(id: number) {
    return request<{ data: Reservation }>(`/api/reservation/${id}`);
  }

  // Laravel scopes this list to tools owned by the authenticated lender.
  function getReceivedReservations() {
    return request<{ data: LenderReservation[] }>('/api/reservation/received')
  }

  function getLenderReservation(id: number) {
    return request<{ data: LenderReservation }>('/api/reservation/' + id)
  }

  function approveReservation(id: number) {
    return request<ReservationMutationResponse>('/api/reservation/' + id + '/approve', {
      method: 'PATCH',
    })
  }

  function cancelReservation(id: number, reason: string | null = null) {
    return request<ReservationMutationResponse>(`/api/reservation/${id}/cancel`, {
      method: "PATCH",
      body: { cancellation_reason: reason?.trim() || null },
    });
  }

  function handOffReservation(id: number) {
    return request<ReservationMutationResponse>('/api/loan/' + id + '/handoff', {
      method: 'PATCH',
    })
  }

  return {
    getQuote,
    createReservation,
    getReservations,
    getReservation,
    getReceivedReservations,
    getLenderReservation,
    approveReservation,
    handOffReservation,
    cancelReservation,
  };
}
