import type {
  Reservation,
  ReservationPayload,
  ReservationQuote,
} from "~/types/reservation";

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

  function cancelReservation(id: number) {
    return request<{
      message: string;
      data: Reservation;
    }>(`/api/reservation/${id}/cancel`, {
      method: "PATCH",
    });
  }

  return {
    getQuote,
    createReservation,
    getReservations,
    getReservation,
    cancelReservation,
  };
}
