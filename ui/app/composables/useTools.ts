import type { Category, PaginatedTools, Tool, ToolFilters, ToolInput } from '~/types/tool'

export interface ToolPayload {
    title: string
    description: string
    category_id: number
    daily_rate: number
    condition: string
    picture?: File | null
}

export function useTools() {
  const { request } = useApi()

  function listTools(filters: ToolFilters = {}) {
    return request<PaginatedTools>('/api/tools', { query: filters })
  }

  function listMyTools(filters: ToolFilters = {}) {
    return request<PaginatedTools>('/api/tools/mine', { query: filters })
  }

  function getTool(id: number) {
    return request<Tool>(`/api/tools/${id}`)
  }

  function listCategories() {
    return request<Category[]>('/api/categories')
  }

  function createTool(data: ToolPayload) {
    const formData = new FormData()

    formData.append('title', data.title)
    formData.append('description', data.description)
    formData.append('category_id', String(data.category_id))
    formData.append('daily_rate', String(data.daily_rate))
    formData.append('condition', data.condition)

    if (data.picture) {
      formData.append('picture', data.picture)
    }

    console.log('FormData contents:')
    for (const [key, value] of formData.entries()) {
      console.log(`${key}: ${value}`)
    }

    return request<{ message: string; tool: Tool }>(
      '/api/tools/store',
      {
        method: 'POST',
        body: formData
      }
    )
  }

  function updateTool(id: number, data: ToolInput) {
    return request<{ message: string; tool: Tool }>(`/api/tools/${id}`, {
      method: 'PATCH',
      body: data,
    })
  }

  function deleteTool(id: number) {
    return request<void>(`/api/tools/${id}`, { method: 'DELETE' })
  }

  return {
    listTools,
    listMyTools,
    getTool,
    listCategories,
    createTool,
    updateTool,
    deleteTool,
  }
}
