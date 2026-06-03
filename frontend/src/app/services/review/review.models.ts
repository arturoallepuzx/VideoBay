export interface ReviewAuthor {
  uuid: string;
  name: string | null;
  avatar_url: string | null;
}

export interface Review {
  id: string;
  rating: number;
  body: string | null;
  contains_spoilers: boolean;
  likes_count: number;
  liked: boolean;
  created_at: string;
  updated_at: string;
  author: ReviewAuthor | null;
}

export interface ReviewsPage {
  items: Review[];
  page: number;
  total_pages: number;
  total: number;
}

export interface CreateReviewPayload {
  rating: number;
  body?: string | null;
  contains_spoilers?: boolean;
}

export type ReportReason = 'spam' | 'offensive' | 'hidden_spoiler' | 'other';

export interface ReportedReview {
  id: string;
  body: string | null;
  rating: number;
  contains_spoilers: boolean;
  created_at: string;
  author: ReviewAuthor | null;
}

export interface ReviewReportItem {
  id: number;
  reason: string;
  status: string;
  created_at: string;
  reporter: ReviewAuthor | null;
  review: ReportedReview;
}

export interface ReviewReportsPage {
  items: ReviewReportItem[];
  page: number;
  total_pages: number;
  total: number;
}

export interface ToggleLikeResult {
  review_id: string;
  liked: boolean;
  likes_count: number;
}
