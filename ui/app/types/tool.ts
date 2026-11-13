export type ToolCondition = 'CREATED' | 'NEW' | 'GOOD' | 'FAIR' | 'POOR'
export type ToolStatus = 'AVAILABLE' | 'IN_USE' | 'MAINTENANCE' | 'RETIRED'

export interface Category {
  id: number
  name: string
}

export interface Tool {
  id: number
  owner_id: number
  category_id: number
  title: string
  description: string
  daily_rate: string
  availability_status: ToolStatus
  condition: ToolCondition
  picture: string | null
  category?: Category | null
  created_at?: string
}

export interface ToolInput {
  title: string
  description: string
  category_id: number
  daily_rate: number
  condition: ToolCondition
  picture: string | null
}

export interface PaginatedTools {
  current_page: number
  data: Tool[]
  last_page: number
  per_page: number
  total: number
}

export interface ToolFilters {
  q?: string
  category_id?: number
  condition?: ToolCondition
  availability_status?: ToolStatus
  min_rate?: number
  max_rate?: number
  page?: number
  per_page?: number
}
