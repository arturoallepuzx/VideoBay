import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { BaseApiService } from '../api/base-api.service';
import { ContinueWatchingPage, MovieRefPayload, PendingVideoFiles, PlaybackProgress, VideoFilesPage, VideoSourcePayload } from './streaming.models';

@Injectable({ providedIn: 'root' })
export class StreamingService extends BaseApiService {

  getProgress(movieId: string): Observable<PlaybackProgress> {
    return this.get<PlaybackProgress>(`/playback/${movieId}`);
  }

  listContinueWatching(perPage = 12): Observable<ContinueWatchingPage> {
    return this.get<ContinueWatchingPage>('/playback/continue-watching', { per_page: perPage });
  }

  recordProgress(
    movieId: string,
    positionSeconds: number,
    durationSeconds: number | null,
    completed: boolean,
  ): Observable<unknown> {
    return this.put(`/playback/${movieId}`, {
      position_seconds: Math.floor(positionSeconds),
      duration_seconds: durationSeconds !== null ? Math.floor(durationSeconds) : null,
      completed,
    });
  }

  deleteProgress(movieId: string): Observable<unknown> {
    return this.delete(`/playback/${movieId}`);
  }

  streamUrl(videoFileId: string): string {
    return `${environment.apiUrl}/stream/${videoFileId}`;
  }

  listVideos(page = 1): Observable<VideoFilesPage> {
    return this.get<VideoFilesPage>('/admin/videos', { page });
  }

  listPendingSources(): Observable<PendingVideoFiles> {
    return this.get<PendingVideoFiles>('/admin/videos/pending');
  }

  uploadVideo(file: File, movie: MovieRefPayload): Observable<unknown> {
    const form = new FormData();
    form.append('video', file);
    if (movie.movie_uuid) {
      form.append('movie_uuid', movie.movie_uuid);
    }
    if (movie.tmdb_id != null) {
      form.append('tmdb_id', String(movie.tmdb_id));
    }
    return this.post('/admin/videos/upload', form);
  }

  registerVideo(payload: VideoSourcePayload): Observable<unknown> {
    return this.post('/admin/videos/register', payload);
  }

  transcodeExisting(payload: VideoSourcePayload): Observable<unknown> {
    return this.post('/admin/videos/transcode-existing', payload);
  }

  reassignVideo(videoId: string, movie: MovieRefPayload): Observable<unknown> {
    return this.put(`/admin/videos/${videoId}/movie`, movie);
  }

  deleteVideo(videoId: string): Observable<unknown> {
    return this.delete(`/admin/videos/${videoId}`);
  }
}
