import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import { CreateReviewPayload, ReportReason, ReviewReportsPage, ReviewsPage, ToggleLikeResult } from './review.models';

@Injectable({ providedIn: 'root' })
export class ReviewService extends BaseApiService {

  listForMovie(movieId: string, page = 1): Observable<ReviewsPage> {
    return this.get<ReviewsPage>(`/movies/${movieId}/reviews`, { page });
  }

  create(movieId: string, payload: CreateReviewPayload): Observable<unknown> {
    return this.post(`/movies/${movieId}/reviews`, payload);
  }

  update(reviewId: string, payload: CreateReviewPayload): Observable<unknown> {
    return this.put(`/reviews/${reviewId}`, payload);
  }

  remove(reviewId: string): Observable<unknown> {
    return this.delete(`/reviews/${reviewId}`);
  }

  toggleLike(reviewId: string): Observable<ToggleLikeResult> {
    return this.post<ToggleLikeResult>(`/reviews/${reviewId}/like`);
  }

  report(reviewId: string, reason: ReportReason): Observable<unknown> {
    return this.post(`/reviews/${reviewId}/report`, { reason });
  }

  listPendingReports(page = 1): Observable<ReviewReportsPage> {
    return this.get<ReviewReportsPage>('/admin/review-reports', { page });
  }

  resolveReport(reportId: number, decision: 'resolved' | 'dismissed'): Observable<unknown> {
    return this.post(`/admin/review-reports/${reportId}/resolve`, { decision });
  }
}
