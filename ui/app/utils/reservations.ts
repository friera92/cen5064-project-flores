export function rentalDays(
  startDate: string,
  endDate: string
): number {
  if (!startDate || !endDate || endDate < startDate) {
    return 0
  }

  const start = Date.parse(`${startDate}T00:00:00Z`)
  const end = Date.parse(`${endDate}T00:00:00Z`)

  if (!Number.isFinite(start) || !Number.isFinite(end)) {
    return 0
  }

  return Math.round((end - start) / 86_400_000) + 1
}

export function estimatedRentalCost(
  dailyRate: number,
  startDate: string,
  endDate: string
): number {
  return rentalDays(startDate, endDate) * dailyRate
}