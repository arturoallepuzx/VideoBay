import { Injectable, inject, signal } from '@angular/core';
import { Observable, catchError, switchMap, tap, throwError } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import { CatalogService } from '../catalog/catalog.service';
import { UiStateService } from '../ui/ui-state.service';
import { FavoriteSlot, MovieListItem, MovieListPage } from './wishlist.models';

@Injectable({ providedIn: 'root' })
export class WishlistService extends BaseApiService {

  readonly wishlistIds = signal(new Set<string>());
  readonly watchLaterIds = signal(new Set<string>());
  readonly wishlistTmdbIds = signal(new Set<string>());
  readonly watchLaterTmdbIds = signal(new Set<string>());

  private readonly ui = inject(UiStateService);
  private readonly catalog = inject(CatalogService);

  loadCounts(): void {
    this.listWishlist().subscribe({
      next: (page) => {
        this.wishlistIds.set(new Set(page.items.map((item) => item.movie_id)));
        this.wishlistTmdbIds.set(this.tmdbSet(page.items));
        this.ui.wishlistCount.set(page.total);
      },
      error: () => undefined,
    });

    this.listWatchLater().subscribe({
      next: (page) => {
        this.watchLaterIds.set(new Set(page.items.map((item) => item.movie_id)));
        this.watchLaterTmdbIds.set(this.tmdbSet(page.items));
        this.ui.watchLaterCount.set(page.total);
      },
      error: () => undefined,
    });
  }

  private tmdbSet(items: MovieListItem[]): Set<string> {
    return new Set(
      items
        .map((item) => item.tmdb_id)
        .filter((id): id is number => id !== null)
        .map(String),
    );
  }

  listWishlist(page = 1): Observable<MovieListPage> {
    return this.get<MovieListPage>('/wishlist', { page });
  }

  listWatchLater(page = 1): Observable<MovieListPage> {
    return this.get<MovieListPage>('/watch-later', { page });
  }

  listFavorites(): Observable<{ slots: FavoriteSlot[] }> {
    return this.get<{ slots: FavoriteSlot[] }>('/favorites');
  }

  isWished(movieId: string): boolean {
    return this.wishlistIds().has(movieId);
  }

  isWatchLater(movieId: string): boolean {
    return this.watchLaterIds().has(movieId);
  }

  isWishedTmdb(tmdbId: string): boolean {
    return this.wishlistTmdbIds().has(tmdbId);
  }

  isWatchLaterTmdb(tmdbId: string): boolean {
    return this.watchLaterTmdbIds().has(tmdbId);
  }

  toggleWishlistByTmdb(tmdbId: string): Observable<unknown> {
    const wasPresent = this.isWishedTmdb(tmdbId);
    this.markWishlistTmdb(tmdbId, !wasPresent);

    return this.catalog.getMovie(tmdbId).pipe(
      switchMap((detail) => {
        const request = wasPresent ? this.delete(`/wishlist/${detail.uuid}`) : this.post(`/wishlist/${detail.uuid}`);
        return request.pipe(tap(() => this.markWishlist(detail.uuid, !wasPresent)));
      }),
      catchError((error) => {
        this.markWishlistTmdb(tmdbId, wasPresent);
        return throwError(() => error);
      }),
    );
  }

  toggleWatchLaterByTmdb(tmdbId: string): Observable<unknown> {
    const wasPresent = this.isWatchLaterTmdb(tmdbId);
    this.markWatchLaterTmdb(tmdbId, !wasPresent);

    return this.catalog.getMovie(tmdbId).pipe(
      switchMap((detail) => {
        const request = wasPresent ? this.delete(`/watch-later/${detail.uuid}`) : this.post(`/watch-later/${detail.uuid}`);
        return request.pipe(tap(() => this.markWatchLater(detail.uuid, !wasPresent)));
      }),
      catchError((error) => {
        this.markWatchLaterTmdb(tmdbId, wasPresent);
        return throwError(() => error);
      }),
    );
  }

  toggleWishlist(movieId: string): Observable<unknown> {
    const wasPresent = this.isWished(movieId);
    this.markWishlist(movieId, !wasPresent);

    const request = wasPresent ? this.delete(`/wishlist/${movieId}`) : this.post(`/wishlist/${movieId}`);

    return request.pipe(
      catchError((error) => {
        this.markWishlist(movieId, wasPresent);
        return throwError(() => error);
      }),
    );
  }

  toggleWatchLater(movieId: string): Observable<unknown> {
    const wasPresent = this.isWatchLater(movieId);
    this.markWatchLater(movieId, !wasPresent);

    const request = wasPresent ? this.delete(`/watch-later/${movieId}`) : this.post(`/watch-later/${movieId}`);

    return request.pipe(
      catchError((error) => {
        this.markWatchLater(movieId, wasPresent);
        return throwError(() => error);
      }),
    );
  }

  addToWishlist(movieId: string): Observable<unknown> {
    return this.post(`/wishlist/${movieId}`).pipe(tap(() => this.markWishlist(movieId, true)));
  }

  addToWatchLater(movieId: string): Observable<unknown> {
    return this.post(`/watch-later/${movieId}`).pipe(tap(() => this.markWatchLater(movieId, true)));
  }

  saveToWishlistByTmdb(tmdbId: string): Observable<unknown> {
    return this.catalog.getMovie(tmdbId).pipe(switchMap((detail) => this.addToWishlist(detail.uuid)));
  }

  saveToWatchLaterByTmdb(tmdbId: string): Observable<unknown> {
    return this.catalog.getMovie(tmdbId).pipe(switchMap((detail) => this.addToWatchLater(detail.uuid)));
  }

  private markWishlist(movieId: string, present: boolean): void {
    const ids = new Set(this.wishlistIds());
    if (present) {
      ids.add(movieId);
    } else {
      ids.delete(movieId);
    }
    this.wishlistIds.set(ids);
    this.ui.wishlistCount.set(ids.size);
  }

  private markWatchLater(movieId: string, present: boolean): void {
    const ids = new Set(this.watchLaterIds());
    if (present) {
      ids.add(movieId);
    } else {
      ids.delete(movieId);
    }
    this.watchLaterIds.set(ids);
    this.ui.watchLaterCount.set(ids.size);
  }

  private markWishlistTmdb(tmdbId: string, present: boolean): void {
    const ids = new Set(this.wishlistTmdbIds());
    if (present) {
      ids.add(tmdbId);
    } else {
      ids.delete(tmdbId);
    }
    this.wishlistTmdbIds.set(ids);
  }

  private markWatchLaterTmdb(tmdbId: string, present: boolean): void {
    const ids = new Set(this.watchLaterTmdbIds());
    if (present) {
      ids.add(tmdbId);
    } else {
      ids.delete(tmdbId);
    }
    this.watchLaterTmdbIds.set(ids);
  }
}
