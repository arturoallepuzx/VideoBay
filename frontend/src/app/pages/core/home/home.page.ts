import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';
import { catchError, forkJoin, map, of } from 'rxjs';
import { CatalogService } from '../../../services/catalog/catalog.service';
import { MovieCard } from '../../../services/catalog/catalog.models';
import { StreamingService } from '../../../services/streaming/streaming.service';
import { ContinueWatchingItem } from '../../../services/streaming/streaming.models';
import { InventoryService } from '../../../services/inventory/inventory.service';
import { MovieCardComponent } from '../../../components/movie-card/movie-card.component';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';
import { ToastService } from '../../../services/ui/toast.service';
import { WishlistService } from '../../../services/wishlist/wishlist.service';

interface ContinueWatchingCard {
  uuid: string;
  title: string;
  poster_path: string | null;
  progressPct: number;
}

@Component({
  selector: 'app-home',
  imports: [IonIcon, MovieCardComponent, TmdbImagePipe],
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class HomePage {

  protected readonly streamable = signal<MovieCard[]>([]);
  protected readonly purchasable = signal<MovieCard[]>([]);
  protected readonly continueWatching = signal<ContinueWatchingCard[]>([]);
  protected readonly loaded = signal(false);
  protected readonly skeletons = Array.from({ length: 6 });

  private readonly catalog = inject(CatalogService);
  private readonly streaming = inject(StreamingService);
  private readonly inventory = inject(InventoryService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  protected readonly wishlist = inject(WishlistService);

  constructor() {
    let pending = 2;
    const done = (): void => {
      if (--pending === 0) {
        this.loaded.set(true);
      }
    };

    this.catalog.listStreamable({ per_page: 12 }).subscribe({
      next: (page) => {
        this.streamable.set(page.items);
        done();
      },
      error: () => done(),
    });

    this.catalog.listPurchasable({ per_page: 12 }).subscribe({
      next: (page) => {
        this.purchasable.set(page.items);
        done();
      },
      error: () => done(),
    });

    this.streaming.listContinueWatching(12).subscribe({
      next: (page) => this.loadContinueCards(page.items),
      error: () => undefined,
    });
  }

  protected openMovie(id: string): void {
    this.router.navigate(['/movie', id]);
  }

  protected goToCopy(movie: MovieCard): void {
    this.inventory.listCopies({ movie_id: movie.movie_id, per_page: 24 }).subscribe({
      next: (page) => {
        const available = page.copies.filter((copy) => copy.stock_available > 0);
        const pool = available.length ? available : page.copies;

        if (pool.length === 0) {
          this.openMovie(movie.movie_id);
          return;
        }

        const cheapest = pool.reduce((min, copy) => (copy.price_cents < min.price_cents ? copy : min), pool[0]);
        this.router.navigate(['/copy', cheapest.id]);
      },
      error: () => this.openMovie(movie.movie_id),
    });
  }

  protected resume(id: string): void {
    this.router.navigate(['/player', id]);
  }

  private loadContinueCards(items: ContinueWatchingItem[]): void {
    if (!items.length) {
      return;
    }

    const lookups = items.map((item) =>
      this.catalog.getMovie(item.movie_id).pipe(
        map((movie): ContinueWatchingCard | null =>
          movie.video_file_id
            ? {
                uuid: movie.uuid,
                title: movie.title,
                poster_path: movie.poster_path,
                progressPct: this.progressPct(item),
              }
            : null,
        ),
        catchError(() => of(null)),
      ),
    );

    forkJoin(lookups).subscribe((cards) =>
      this.continueWatching.set(cards.filter((card): card is ContinueWatchingCard => card !== null)),
    );
  }

  private progressPct(item: ContinueWatchingItem): number {
    if (!item.duration_seconds || item.duration_seconds <= 0) {
      return 0;
    }

    return Math.max(2, Math.min(100, Math.round((item.position_seconds / item.duration_seconds) * 100)));
  }

  protected go(path: string): void {
    this.router.navigate([path]);
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
