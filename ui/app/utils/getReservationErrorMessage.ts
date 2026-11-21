export function getReservationErrorMessage(
  error: any
): string {
  const status =
    error?.response?.status ??
    error?.statusCode ??
    error?.status

  switch (status) {
    case 401:
      return 'Your session has expired. Please log in again.'

    case 403:
      return 'You are not authorized to perform this action.'

    case 404:
      return 'The reservation could not be found.'

    case 409:
      return 'The reservation status has changed. Please refresh the page and try again.'

    case 422: {
      const validationMessage =
        error?.data?.errors?.cancellation_reason?.[0]

      return typeof validationMessage === 'string'
        ? validationMessage
        : 'Please verify the information provided.'
    }

    default:
      return 'We encountered an unexpected error. Please try again later.'
  }
}