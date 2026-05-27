export type RoleCode = "admin" | "manager" | "staff" | "viewer"

export interface User {
  id: number
  name: string
  email: string
  role: { id: number; name: string; slug: RoleCode } | null
}

export interface Role {
  id: number
  name: string
  slug: RoleCode
  description: string | null
}

export interface Warehouse {
  id: number
  code: string
  name: string
  city: string
  address: string | null
  latitude: number | null
  longitude: number | null
  capacity: number
  manager_id: number | null
  manager?: { id: number; name: string } | null
  is_active: boolean
  total_stock?: number
  total_products?: number
  product_count?: number
}

export interface Category {
  id: number
  name: string
  parent_id: number | null
  description: string | null
  products_count?: number
}

export interface Supplier {
  id: number
  code: string
  name: string
  npwp: string | null
  phone: string | null
  email: string | null
  address: string | null
  pic_name: string | null
  is_active: boolean
}

export interface Product {
  id: number
  sku: string
  name: string
  description: string | null
  category: { id: number; name: string } | null
  unit: string
  min_stock: number
  total_stock: number
  is_low_stock: boolean
  price_buy: number
  price_sell: number
  is_active: boolean
  primary_image?: string | null
  stocks?: ProductStock[]
  created_at: string
}

export interface ProductStock {
  warehouse_id: number
  warehouse_name: string
  warehouse_code: string
  quantity: number
  reserved_quantity: number
  available: number
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface StockMovement {
  id: number
  product_id: number
  warehouse_id: number
  user_id: number
  type: "IN" | "OUT" | "TRANSFER_IN" | "TRANSFER_OUT" | "ADJUSTMENT"
  quantity: number
  unit_price: number
  note: string | null
  movement_date: string
  product?: { id: number; sku: string; name: string; unit: string }
  warehouse?: { id: number; code: string; name: string }
  supplier?: { id: number; name: string } | null
  user?: { id: number; name: string }
  created_at: string
}

export interface StockTransfer {
  id: number
  transfer_code: string
  from_warehouse_id: number
  to_warehouse_id: number
  requested_by: number
  approved_by: number | null
  received_by: number | null
  status: "PENDING" | "APPROVED" | "REJECTED" | "SHIPPED" | "RECEIVED" | "CANCELLED"
  reason: string | null
  reject_note: string | null
  requested_at: string | null
  approved_at: string | null
  shipped_at: string | null
  received_at: string | null
  fromWarehouse?: Warehouse
  toWarehouse?: Warehouse
  requester?: { id: number; name: string }
  approver?: { id: number; name: string } | null
  items?: TransferItem[]
  created_at: string
}

export interface TransferItem {
  id: number
  transfer_id: number
  product_id: number
  quantity: number
  product?: { id: number; sku: string; name: string; unit?: string }
}

export interface Notification {
  id: number
  user_id: number
  type: string
  severity: "CRITICAL" | "WARNING" | "INFO"
  title: string
  message: string
  data: Record<string, unknown> | null
  read_at: string | null
  created_at: string
}

export interface AuditLog {
  id: number
  user_id: number | null
  action: string
  model_type: string | null
  model_id: number | null
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  ip_address: string | null
  user_agent: string | null
  user?: { id: number; name: string; email: string }
  created_at: string
}

export interface ErrorLog {
  id: number
  severity: "CRITICAL" | "WARNING" | "INFO"
  message: string
  file: string | null
  line: number | null
  trace: string | null
  user_id: number | null
  url: string | null
  method: string | null
  is_resolved: boolean
  user?: { id: number; name: string } | null
  created_at: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page?: number
  total: number
  unread_count?: number
  stats?: { critical: number; warning: number; info: number }
}

export interface ApiResponse<T> {
  success: boolean
  message?: string
  data: T
  meta?: PaginationMeta
  errors?: Record<string, string[]>
}
