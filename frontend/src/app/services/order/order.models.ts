export interface OrderItem {
  physical_copy_id: string;
  quantity: number;
  unit_price_cents: number;
  subtotal_cents: number;
  movie_title: string | null;
  format: string | null;
  condition: string | null;
}

export interface Order {
  id: string;
  status: string;
  total_cents: number;
  pickup_code: string | null;
  paid_at: string | null;
  ready_at: string | null;
  picked_up_at: string | null;
  cancelled_at: string | null;
  expires_at: string | null;
  created_at: string;
  items: OrderItem[];
}

export interface OrdersPage {
  orders: Order[];
  page: number;
  total_pages: number;
  total: number;
}

export interface OrderStatusCounts {
  pending_payment: number;
  paid: number;
  ready_for_pickup: number;
  picked_up: number;
  cancelled: number;
  refunded: number;
}
