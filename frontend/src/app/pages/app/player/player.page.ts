import {
  ChangeDetectionStrategy,
  Component,
  ElementRef,
  HostListener,
  OnDestroy,
  computed,
  inject,
  signal,
  viewChild,
} from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { CatalogService } from '../../../services/catalog/catalog.service';
import { MovieDetail } from '../../../services/catalog/catalog.models';
import { StreamingService } from '../../../services/streaming/streaming.service';
import { SubtitleService } from '../../../services/subtitle/subtitle.service';
import { ExternalSubtitleCandidate, Subtitle, SubtitleReportReason } from '../../../services/subtitle/subtitle.models';
import { ToastService } from '../../../services/ui/toast.service';
import { ThemeService, SubtitleSize } from '../../../services/theme/theme.service';
import { AuthService } from '../../../services/auth/auth.service';
import { ReportMenuComponent, ReportReasonOption } from '../../../components/report-menu/report-menu.component';
import { LogoComponent } from '../../../components/logo/logo.component';
import { DurationPipe } from '../../../pipes/duration.pipe';

@Component({
  selector: 'app-player',
  imports: [FormsModule, IonIcon, ReportMenuComponent, LogoComponent, DurationPipe],
  templateUrl: 'player.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PlayerPage implements OnDestroy {

  protected readonly movie = signal<MovieDetail | null>(null);
  protected readonly subtitles = signal<Subtitle[]>([]);
  protected readonly subOffset = signal(0);
  protected readonly syncMode = signal(false);
  protected readonly syncSpan = computed(() => {
    const max = this.duration() || 600;
    return Math.min(max, Math.max(10, Math.ceil(Math.abs(this.subOffset())) + 5));
  });
  protected readonly notAvailable = signal(false);

  protected readonly playing = signal(false);
  protected readonly buffering = signal(false);
  protected readonly currentTime = signal(0);
  protected readonly duration = signal(0);
  protected readonly muted = signal(false);
  protected readonly volume = signal(1);
  protected readonly idle = signal(false);
  protected readonly isFullscreen = signal(false);
  protected readonly isScrubbing = signal(false);
  protected get isTouch(): boolean {
    return window.matchMedia('(hover: none)').matches;
  }
  protected readonly showSettings = signal(false);
  protected readonly activeSub = signal('off');
  protected readonly cueText = signal('');

  protected readonly showAddSubs = signal(false);
  protected readonly searchingSubs = signal(false);
  protected readonly subCandidates = signal<ExternalSubtitleCandidate[]>([]);
  protected readonly searchLang = signal<string | null>(null);
  protected readonly importingId = signal<string | null>(null);
  protected readonly uploadingSub = signal(false);
  protected subFile: File | null = null;
  protected uploadLang = 'es';

  protected readonly langs = [
    { code: 'es', label: 'Español' },
    { code: 'en', label: 'Inglés' },
    { code: 'fr', label: 'Francés' },
    { code: 'it', label: 'Italiano' },
    { code: 'de', label: 'Alemán' },
    { code: 'pt', label: 'Portugués' },
    { code: 'ja', label: 'Japonés' },
  ];

  protected readonly streamUrl = computed(() => {
    const movie = this.movie();
    return movie && movie.video_file_id ? this.streaming.streamUrl(movie.video_file_id) : null;
  });

  protected readonly progressPct = computed(() => {
    const total = this.duration();
    return total > 0 ? (this.currentTime() / total) * 100 : 0;
  });

  protected readonly streaming = inject(StreamingService);
  protected readonly subtitleService = inject(SubtitleService);
  protected readonly theme = inject(ThemeService);

  private readonly videoRef = viewChild<ElementRef<HTMLVideoElement>>('video');
  private readonly catalog = inject(CatalogService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly toast = inject(ToastService);
  private readonly auth = inject(AuthService);

  private movieId = '';
  private resumeAt = 0;
  private activeTrack: TextTrack | null = null;
  private idleTimer?: ReturnType<typeof setTimeout>;
  private lastPointerX = -1;
  private lastPointerY = -1;
  private saveTimer?: ReturnType<typeof setInterval>;
  private lastTap = 0;
  private tapTimer?: ReturnType<typeof setTimeout>;

  constructor() {
    this.volume.set(this.theme.playerVolume());
    this.muted.set(this.theme.playerMuted());

    this.route.paramMap.subscribe((params) => {
      const id = params.get('id');
      if (id) {
        this.load(id);
      }
    });
  }

  private readonly onCueChange = (): void => {
    const cues = this.activeTrack?.activeCues;
    const parts: string[] = [];
    for (let i = 0; cues && i < cues.length; i++) {
      parts.push((cues[i] as VTTCue).text);
    }
    this.cueText.set(parts.join('\n'));
  };

  onVideoTap(event: MouseEvent): void {
    const now = Date.now();
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const isLeft = event.clientX - rect.left < rect.width / 2;

    if (now - this.lastTap < 300) {
      clearTimeout(this.tapTimer);
      this.lastTap = 0;
      this.skip(isLeft ? -10 : 10);
      return;
    }

    this.lastTap = now;
    this.tapTimer = setTimeout(() => this.onSingleTap(), 260);
  }

  private onSingleTap(): void {
    if (this.isTouch) {
      if (this.idle()) {
        this.wake();
      } else {
        clearTimeout(this.idleTimer);
        this.goIdle();
      }
      return;
    }
    this.togglePlay();
  }

  togglePlay(): void {
    const video = this.element();
    if (!video) {
      return;
    }
    if (video.paused) {
      void video.play();
    } else {
      video.pause();
    }
  }

  onMeta(): void {
    const video = this.element();
    if (!video) {
      return;
    }
    video.volume = this.volume();
    video.muted = this.muted();
    this.duration.set(video.duration || 0);
    if (this.resumeAt > 5 && this.resumeAt < video.duration - 5) {
      video.currentTime = this.resumeAt;
    }
  }

  onTime(): void {
    const video = this.element();
    if (video) {
      this.currentTime.set(video.currentTime);
    }
  }

  onEnded(): void {
    this.playing.set(false);
    this.saveProgress(true);
  }

  onScrubDown(event: PointerEvent): void {
    const el = event.currentTarget as HTMLElement;
    el.setPointerCapture?.(event.pointerId);
    this.isScrubbing.set(true);
    this.seekToPointer(event, el);
    event.stopPropagation();
  }

  onScrubMove(event: PointerEvent): void {
    if (!this.isScrubbing()) {
      return;
    }
    this.seekToPointer(event, event.currentTarget as HTMLElement);
  }

  onScrubUp(event: PointerEvent): void {
    if (!this.isScrubbing()) {
      return;
    }
    this.isScrubbing.set(false);
    (event.currentTarget as HTMLElement).releasePointerCapture?.(event.pointerId);
  }

  private seekToPointer(event: PointerEvent, el: HTMLElement): void {
    const video = this.element();
    if (!video || this.duration() === 0) {
      return;
    }
    const rect = el.getBoundingClientRect();
    const ratio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
    const target = this.duration() * ratio;
    this.currentTime.set(target);
    video.currentTime = target;
  }

  skip(delta: number): void {
    const video = this.element();
    if (!video) {
      return;
    }
    const target = Math.max(0, Math.min(this.duration(), video.currentTime + delta));
    this.currentTime.set(target);
    video.currentTime = target;
  }

  toggleMute(): void {
    const video = this.element();
    if (video) {
      video.muted = !video.muted;
      this.muted.set(video.muted);
      this.theme.setPlayerMuted(video.muted);
    }
  }

  setVolume(event: Event): void {
    const value = Number((event.target as HTMLInputElement).value);
    this.volume.set(value);
    this.theme.setPlayerVolume(value);
    const video = this.element();
    if (video) {
      video.volume = value;
      video.muted = value === 0;
      this.muted.set(video.muted);
      this.theme.setPlayerMuted(video.muted);
    }
  }

  setSubSize(size: SubtitleSize): void {
    this.theme.setSubtitleSize(size);
    this.persistAccessibility();
  }

  toggleSubBg(): void {
    this.theme.setSubtitleBackground(!this.theme.subtitleBackground());
    this.persistAccessibility();
  }

  private persistAccessibility(): void {
    if (this.auth.isAuthenticated()) {
      this.auth.updateAccessibility(this.theme.currentSettings()).subscribe({ error: () => undefined });
    }
  }

  setSubtitle(uuid: string): void {
    this.activateSubtitle(uuid);
    if (uuid !== 'off') {
      const subtitle = this.subtitles().find((item) => item.uuid === uuid);
      if (subtitle) {
        this.theme.setSubtitlePreference(subtitle.language, subtitle.uuid);
        this.persistAccessibility();
      }
    }
  }

  private activateSubtitle(uuid: string): void {
    this.activeSub.set(uuid);
    const video = this.element();
    if (!video) {
      return;
    }
    if (this.activeTrack) {
      this.activeTrack.removeEventListener('cuechange', this.onCueChange);
      this.activeTrack = null;
    }
    const tracks = video.textTracks;
    for (let i = 0; i < tracks.length; i++) {
      tracks[i].mode = 'hidden';
      if (uuid !== 'off' && this.subtitles()[i]?.uuid === uuid) {
        this.activeTrack = tracks[i];
      }
    }
    if (this.activeTrack) {
      this.activeTrack.addEventListener('cuechange', this.onCueChange);
      this.applyOffset(this.activeTrack);
    }
    this.onCueChange();
  }

  private autoSelectSubtitle(items: Subtitle[]): void {
    if (this.activeSub() !== 'off') {
      return;
    }
    const preferred = items.find((item) => item.uuid === this.theme.subtitleUuid())
      ?? items.find((item) => item.language === this.theme.subtitleLanguage());
    if (preferred) {
      setTimeout(() => this.activateSubtitle(preferred.uuid));
    }
  }

  onTrackLoad(event: Event): void {
    const track = (event.target as HTMLTrackElement).track;
    if (track) {
      this.applyOffset(track);
      if (track === this.activeTrack) {
        this.onCueChange();
      }
    }
  }

  toggleSettings(): void {
    this.showSettings.update((open) => !open);
    this.wake();
  }

  openSync(): void {
    if (this.activeSub() === 'off') {
      return;
    }
    this.showSettings.set(false);
    this.syncMode.set(true);
    this.wake();
  }

  closeSync(): void {
    this.syncMode.set(false);
    this.wake();
  }

  setOffset(event: Event): void {
    this.subOffset.set(Math.round(Number((event.target as HTMLInputElement).value) * 10) / 10);
    this.applyActiveOffset();
  }

  offsetText(): string {
    const value = this.subOffset();
    return (value > 0 ? '+' : '') + value.toFixed(1) + 's';
  }

  nudgeOffset(delta: number): void {
    const max = this.duration() || 600;
    this.subOffset.update((value) => Math.round(Math.min(max, Math.max(-max, value + delta)) * 10) / 10);
    this.applyActiveOffset();
  }

  syncHere(): void {
    const video = this.element();
    if (!video) {
      return;
    }
    const cues = this.activeTrack?.cues ?? null;
    if (!cues || cues.length === 0) {
      return;
    }
    const now = video.currentTime;
    const offset = this.subOffset();
    let bestOrigin: number | null = null;
    let bestDistance = Infinity;
    for (let i = 0; i < cues.length; i++) {
      const cue = cues[i] as VTTCue & { vbStart?: number };
      const origin = cue.vbStart ?? cue.startTime;
      const distance = Math.abs(origin + offset - now);
      if (distance < bestDistance) {
        bestDistance = distance;
        bestOrigin = origin;
      }
    }
    if (bestOrigin !== null) {
      this.subOffset.set(Math.round((now - bestOrigin) * 10) / 10);
      this.applyActiveOffset();
    }
  }

  private applyActiveOffset(): void {
    if (this.activeTrack) {
      this.applyOffset(this.activeTrack);
    }
  }

  private applyOffset(track: TextTrack): void {
    const cues = track.cues;
    if (!cues) {
      return;
    }
    const offset = this.subOffset();
    for (let i = 0; i < cues.length; i++) {
      const cue = cues[i] as VTTCue & { vbStart?: number; vbEnd?: number };
      if (cue.vbStart === undefined) {
        cue.vbStart = cue.startTime;
        cue.vbEnd = cue.endTime;
      }
      const start = Math.max(0, cue.vbStart + offset);
      const end = Math.max(start + 0.05, (cue.vbEnd ?? cue.vbStart) + offset);
      if (end >= cue.startTime) {
        cue.endTime = end;
        cue.startTime = start;
      } else {
        cue.startTime = start;
        cue.endTime = end;
      }
    }
  }

  toggleAddSubs(): void {
    this.showAddSubs.update((open) => !open);
    if (!this.showAddSubs()) {
      this.subCandidates.set([]);
      this.searchLang.set(null);
    }
  }

  searchByLang(lang: string): void {
    if (!this.movieId) {
      return;
    }
    this.searchLang.set(lang);
    this.searchingSubs.set(true);
    this.subCandidates.set([]);
    this.subtitleService.searchExternal(this.movieId, lang).subscribe({
      next: (response) => {
        this.subCandidates.set(response.items);
        this.searchingSubs.set(false);
      },
      error: () => {
        this.searchingSubs.set(false);
        this.toast.show('No se pudo buscar subtítulos online');
      },
    });
  }

  importSubtitle(candidate: ExternalSubtitleCandidate): void {
    if (!this.movieId) {
      return;
    }
    this.importingId.set(candidate.file_id);
    this.subtitleService.importExternal(this.movieId, candidate).subscribe({
      next: () => {
        this.importingId.set(null);
        this.subCandidates.set([]);
        this.searchLang.set(null);
        this.showAddSubs.set(false);
        this.toast.show('Subtítulo añadido');
        this.loadSubtitles(this.movieId);
      },
      error: () => {
        this.importingId.set(null);
        this.toast.show('No se pudo importar el subtítulo');
      },
    });
  }

  onSubFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.subFile = input.files?.[0] ?? null;
  }

  uploadSubtitle(): void {
    if (!this.movieId || !this.subFile) {
      this.toast.show('Elige un archivo .srt o .vtt');
      return;
    }
    if (!this.uploadLang.trim()) {
      this.toast.show('Indica el idioma');
      return;
    }
    this.uploadingSub.set(true);
    this.subtitleService.upload(this.movieId, this.subFile, this.uploadLang.trim()).subscribe({
      next: () => {
        this.uploadingSub.set(false);
        this.subFile = null;
        this.showAddSubs.set(false);
        this.toast.show('Subtítulo añadido');
        this.loadSubtitles(this.movieId);
      },
      error: () => {
        this.uploadingSub.set(false);
        this.toast.show('No se pudo subir el subtítulo');
      },
    });
  }

  protected readonly subtitleReasons: ReportReasonOption[] = [
    { value: 'out_of_sync', label: 'Desincronizados' },
    { value: 'wrong_language', label: 'Idioma incorrecto' },
    { value: 'spam', label: 'Spam o publicidad' },
    { value: 'offensive', label: 'Contenido ofensivo' },
    { value: 'other', label: 'Otro motivo' },
  ];

  reportSubtitle(uuid: string, reason: string): void {
    this.toast.show('Subtítulo reportado. Gracias.');
    this.subtitleService.report(uuid, reason as SubtitleReportReason).subscribe({
      error: () => this.toast.show('No se pudo enviar el reporte'),
    });
  }

  toggleFullscreen(): void {
    const host = this.element()?.closest('.player') as HTMLElement | null;
    if (!document.fullscreenElement) {
      void host?.requestFullscreen?.();
    } else {
      void document.exitFullscreen?.();
    }
  }

  @HostListener('document:fullscreenchange')
  onFullscreenChange(): void {
    this.isFullscreen.set(!!document.fullscreenElement);
  }

  close(): void {
    this.saveProgress(false);
    this.router.navigate(['/movie', this.movieId]);
  }

  wake(event?: MouseEvent): void {
    if (event) {
      if (this.isTouch) {
        return;
      }
      const movedX = Math.abs(event.clientX - this.lastPointerX);
      const movedY = Math.abs(event.clientY - this.lastPointerY);
      if (movedX < 3 && movedY < 3) {
        return;
      }
      this.lastPointerX = event.clientX;
      this.lastPointerY = event.clientY;
    }
    this.idle.set(false);
    clearTimeout(this.idleTimer);
    this.idleTimer = setTimeout(() => this.goIdle(), 3500);
  }

  private goIdle(): void {
    if (this.showSettings() || this.syncMode()) {
      return;
    }
    this.idle.set(true);
  }

  @HostListener('document:keydown', ['$event'])
  onKey(event: KeyboardEvent): void {
    if ((event.target as HTMLElement).tagName === 'INPUT') {
      return;
    }
    this.wake();

    if (event.code === 'Space') {
      event.preventDefault();
      this.togglePlay();
    } else if (event.code === 'ArrowLeft') {
      this.skip(-10);
    } else if (event.code === 'ArrowRight') {
      this.skip(10);
    } else if (event.key === 'm' || event.key === 'M') {
      this.toggleMute();
    } else if (event.key === 'f' || event.key === 'F') {
      this.toggleFullscreen();
    } else if (event.key === 'Escape') {
      this.close();
    }
  }

  ngOnDestroy(): void {
    clearInterval(this.saveTimer);
    clearTimeout(this.idleTimer);
    clearTimeout(this.tapTimer);
    this.activeTrack?.removeEventListener('cuechange', this.onCueChange);
    this.saveProgress(false);
  }

  private element(): HTMLVideoElement | null {
    return this.videoRef()?.nativeElement ?? null;
  }

  private load(identifier: string): void {
    this.catalog.getMovie(identifier).subscribe({
      next: (movie) => {
        this.movie.set(movie);
        this.movieId = movie.uuid;

        if (!movie.video_file_id) {
          this.notAvailable.set(true);
          return;
        }

        this.loadSubtitles(movie.uuid);
        this.loadProgress(movie.uuid);
        if (this.isTouch) {
          this.idle.set(true);
        } else {
          this.wake();
        }
        this.saveTimer = setInterval(() => this.saveProgress(false), 10000);
      },
      error: () => this.notAvailable.set(true),
    });
  }

  private loadSubtitles(movieId: string): void {
    this.subtitleService.listForMovie(movieId).subscribe({
      next: (response) => {
        this.subtitles.set(response.items);
        this.autoSelectSubtitle(response.items);
      },
      error: () => this.subtitles.set([]),
    });
  }

  private loadProgress(movieId: string): void {
    this.streaming.getProgress(movieId).subscribe({
      next: (progress) => {
        if (progress.position_seconds) {
          this.resumeAt = progress.position_seconds;
        }
      },
      error: () => undefined,
    });
  }

  private saveProgress(completed: boolean): void {
    const video = this.element();
    if (!video || !this.movieId || !video.currentTime) {
      return;
    }
    const duration = video.duration || 0;
    const finished = completed || (duration > 0 && video.currentTime >= duration * 0.95);
    this.streaming
      .recordProgress(this.movieId, video.currentTime, duration || null, finished)
      .subscribe({ error: () => undefined });
  }
}
