import { ChangeDetectionStrategy, Component, ElementRef, computed, inject, signal, viewChild } from '@angular/core';
import { DatePipe, DecimalPipe } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { CatalogService } from '../../../services/catalog/catalog.service';
import { MovieCard, MovieCredit, MovieDetail } from '../../../services/catalog/catalog.models';
import { InventoryService } from '../../../services/inventory/inventory.service';
import { Copy } from '../../../services/inventory/inventory.models';
import { ReviewService } from '../../../services/review/review.service';
import { ReportReason, Review } from '../../../services/review/review.models';
import { MovieCardComponent } from '../../../components/movie-card/movie-card.component';
import { ReportMenuComponent, ReportReasonOption } from '../../../components/report-menu/report-menu.component';
import { ConfirmDialogComponent } from '../../../components/confirm-dialog/confirm-dialog.component';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';
import { RuntimePipe } from '../../../pipes/runtime.pipe';
import { MoneyPipe } from '../../../pipes/money.pipe';
import { ToastService } from '../../../services/ui/toast.service';
import { WishlistService } from '../../../services/wishlist/wishlist.service';
import { CartService } from '../../../services/cart/cart.service';
import { AuthService } from '../../../services/auth/auth.service';

@Component({
  selector: 'app-movie',
  imports: [DatePipe, DecimalPipe, FormsModule, IonIcon, MovieCardComponent, EmptyStateComponent, ReportMenuComponent, ConfirmDialogComponent, TmdbImagePipe, RuntimePipe, MoneyPipe],
  templateUrl: 'movie.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MoviePage {

  protected readonly movie = signal<MovieDetail | null>(null);
  protected readonly copies = signal<Copy[]>([]);
  protected readonly reviews = signal<Review[]>([]);
  protected readonly similar = signal<MovieCard[]>([]);
  protected readonly revealed = signal(new Set<string>());

  protected readonly showReviewForm = signal(false);
  protected readonly reviewRating = signal(8);
  protected readonly hoverRating = signal<number | null>(null);
  protected readonly displayRating = computed(() => this.hoverRating() ?? this.reviewRating());
  protected reviewBody = '';
  protected reviewSpoilers = false;
  protected readonly reviewToDelete = signal<Review | null>(null);

  private readonly castScroller = viewChild<ElementRef<HTMLElement>>('castScroller');

  protected readonly directors = computed(() =>
    (this.movie()?.credits ?? []).filter((credit) => credit.job === 'Director'),
  );

  protected readonly cast = computed(() =>
    (this.movie()?.credits ?? [])
      .filter((credit) => credit.department === 'Acting')
      .sort((a, b) => (a.credit_order ?? 999) - (b.credit_order ?? 999)),
  );

  protected readonly minPriceCents = computed(() => {
    const list = this.copies();
    return list.length ? Math.min(...list.map((copy) => copy.price_cents)) : 0;
  });

  protected readonly totalStock = computed(() =>
    this.copies().reduce((sum, copy) => sum + copy.stock_available, 0),
  );

  private readonly catalog = inject(CatalogService);
  private readonly inventory = inject(InventoryService);
  private readonly reviewService = inject(ReviewService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly toast = inject(ToastService);
  protected readonly wishlist = inject(WishlistService);
  protected readonly cart = inject(CartService);
  protected readonly auth = inject(AuthService);

  constructor() {
    this.route.paramMap.subscribe((params) => {
      const id = params.get('id');
      if (id) {
        this.load(id);
      }
    });
  }

  protected creditYear(date: string | null): number | null {
    return date ? Number(date.slice(0, 4)) : null;
  }

  protected play(): void {
    const current = this.movie();
    if (current) {
      this.router.navigate(['/player', current.uuid]);
    }
  }

  protected openPerson(credit: MovieCredit): void {
    if (this.castMoved) {
      this.castMoved = false;
      return;
    }
    const id = credit.person_tmdb_id ?? credit.person_uuid;
    this.router.navigate(['/person', id]);
  }

  protected openMovie(id: string): void {
    this.router.navigate(['/movie', id]);
  }

  protected openCopy(copy: Copy): void {
    this.router.navigate(['/copy', copy.id]);
  }

  protected scrollToCopies(): void {
    document.getElementById('copies')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  protected saveSimilarWish(card: MovieCard): void {
    this.wishlist.saveToWishlistByTmdb(card.movie_id).subscribe({
      next: () => this.toast.show('Añadido a la wishlist'),
      error: () => this.toast.show('No se pudo guardar'),
    });
  }

  protected saveSimilarLater(card: MovieCard): void {
    this.wishlist.saveToWatchLaterByTmdb(card.movie_id).subscribe({
      next: () => this.toast.show('Guardado para ver más tarde'),
      error: () => this.toast.show('No se pudo guardar'),
    });
  }

  protected toggleSpoiler(reviewId: string): void {
    this.revealed.update((set) => new Set(set).add(reviewId));
  }

  protected wish(): void {
    const current = this.movie();
    if (!current) {
      return;
    }

    this.wishlist.toggleWishlist(current.uuid).subscribe({
      next: () =>
        this.toast.show(this.wishlist.isWished(current.uuid) ? 'Añadido a la wishlist' : 'Quitado de la wishlist'),
      error: () => this.toast.show('No se pudo actualizar la wishlist'),
    });
  }

  protected later(): void {
    const current = this.movie();
    if (!current) {
      return;
    }

    this.wishlist.toggleWatchLater(current.uuid).subscribe({
      next: () =>
        this.toast.show(this.wishlist.isWatchLater(current.uuid) ? 'Guardado para ver más tarde' : 'Quitado de la lista'),
      error: () => this.toast.show('No se pudo actualizar la lista'),
    });
  }

  protected addCopyToCart(copy: Copy): void {
    this.cart.add(copy.id, 1, copy).subscribe({
      error: () => this.toast.show('No se pudo añadir al carrito'),
    });
  }

  protected readonly reviewReasons: ReportReasonOption[] = [
    { value: 'spam', label: 'Spam o publicidad' },
    { value: 'offensive', label: 'Ofensivo o abusivo' },
    { value: 'hidden_spoiler', label: 'Spoiler sin marcar' },
    { value: 'other', label: 'Otro motivo' },
  ];

  protected reportReview(review: Review, reason: string): void {
    this.toast.show('Reseña reportada. Gracias.');
    this.reviewService.report(review.id, reason as ReportReason).subscribe({
      error: () => this.toast.show('No se pudo enviar el reporte'),
    });
  }

  protected readonly starSlots = [1, 2, 3, 4, 5];

  protected starsFor(rating: number): ('full' | 'half' | 'empty')[] {
    const value = rating / 2;
    return Array.from({ length: 5 }, (_, index) => {
      if (value >= index + 1) {
        return 'full';
      }
      return value >= index + 0.5 ? 'half' : 'empty';
    });
  }

  protected starState(slot: number, rating: number): 'full' | 'half' | 'empty' {
    if (rating >= slot * 2) {
      return 'full';
    }
    return rating >= slot * 2 - 1 ? 'half' : 'empty';
  }

  protected starIconName(state: 'full' | 'half' | 'empty'): string {
    return state === 'full' ? 'star' : state === 'half' ? 'star-half' : 'star-outline';
  }

  protected isOwnReview(review: Review): boolean {
    return !!review.author && review.author.uuid === this.auth.user()?.id;
  }

  private castDragging = false;
  private castMoved = false;
  private castStartX = 0;
  private castStartScroll = 0;

  protected onCastDown(event: PointerEvent): void {
    const element = this.castScroller()?.nativeElement;
    if (!element || event.button !== 0) {
      return;
    }
    this.castDragging = true;
    this.castMoved = false;
    this.castStartX = event.clientX;
    this.castStartScroll = element.scrollLeft;
    element.classList.add('is-dragging');
  }

  protected onCastMove(event: PointerEvent): void {
    if (!this.castDragging) {
      return;
    }
    const element = this.castScroller()?.nativeElement;
    if (!element) {
      return;
    }
    const delta = event.clientX - this.castStartX;
    if (Math.abs(delta) > 4) {
      this.castMoved = true;
    }
    element.scrollLeft = this.castStartScroll - delta;
  }

  protected onCastUp(event: PointerEvent): void {
    this.castDragging = false;
    this.castScroller()?.nativeElement.classList.remove('is-dragging');
  }

  protected askDeleteReview(review: Review): void {
    this.reviewToDelete.set(review);
  }

  protected cancelDeleteReview(): void {
    this.reviewToDelete.set(null);
  }

  protected confirmDeleteReview(): void {
    const review = this.reviewToDelete();
    this.reviewToDelete.set(null);
    if (!review) {
      return;
    }

    this.reviews.update((list) => list.filter((item) => item.id !== review.id));

    this.reviewService.remove(review.id).subscribe({
      next: () => this.toast.show('Reseña eliminada'),
      error: () => {
        this.toast.show('No se pudo eliminar la reseña');
        const movie = this.movie();
        if (movie) {
          this.loadReviews(movie.uuid);
        }
      },
    });
  }

  protected likeReview(review: Review): void {
    const liked = !review.liked;
    const delta = liked ? 1 : -1;

    this.patchReview(review.id, (item) => ({
      ...item,
      liked,
      likes_count: Math.max(0, item.likes_count + delta),
    }));

    this.reviewService.toggleLike(review.id).subscribe({
      next: (result) =>
        this.patchReview(review.id, (item) => ({ ...item, liked: result.liked, likes_count: result.likes_count })),
      error: () => {
        this.patchReview(review.id, (item) => ({
          ...item,
          liked: !liked,
          likes_count: Math.max(0, item.likes_count - delta),
        }));
        this.toast.show('No se pudo registrar el me gusta');
      },
    });
  }

  private patchReview(reviewId: string, patch: (review: Review) => Review): void {
    this.reviews.update((list) => list.map((item) => (item.id === reviewId ? patch(item) : item)));
  }

  protected submitReview(): void {
    const current = this.movie();
    if (!current) {
      return;
    }

    this.reviewService
      .create(current.uuid, {
        rating: this.reviewRating(),
        body: this.reviewBody.trim() || null,
        contains_spoilers: this.reviewSpoilers,
      })
      .subscribe({
        next: () => {
          this.toast.show('Reseña publicada');
          this.showReviewForm.set(false);
          this.reviewBody = '';
          this.reviewSpoilers = false;
          this.reviewRating.set(8);
          this.loadReviews(current.uuid);
        },
        error: () => this.toast.show('No se pudo publicar la reseña'),
      });
  }

  private load(identifier: string): void {
    this.catalog.getMovie(identifier).subscribe({
      next: (movie) => {
        this.movie.set(movie);
        this.loadCopies(movie.uuid);
        this.loadReviews(movie.uuid);
        this.loadSimilar(identifier);
      },
      error: () => this.movie.set(null),
    });
  }

  private loadCopies(movieId: string): void {
    this.inventory.listCopies({ movie_id: movieId, per_page: 30 }).subscribe({
      next: (page) => this.copies.set(page.copies),
      error: () => this.copies.set([]),
    });
  }

  private loadReviews(movieId: string): void {
    this.reviewService.listForMovie(movieId).subscribe({
      next: (page) => this.reviews.set(page.items),
      error: () => this.reviews.set([]),
    });
  }

  private loadSimilar(identifier: string): void {
    this.catalog.getSimilar(identifier).subscribe({
      next: (page) =>
        this.similar.set(
          page.results.map((result) => ({
            movie_id: String(result.tmdb_id ?? ''),
            title: result.title,
            poster_path: result.poster_path,
            release_year: result.release_date ? Number(result.release_date.slice(0, 4)) : null,
            tmdb_rating: result.tmdb_rating,
          })),
        ),
      error: () => this.similar.set([]),
    });
  }
}
