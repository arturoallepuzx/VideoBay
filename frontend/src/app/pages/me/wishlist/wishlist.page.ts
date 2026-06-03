import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { WishlistService } from '../../../services/wishlist/wishlist.service';
import { MovieListItem } from '../../../services/wishlist/wishlist.models';
import { MovieCard } from '../../../services/catalog/catalog.models';
import { MovieCardComponent } from '../../../components/movie-card/movie-card.component';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { ToastService } from '../../../services/ui/toast.service';

@Component({
  selector: 'app-wishlist',
  imports: [MovieCardComponent, EmptyStateComponent],
  templateUrl: 'wishlist.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class WishlistPage {

  protected readonly tab = signal<'wishlist' | 'later'>('wishlist');
  protected readonly wishlistItems = signal<MovieListItem[]>([]);
  protected readonly laterItems = signal<MovieListItem[]>([]);
  protected readonly loaded = signal(false);
  protected readonly skeletons = Array.from({ length: 8 });

  protected readonly items = computed(() =>
    this.tab() === 'wishlist' ? this.wishlistItems() : this.laterItems(),
  );

  protected readonly wishlist = inject(WishlistService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly toast = inject(ToastService);

  constructor() {
    this.route.queryParamMap.subscribe((params) => {
      this.tab.set(params.get('tab') === 'later' ? 'later' : 'wishlist');
    });
    this.reload();
  }

  protected setTab(tab: 'wishlist' | 'later'): void {
    this.tab.set(tab);
  }

  protected openMovie(id: string): void {
    this.router.navigate(['/movie', id]);
  }

  protected goHome(): void {
    this.router.navigate(['/home']);
  }

  protected toCard(item: MovieListItem): MovieCard {
    return {
      movie_id: item.movie_id,
      title: item.title ?? '',
      poster_path: item.poster_path,
      release_year: item.release_year,
      tmdb_rating: null,
    };
  }

  protected onWish(item: MovieListItem): void {
    this.wishlist.toggleWishlist(item.movie_id).subscribe({
      next: () => {
        this.reload();
        this.toast.show(this.wishlist.isWished(item.movie_id) ? 'Añadido a la wishlist' : 'Quitado de la wishlist');
      },
      error: () => this.toast.show('No se pudo actualizar'),
    });
  }

  protected onLater(item: MovieListItem): void {
    this.wishlist.toggleWatchLater(item.movie_id).subscribe({
      next: () => {
        this.reload();
        this.toast.show(this.wishlist.isWatchLater(item.movie_id) ? 'Guardado para ver más tarde' : 'Quitado de la lista');
      },
      error: () => this.toast.show('No se pudo actualizar'),
    });
  }

  private reload(): void {
    this.loaded.set(false);
    let pending = 2;
    const done = (): void => {
      if (--pending === 0) {
        this.loaded.set(true);
      }
    };

    this.wishlist.listWishlist().subscribe({
      next: (page) => {
        this.wishlistItems.set(page.items);
        done();
      },
      error: () => {
        this.wishlistItems.set([]);
        done();
      },
    });
    this.wishlist.listWatchLater().subscribe({
      next: (page) => {
        this.laterItems.set(page.items);
        done();
      },
      error: () => {
        this.laterItems.set([]);
        done();
      },
    });
  }
}
