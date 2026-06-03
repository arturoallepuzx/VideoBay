export interface CartItem {
  physical_copy_id: string;
  quantity: number;
  available: boolean;
  movie_id: string | null;
  movie_title: string | null;
  poster_path: string | null;
  format: string | null;
  condition: string | null;
  unit_price_cents: number | null;
  stock_available: number | null;
  subtotal_cents: number;
}

export interface Cart {
  items: CartItem[];
  total_cents: number;
  has_unavailable_items: boolean;
}

export interface CheckoutSession {
  order_id: string;
  checkout_url: string;
  total_cents: number;
}
