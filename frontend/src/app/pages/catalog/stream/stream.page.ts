import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CatalogService } from '../../../services/catalog/catalog.service';
import { MovieCard } from '../../../services/catalog/catalog.models';
import { MovieCardComponent } from '../../../components/movie-card/movie-card.component';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { InfiniteScrollDirective } from '../../../components/infinite-scroll/infinite-scroll.directive';
import { ToastService } from '../../../services/ui/toast.service';
import { WishlistService } from '../../../services/wishlist/wishlist.service';

@Component({
  selector: 'app-stream',
  imports: [FormsModule, MovieCardComponent, EmptyStateComponent, InfiniteScrollDirective],
  templateUrl: 'stream.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class StreamPage {

  protected readonly movies = signal<MovieCard[]>([]);
  protected readonly total = signal(0);
  protected readonly loaded = signal(false);
  protected readonly loadingMore = signal(false);
  protected readonly skeletons = Array.from({ length: 12 });
  protected sort = 'rating';

  private readonly page = signal(1);
  private readonly totalPages = signal(1);

  protected readonly hasMore = computed(() => this.page() < this.totalPages());

  private readonly catalog = inject(CatalogService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  protected readonly wishlist = inject(WishlistService);

  constructor() {
    this.load();
  }

  protected load(): void {
    this.page.set(1);

    this.catalog.listStreamable({ sort: this.sort, page: 1, per_page: 24 }).subscribe({
      next: (page) => {
        this.movies.set(page.items);
        this.total.set(page.total);
        this.totalPages.set(page.total_pages);
        this.loaded.set(true);
      },
      error: () => {
        this.movies.set([]);
        this.total.set(0);
        this.loaded.set(true);
      },
    });
  }

  protected loadMore(): void {
    if (this.loadingMore() || !this.hasMore()) {
      return;
    }

    this.loadingMore.set(true);
    const next = this.page() + 1;

    this.catalog.listStreamable({ sort: this.sort, page: next, per_page: 24 }).subscribe({
      next: (page) => {
        this.movies.update((current) => [...current, ...page.items]);
        this.page.set(next);
        this.total.set(page.total);
        this.totalPages.set(page.total_pages);
        this.loadingMore.set(false);
      },
      error: () => this.loadingMore.set(false),
    });
  }

  protected openMovie(id: string): void {
    this.router.navigate(['/movie', id]);
  }

  protected toggleWish(movie: MovieCard): void {
    this.wishlist.toggleWishlist(movie.movie_id).subscribe({
      next: () =>
        this.toast.show(this.wishlist.isWished(movie.movie_id) ? 'Añadido a la wishlist' : 'Quitado de la wishlist'),
      error: () => this.toast.show('No se pudo actualizar la wishlist'),
    });
  }

  protected toggleLater(movie: MovieCard): void {
    this.wishlist.toggleWatchLater(movie.movie_id).subscribe({
      next: () =>
        this.toast.show(this.wishlist.isWatchLater(movie.movie_id) ? 'Guardado para ver más tarde' : 'Quitado de la lista'),
      error: () => this.toast.show('No se pudo actualizar la lista'),
    });
  }
}
