import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { BaseApiService } from '../api/base-api.service';
import { ExternalSubtitleCandidate, Subtitle, SubtitleReportReason, SubtitleReportsPage } from './subtitle.models';

@Injectable({ providedIn: 'root' })
export class SubtitleService extends BaseApiService {

  listForMovie(movieId: string): Observable<{ items: Subtitle[] }> {
    return this.get<{ items: Subtitle[] }>(`/movies/${movieId}/subtitles`);
  }

  searchExternal(movieId: string, language?: string): Observable<{ items: ExternalSubtitleCandidate[] }> {
    return this.get<{ items: ExternalSubtitleCandidate[] }>(`/movies/${movieId}/subtitles/external`, { language });
  }

  importExternal(movieId: string, candidate: ExternalSubtitleCandidate): Observable<Subtitle> {
    return this.post<Subtitle>(`/movies/${movieId}/subtitles/import`, {
      provider: candidate.provider,
      external_id: candidate.external_id,
      file_id: candidate.file_id,
      language: candidate.language,
      label: candidate.label,
    });
  }

  upload(movieId: string, file: File, language: string, label?: string): Observable<Subtitle> {
    const formData = new FormData();
    formData.append('subtitle', file);
    formData.append('language', language);

    if (label) {
      formData.append('label', label);
    }

    return this.post<Subtitle>(`/movies/${movieId}/subtitles/upload`, formData);
  }

  report(subtitleId: string, reason: SubtitleReportReason): Observable<unknown> {
    return this.post(`/subtitles/${subtitleId}/report`, { reason });
  }

  listPendingReports(page = 1): Observable<SubtitleReportsPage> {
    return this.get<SubtitleReportsPage>('/admin/subtitle-reports', { page });
  }

  resolveReport(reportId: number, decision: 'resolved' | 'dismissed'): Observable<unknown> {
    return this.post(`/admin/subtitle-reports/${reportId}/resolve`, { decision });
  }

  trackUrl(subtitleUuid: string): string {
    return `${environment.apiUrl}/subtitles/${subtitleUuid}`;
  }
}
