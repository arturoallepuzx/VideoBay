export interface Copy {
  id: string;
  movie_id: string;
  movie_title: string | null;
  poster_path: string | null;
  sku: string;
  barcode: string | null;
  format: string;
  region: string | null;
  condition: string;
  cover_photo_url: string | null;
  price_cents: number;
  currency: string;
  stock_available: number;
}

export interface CopiesPage {
  copies: Copy[];
  page: number;
  total_pages: number;
  total: number;
}

export interface CopyDetail extends Copy {
  stock_reserved: number;
  active: boolean;
  created_at: string;
  updated_at: string;
}

export interface CopyFilters {
  movie_id?: string;
  page?: number;
  per_page?: number;
}

export interface PricingRules {
  base_prices_cents: Record<string, number>;
  condition_multipliers: Record<string, number>;
  buy_margin_percent: number;
  currency: string;
  updated_at: string;
}

export interface UpdatePricingPayload {
  base_prices_cents?: Record<string, number>;
  condition_multipliers?: Record<string, number>;
  buy_margin_percent?: number;
}

export interface SaleProposalPayload {
  movie_id?: string | null;
  title_text?: string | null;
  barcode?: string | null;
  format: string;
  condition: string;
  notes?: string | null;
  offered_price_cents?: number | null;
}

export interface AddCopyPayload {
  movie_id: string;
  sku: string;
  barcode?: string | null;
  format: string;
  region?: string | null;
  condition: string;
  cover_photo_url?: string | null;
  price_cents: number;
  stock_available: number;
}

export interface UpdateCopyPayload {
  barcode?: string | null;
  condition?: string;
  cover_photo_url?: string | null;
  price_cents?: number;
  active?: boolean;
}
