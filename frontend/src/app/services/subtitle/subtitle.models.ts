export type SubtitleSource = 'external' | 'user_upload' | 'admin_upload';

export interface Subtitle {
  uuid: string;
  language: string;
  label: string;
  source: SubtitleSource;
  is_default?: boolean;
}

export interface ExternalSubtitleCandidate {
  provider: 'opensubtitles';
  external_id: string;
  file_id: string;
  language: string;
  label: string;
  download_count?: number | null;
  hearing_impaired: boolean;
}

export type SubtitleReportReason = 'out_of_sync' | 'wrong_language' | 'spam' | 'offensive' | 'other';

export interface SubtitleReportMovie {
  uuid: string;
  title: string | null;
  poster_path: string | null;
  release_year: number | null;
}

export interface SubtitleReportUser {
  uuid: string;
  name: string | null;
  avatar_url: string | null;
}

export interface SubtitleReportSubtitle {
  uuid: string;
  language: string;
  label: string;
  source: SubtitleSource;
  provider: string | null;
  external_id: string | null;
  movie: SubtitleReportMovie;
  uploaded_by: SubtitleReportUser | null;
}

export interface SubtitleReportItem {
  id: number;
  reason: string;
  status: string;
  created_at: string;
  subtitle: SubtitleReportSubtitle;
}

export interface SubtitleReportsPage {
  items: SubtitleReportItem[];
  page: number;
  total_pages: number;
  total: number;
}
