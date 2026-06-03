import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { IonIcon } from '@ionic/angular/standalone';
import { AdminSidebarComponent } from '../../../components/admin-sidebar/admin-sidebar.component';
import { ReviewService } from '../../../services/review/review.service';
import { ReviewReportItem } from '../../../services/review/review.models';
import { SubtitleService } from '../../../services/subtitle/subtitle.service';
import { SubtitleReportItem } from '../../../services/subtitle/subtitle.models';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';
import { ToastService } from '../../../services/ui/toast.service';

type ModerationTab = 'reviews' | 'subtitles';

@Component({
  selector: 'app-admin-moderation',
  imports: [DatePipe, IonIcon, AdminSidebarComponent, TmdbImagePipe],
  templateUrl: 'admin-moderation.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminModerationPage {

  protected readonly tab = signal<ModerationTab>('reviews');

  protected readonly reports = signal<ReviewReportItem[]>([]);
  protected readonly loading = signal(false);
  protected readonly page = signal(1);
  protected readonly totalPages = signal(1);
  protected readonly total = signal(0);
  protected readonly expandedId = signal<number | null>(null);
  protected readonly resolvingId = signal<number | null>(null);

  protected readonly subReports = signal<SubtitleReportItem[]>([]);
  protected readonly subLoading = signal(false);
  protected readonly subPage = signal(1);
  protected readonly subTotalPages = signal(1);
  protected readonly subTotal = signal(0);
  protected readonly subExpandedId = signal<number | null>(null);
  protected readonly subResolvingId = signal<number | null>(null);
  protected readonly subLoaded = signal(false);

  protected readonly skeletons = Array.from({ length: 4 });

  private readonly reviews = inject(ReviewService);
  private readonly subtitles = inject(SubtitleService);
  private readonly toast = inject(ToastService);

  constructor() {
    this.load(true);
    this.loadSubtitles(true);
  }

  protected setTab(tab: ModerationTab): void {
    this.tab.set(tab);
  }

  protected reasonLabel(reason: string): string {
    return { spam: 'Spam', offensive: 'Ofensivo', hidden_spoiler: 'Spoiler oculto', other: 'Otro' }[reason] ?? reason;
  }

  protected subReasonLabel(reason: string): string {
    return {
      out_of_sync: 'Desincronizado',
      wrong_language: 'Idioma incorrecto',
      spam: 'Spam',
      offensive: 'Ofensivo',
      other: 'Otro',
    }[reason] ?? reason;
  }

  protected langLabel(code: string): string {
    return ({ es: 'Español', en: 'Inglés', fr: 'Francés', de: 'Alemán', it: 'Italiano', pt: 'Portugués' } as Record<string, string>)[code] ?? code.toUpperCase();
  }

  protected toggle(reportId: number): void {
    this.expandedId.set(this.expandedId() === reportId ? null : reportId);
  }

  protected toggleSub(reportId: number): void {
    this.subExpandedId.set(this.subExpandedId() === reportId ? null : reportId);
  }

  protected resolve(reportId: number, decision: 'resolved' | 'dismissed'): void {
    this.resolvingId.set(reportId);

    this.reviews.resolveReport(reportId, decision).subscribe({
      next: () => {
        this.resolvingId.set(null);
        this.reports.set(this.reports().filter((report) => report.id !== reportId));
        this.total.update((value) => Math.max(0, value - 1));
        this.toast.show(decision === 'resolved' ? 'Reseña eliminada' : 'Reporte descartado');
      },
      error: (err: HttpErrorResponse) => {
        this.resolvingId.set(null);
        this.toast.show((err.error?.error as string) ?? 'No se pudo resolver');
      },
    });
  }

  protected resolveSub(reportId: number, decision: 'resolved' | 'dismissed'): void {
    this.subResolvingId.set(reportId);

    this.subtitles.resolveReport(reportId, decision).subscribe({
      next: () => {
        this.subResolvingId.set(null);
        this.subReports.set(this.subReports().filter((report) => report.id !== reportId));
        this.subTotal.update((value) => Math.max(0, value - 1));
        this.toast.show(decision === 'resolved' ? 'Subtítulo eliminado' : 'Reporte descartado');
      },
      error: (err: HttpErrorResponse) => {
        this.subResolvingId.set(null);
        this.toast.show((err.error?.error as string) ?? 'No se pudo resolver');
      },
    });
  }

  protected loadMore(): void {
    if (this.page() < this.totalPages()) {
      this.page.update((value) => value + 1);
      this.load(false);
    }
  }

  protected loadMoreSub(): void {
    if (this.subPage() < this.subTotalPages()) {
      this.subPage.update((value) => value + 1);
      this.loadSubtitles(false);
    }
  }

  private load(reset: boolean): void {
    if (reset) {
      this.page.set(1);
    }
    this.loading.set(true);

    this.reviews.listPendingReports(this.page()).subscribe({
      next: (result) => {
        this.reports.set(reset ? result.items : [...this.reports(), ...result.items]);
        this.totalPages.set(result.total_pages);
        this.total.set(result.total);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  private loadSubtitles(reset: boolean): void {
    if (reset) {
      this.subPage.set(1);
    }
    this.subLoading.set(true);

    this.subtitles.listPendingReports(this.subPage()).subscribe({
      next: (result) => {
        this.subReports.set(reset ? result.items : [...this.subReports(), ...result.items]);
        this.subTotalPages.set(result.total_pages);
        this.subTotal.set(result.total);
        this.subLoading.set(false);
        this.subLoaded.set(true);
      },
      error: () => {
        this.subLoading.set(false);
        this.subLoaded.set(true);
      },
    });
  }
}
