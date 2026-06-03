import { AfterViewInit, ChangeDetectionStrategy, Component, ElementRef, computed, inject, signal, viewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { Subject, debounceTime, distinctUntilChanged } from 'rxjs';
import { IonIcon } from '@ionic/angular/standalone';
import { CatalogService } from '../../../services/catalog/catalog.service';
import { MovieCard, MovieSearchResult, PersonSearchResult } from '../../../services/catalog/catalog.models';
import { MovieCardComponent } from '../../../components/movie-card/movie-card.component';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { InfiniteScrollDirective } from '../../../components/infinite-scroll/infinite-scroll.directive';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';
import { WishlistService } from '../../../services/wishlist/wishlist.service';
import { ToastService } from '../../../services/ui/toast.service';

@Component({
  selector: 'app-search',
  imports: [FormsModule, IonIcon, MovieCardComponent, EmptyStateComponent, InfiniteScrollDirective, TmdbImagePipe],
  templateUrl: 'search.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SearchPage implements AfterViewInit {

  protected query = '';
  protected readonly movies = signal<MovieCard[]>([]);
  protected readonly people = signal<PersonSearchResult[]>([]);
  protected readonly searched = signal(false);
  protected readonly searching = signal(false);
  protected readonly loadingMore = signal(false);
  protected readonly tab = signal<'movies' | 'people'>('movies');
  protected readonly skeletons = Array.from({ length: 12 });

  private readonly searchInput = viewChild<ElementRef<HTMLInputElement>>('searchInput');

  private readonly page = signal(1);
  private readonly totalPages = signal(1);

  protected readonly hasMore = computed(() => this.movies().length > 0 && this.page() < this.totalPages());

  private currentTerm = '';

  private readonly catalog = inject(CatalogService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  protected readonly wishlist = inject(WishlistService);
  private readonly toast = inject(ToastService);
  private readonly input$ = new Subject<string>();

  constructor() {
    this.input$.pipe(debounceTime(350), distinctUntilChanged()).subscribe((query) => this.runSearch(query));

    this.route.queryParamMap.subscribe((params) => {
      const query = params.get('q') ?? '';
      if (query && query !== this.query) {
        this.query = query;
        this.runSearch(query);
      }
    });
  }

  ngAfterViewInit(): void {
    this.searchInput()?.nativeElement.focus();
  }

  protected setTab(tab: 'movies' | 'people'): void {
    this.tab.set(tab);
  }

  protected onInput(value: string): void {
    this.query = value;
    this.input$.next(value);
  }

  protected openMovie(id: string): void {
    this.router.navigate(['/movie', id]);
  }

  protected openPerson(id: number | null): void {
    if (id !== null) {
      this.router.navigate(['/person', id]);
    }
  }

  protected saveWish(movie: MovieCard): void {
    this.wishlist.toggleWishlistByTmdb(movie.movie_id).subscribe({
      next: () =>
        this.toast.show(this.wishlist.isWishedTmdb(movie.movie_id) ? 'Añadido a la wishlist' : 'Quitado de la wishlist'),
      error: () => this.toast.show('No se pudo guardar'),
    });
  }

  protected saveLater(movie: MovieCard): void {
    this.wishlist.toggleWatchLaterByTmdb(movie.movie_id).subscribe({
      next: () =>
        this.toast.show(this.wishlist.isWatchLaterTmdb(movie.movie_id) ? 'Guardado para ver más tarde' : 'Quitado de la lista'),
      error: () => this.toast.show('No se pudo guardar'),
    });
  }

  protected loadMore(): void {
    if (this.loadingMore() || !this.hasMore()) {
      return;
    }

    this.loadingMore.set(true);
    const next = this.page() + 1;

    this.catalog.searchMovies(this.currentTerm, next).subscribe({
      next: (page) => {
        this.movies.update((current) => [...current, ...page.results.map((result) => this.toCard(result))]);
        this.page.set(page.page);
        this.totalPages.set(page.total_pages);
        this.loadingMore.set(false);
      },
      error: () => this.loadingMore.set(false),
    });
  }

  private runSearch(query: string): void {
    const term = query.trim();
    this.currentTerm = term;
    this.page.set(1);

    if (!term) {
      this.movies.set([]);
      this.people.set([]);
      this.searched.set(false);
      this.searching.set(false);
      return;
    }

    this.searched.set(true);
    this.searching.set(true);

    this.catalog.searchMovies(term).subscribe({
      next: (page) => {
        this.movies.set(page.results.map((result) => this.toCard(result)));
        this.totalPages.set(page.total_pages);
        this.searching.set(false);
      },
      error: () => {
        this.movies.set([]);
        this.searching.set(false);
      },
    });

    this.catalog.searchPeople(term).subscribe({
      next: (page) => this.people.set(page.results),
      error: () => this.people.set([]),
    });
  }

  private toCard(result: MovieSearchResult): MovieCard {
    return {
      movie_id: String(result.tmdb_id ?? ''),
      title: result.title,
      poster_path: result.poster_path,
      release_year: result.release_date ? Number(result.release_date.slice(0, 4)) : null,
      tmdb_rating: result.tmdb_rating,
    };
  }
}
