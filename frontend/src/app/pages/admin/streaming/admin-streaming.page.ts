import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { IonIcon } from '@ionic/angular/standalone';
import { AdminSidebarComponent } from '../../../components/admin-sidebar/admin-sidebar.component';
import { MoviePickerComponent } from '../../../components/movie-picker/movie-picker.component';
import { StreamingService } from '../../../services/streaming/streaming.service';
import { MovieRefPayload, VideoFile, VideoSourceFile } from '../../../services/streaming/streaming.models';
import { MovieSearchResult } from '../../../services/catalog/catalog.models';
import { ToastService } from '../../../services/ui/toast.service';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';

type AddMode = 'upload' | 'register' | 'transcode';

@Component({
  selector: 'app-admin-streaming',
  imports: [DatePipe, FormsModule, IonIcon, AdminSidebarComponent, MoviePickerComponent, TmdbImagePipe],
  templateUrl: 'admin-streaming.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminStreamingPage {

  protected readonly videos = signal<VideoFile[]>([]);
  protected readonly loading = signal(false);
  protected readonly skeletons = Array.from({ length: 6 });
  protected readonly page = signal(1);
  protected readonly totalPages = signal(1);
  protected readonly total = signal(0);
  protected readonly saving = signal(false);

  protected readonly showAdd = signal(false);
  protected mode: AddMode = 'register';
  protected movie = '';
  protected sourceFilename = '';
  protected file: File | null = null;

  protected readonly rootSources = signal<VideoSourceFile[]>([]);
  protected readonly originalsSources = signal<VideoSourceFile[]>([]);
  protected readonly loadingSources = signal(false);

  protected readonly reassigningId = signal<string | null>(null);
  protected reassignMovie = '';
  protected readonly confirmDeleteId = signal<string | null>(null);

  private readonly streaming = inject(StreamingService);
  private readonly toast = inject(ToastService);

  constructor() {
    this.load(true);
  }

  protected toggleAdd(): void {
    this.showAdd.update((open) => !open);
    if (this.showAdd()) {
      this.movie = '';
      this.sourceFilename = '';
      this.file = null;
      this.loadSources();
    }
  }

  protected setMode(mode: AddMode): void {
    this.mode = mode;
    this.sourceFilename = '';
  }

  protected sourcesForMode(): VideoSourceFile[] {
    return this.mode === 'transcode' ? this.originalsSources() : this.rootSources();
  }

  protected onFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.file = input.files?.[0] ?? null;
  }

  protected onAddMoviePicked(movie: MovieSearchResult): void {
    this.movie = movie.tmdb_id !== null ? String(movie.tmdb_id) : '';
  }

  protected onReassignMoviePicked(movie: MovieSearchResult): void {
    this.reassignMovie = movie.tmdb_id !== null ? String(movie.tmdb_id) : '';
  }

  protected submitAdd(): void {
    const movie = this.movie.trim();
    if (!movie) {
      this.toast.show('Elige la película');
      return;
    }
    const ref = this.movieRef(movie);

    const done = {
      next: () => {
        this.saving.set(false);
        this.showAdd.set(false);
        this.toast.show('Operación enviada');
        this.load(true);
      },
      error: (err: HttpErrorResponse) => {
        this.saving.set(false);
        this.toast.show(this.messageFor(err));
      },
    };

    if (this.mode === 'upload') {
      if (!this.file) {
        this.toast.show('Selecciona un archivo de vídeo');
        return;
      }
      this.saving.set(true);
      this.streaming.uploadVideo(this.file, ref).subscribe(done);
      return;
    }

    if (!this.sourceFilename.trim()) {
      this.toast.show('Elige el archivo de origen');
      return;
    }
    this.saving.set(true);
    const payload = { source_filename: this.sourceFilename.trim(), ...ref };
    if (this.mode === 'register') {
      this.streaming.registerVideo(payload).subscribe(done);
    } else {
      this.streaming.transcodeExisting(payload).subscribe(done);
    }
  }

  protected startReassign(video: VideoFile): void {
    this.confirmDeleteId.set(null);
    this.reassigningId.set(video.id);
    this.reassignMovie = '';
  }

  protected cancelReassign(): void {
    this.reassigningId.set(null);
  }

  protected submitReassign(videoId: string): void {
    const movie = this.reassignMovie.trim();
    if (!movie) {
      this.toast.show('Elige la película destino');
      return;
    }
    this.saving.set(true);
    this.streaming.reassignVideo(videoId, this.movieRef(movie)).subscribe({
      next: () => {
        this.saving.set(false);
        this.reassigningId.set(null);
        this.toast.show('Vídeo reasignado');
        this.load(true);
      },
      error: (err: HttpErrorResponse) => {
        this.saving.set(false);
        this.toast.show(this.messageFor(err));
      },
    });
  }

  protected askRemove(video: VideoFile): void {
    this.reassigningId.set(null);
    this.confirmDeleteId.set(video.id);
  }

  protected cancelRemove(): void {
    this.confirmDeleteId.set(null);
  }

  protected remove(videoId: string): void {
    this.streaming.deleteVideo(videoId).subscribe({
      next: () => {
        this.confirmDeleteId.set(null);
        this.toast.show('Vídeo borrado');
        this.load(true);
      },
      error: (err: HttpErrorResponse) => this.toast.show(this.messageFor(err)),
    });
  }

  protected statusClass(status: string): string {
    if (status === 'ready') {
      return 'status-pill--ready';
    }
    if (status === 'processing') {
      return 'status-pill--paid';
    }
    return '';
  }

  protected sizeMb(bytes: number | null): string {
    return bytes !== null ? `${(bytes / (1024 * 1024)).toFixed(0)} MB` : '—';
  }

  protected durationMin(seconds: number | null): string {
    return seconds !== null ? `${Math.round(seconds / 60)} min` : '—';
  }

  protected loadMore(): void {
    if (this.page() < this.totalPages()) {
      this.page.update((value) => value + 1);
      this.load(false);
    }
  }

  private load(reset: boolean): void {
    if (reset) {
      this.page.set(1);
    }
    this.loading.set(true);

    this.streaming.listVideos(this.page()).subscribe({
      next: (result) => {
        this.videos.set(reset ? result.items : [...this.videos(), ...result.items]);
        this.totalPages.set(result.total_pages);
        this.total.set(result.total);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  private loadSources(): void {
    this.loadingSources.set(true);
    this.streaming.listPendingSources().subscribe({
      next: (result) => {
        this.rootSources.set(result.root);
        this.originalsSources.set(result.originals);
        this.loadingSources.set(false);
      },
      error: () => this.loadingSources.set(false),
    });
  }

  private movieRef(movie: string): MovieRefPayload {
    return /^\d+$/.test(movie) ? { tmdb_id: Number(movie) } : { movie_uuid: movie };
  }

  private messageFor(err: HttpErrorResponse): string {
    return (err.error?.error as string) ?? 'No se pudo completar la operación';
  }
}
