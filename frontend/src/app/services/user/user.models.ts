export interface AdminUser {
  id: string;
  role: 'admin' | 'customer';
  name: string;
  email: string;
  email_verified_at: string | null;
  avatar_url: string | null;
  active_sessions?: number;
  last_seen_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface CreateUserPayload {
  role: string;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  avatar_url?: string | null;
}

export interface UpdateUserPayload {
  name?: string;
  email?: string;
  role?: string;
  avatar_url?: string | null;
}
