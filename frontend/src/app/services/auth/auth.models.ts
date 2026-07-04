export type UserRole = 'admin' | 'customer';

export interface AccessibilitySettings {
  font_scale?: 'sm' | 'md' | 'lg' | 'xl';
  contrast?: 'normal' | 'high';
  motion?: 'normal' | 'reduced';
  big_subtitles?: boolean;
  subtitle_size?: 'sm' | 'md' | 'lg';
  subtitle_background?: boolean;
  subtitle_language?: string;
  subtitle_uuid?: string;
}

export interface AuthUser {
  id: string;
  role: UserRole;
  name: string;
  email: string;
  email_verified_at: string | null;
  avatar_url: string | null;
  accessibility_settings: AccessibilitySettings;
}

export interface AuthSession {
  user: AuthUser;
}
