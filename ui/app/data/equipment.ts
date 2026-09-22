
/**
 * Mock data based on the NeighbourLend class diagram.
 * The API response structure can be adjusted when Laravel is ready.
 */

export interface User {
  id: number
  name: string
  email: string
  phone: string
  address: string
  picture: string
  is_admin: boolean
}

export interface Category {
  id: number
  name: string
  description: string
}

export interface Tool {
  id: number
  title: string
  description: string
  daily_rate: number
  availability_status: string
  condition: string
  picture: string

  // Relationships for frontend display
  category: Category
  owner: User
}

export const categories: Category[] = [
  {
    id: 1,
    name: 'Power Tools',
    description: 'Electric and battery-powered tools.'
  },
  {
    id: 2,
    name: 'Outdoor',
    description: 'Equipment for gardening and outdoor projects.'
  },
  {
    id: 3,
    name: 'Electronics',
    description: 'Electronic devices and accessories.'
  }
]

export const users: User[] = [
  {
    id: 1,
    name: 'Alex Morgan',
    email: 'alex@example.com',
    phone: '305-555-0101',
    address: 'Kendall, Miami',
    picture: '/images/placeholders/avatar.png',
    is_admin: false
  },
  {
    id: 2,
    name: 'Maria Rodriguez',
    email: 'maria@example.com',
    phone: '305-555-0102',
    address: 'Coral Gables, Miami',
    picture: '/images/placeholders/avatar.png',
    is_admin: false
  },
  {
    id: 3,
    name: 'Daniel Smith',
    email: 'daniel@example.com',
    phone: '305-555-0103',
    address: 'Westchester, Miami',
    picture: '/images/placeholders/avatar.png',
    is_admin: false
  },
  {
    id: 4,
    name: 'Sarah Johnson',
    email: 'sarah@example.com',
    phone: '305-555-0104',
    address: 'Doral, Miami',
    picture: '/images/placeholders/avatar.png',
    is_admin: false
  }
]

export const equipment: Tool[] = [
  {
    id: 1,
    title: 'Cordless Drill',
    description: 'Perfect for home repairs and DIY projects.',
    daily_rate: 12,
    availability_status: 'available',
    condition: 'Like New',
    picture:
      'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=800',
    category: categories[0]!,
    owner: users[0]!
  },
  {
    id: 2,
    title: 'Pressure Washer',
    description: 'Ideal for cleaning patios and driveways.',
    daily_rate: 25,
    availability_status: 'available',
    condition: 'Good',
    picture:
      'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800',
    category: categories[1]!,
    owner: users[1]!
  },
  {
    id: 3,
    title: 'Electric Lawn Mower',
    description: 'Easy-to-use mower for small and medium yards.',
    daily_rate: 20,
    availability_status: 'available',
    condition: 'Good',
    picture:
      'https://images.unsplash.com/photo-1591857177580-dc82b9ac4e1e?w=800',
    category: categories[1]!,
    owner: users[2]!
  },
  {
    id: 4,
    title: 'Professional Camera',
    description: 'Capture your next event or creative project.',
    daily_rate: 35,
    availability_status: 'available',
    condition: 'Like New',
    picture:
      'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800',
    category: categories[2]!,
    owner: users[3]!
  }
]