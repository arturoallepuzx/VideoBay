export interface PlaybackProgress {
  position_seconds: number | null;
  duration_seconds: number | null;
  completed: boolean;
  updated_at: string | null;
}

export interface ContinueWatchingItem {
  movie_id: string;
  position_seconds: number;
  duration_seconds: number | null;
  updated_at: string;
}

export interface ContinueWatchingPage {
  items: ContinueWatchingItem[];
  page: number;
  total_pages: number;
  total: number;
}

export interface VideoFileMovie {
  id: string;
  title: string | null;
  poster_path: string | null;
  release_year: number | null;
}

export interface VideoFile {
  id: string;
  movie_id: string;
  movie: VideoFileMovie;
  original_filename: string | null;
  storage_disk: string | null;
  duration_seconds: number | null;
  file_size_bytes: number | null;
  audio_language: string | null;
  monthly_cost: number | null;
  processing_status: string;
  processing_error: string | null;
  created_at: string;
  deleted_at: string | null;
}

export interface VideoFilesPage {
  items: VideoFile[];
  page: number;
  total_pages: number;
  total: number;
}

export interface VideoSourceFile {
  filename: string;
  size_bytes: number;
  modified_at: string;
}

export interface PendingVideoFiles {
  root: VideoSourceFile[];
  originals: VideoSourceFile[];
  ingestion_disk: string;
}

export interface MovieRefPayload {
  movie_uuid?: string;
  tmdb_id?: number;
}

export interface VideoSourcePayload extends MovieRefPayload {
  source_filename: string;
}

export interface DiskUsage {
  disk: string;
  video_count: number;
  total_bytes: number;
  monthly_cost: number | null;
}

export interface StorageUsage {
  disks: DiskUsage[];
  total_bytes: number;
  total_video_count: number;
}
