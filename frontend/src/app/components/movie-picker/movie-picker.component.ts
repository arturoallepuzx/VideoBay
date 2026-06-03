import { ChangeDetectionStrategy, Component, inject, output, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Subject, catchError, debounceTime, distinctUntilChanged, of, switchMap } from 'rxjs';
import { IonIcon } from '@ionic/angular/standalone';
import { CatalogService } from '../../services/catalog/catalog.service';
import { MovieSearchPage, MovieSearchResult } from '../../services/catalog/catalog.models';
import { TmdbImagePipe } from '../../pipes/tmdb-image.pipe';

@Component({
  selector: 'vb-movie-picker',
  imports: [FormsModule, IonIcon, TmdbImagePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div style="position:relative">
      @if (chosen(); as movie) {
        <div style="display:flex;align-items:center;gap:10px;padding:8px;border:1px solid var(--border);border-radius:var(--r-sm);background:var(--surface-2)">
          <div style="width:32px;height:48px;flex-shrink:0;border-radius:var(--r-xs);overflow:hidden;background:var(--surface)">
            @if (movie.poster_path) {
              <img style="width:100%;height:100%;object-fit:cover" [src]="movie.poster_path | tmdbImage: 'poster'" alt="" />
            }
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-family:var(--serif);font-size:14px">{{ movie.title }}</div>
            <div class="mono tiny muted">{{ (movie.release_date || '').slice(0, 4) }} · TMDB {{ movie.tmdb_id }}</div>
          </div>
          <button type="button" class="iconbtn" (click)="clear()" aria-label="Cambiar película">
            <ion-icon name="close" style="font-size:16px"></ion-icon>
          </button>
        </div>
      } @else {
        <input class="field__input" [ngModel]="query" (ngModelChange)="onInput($event)"
          placeholder="Buscar película por nombre…" />
        @if (searching() || results().length) {
          <div style="position:absolute;top:100%;left:0;right:0;z-index:20;margin-top:4px;max-height:300px;overflow:auto;border:1px solid var(--border);border-radius:var(--r-sm);background:var(--surface);box-shadow:var(--shadow)">
            @if (searching()) {
              <div class="tiny muted" style="padding:12px">Buscando…</div>
            }
            @for (movie of results(); track movie.tmdb_id) {
              <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer" (click)="pick(movie)">
                <div style="width:32px;height:48px;flex-shrink:0;border-radius:var(--r-xs);overflow:hidden;background:var(--surface-2)">
                  @if (movie.poster_path) {
                    <img style="width:100%;height:100%;object-fit:cover" [src]="movie.poster_path | tmdbImage: 'poster'" alt="" />
                  }
                </div>
                <div style="min-width:0">
                  <div style="font-family:var(--serif);font-size:14px">{{ movie.title }}</div>
                  <div class="mono tiny muted">{{ (movie.release_date || '').slice(0, 4) }}</div>
                </div>
              </div>
            }
          </div>
        }
      }
    </div>
  `,
})
export class MoviePickerComponent {

  readonly selected = output<MovieSearchResult>();

  protected query = '';
  protected readonly results = signal<MovieSearchResult[]>([]);
  protected readonly searching = signal(false);
  protected readonly chosen = signal<MovieSearchResult | null>(null);

  private readonly catalog = inject(CatalogService);
  private readonly input = new Subject<string>();

  constructor() {
    this.input.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      switchMap((value) => {
        const term = value.trim();
        if (term.length < 2) {
          this.searching.set(false);
          return of<MovieSearchPage>({ results: [], page: 1, total_pages: 0, total_results: 0 });
        }
        this.searching.set(true);
        return this.catalog.searchMovies(term).pipe(
          catchError(() => of<MovieSearchPage>({ results: [], page: 1, total_pages: 0, total_results: 0 })),
        );
      }),
      takeUntilDestroyed(),
    ).subscribe((page) => {
      this.searching.set(false);
      this.results.set(page.results);
    });
  }

  protected onInput(value: string): void {
    this.query = value;
    this.input.next(value);
  }

  protected pick(movie: MovieSearchResult): void {
    this.chosen.set(movie);
    this.results.set([]);
    this.query = movie.title;
    this.selected.emit(movie);
  }

  protected clear(): void {
    this.chosen.set(null);
    this.query = '';
    this.results.set([]);
  }
}
