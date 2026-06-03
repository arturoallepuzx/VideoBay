import { ChangeDetectionStrategy, Component, input, output } from '@angular/core';
import { DecimalPipe } from '@angular/common';
import { IonIcon } from '@ionic/angular/standalone';
import { MovieCard } from '../../services/catalog/catalog.models';
import { TmdbImagePipe } from '../../pipes/tmdb-image.pipe';

@Component({
  selector: 'vb-movie-card',
  imports: [DecimalPipe, IonIcon, TmdbImagePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  styles: [`
    .mc__quick ion-icon { font-size: 15px; }
    .mc__quick button.is-active { background: var(--vb-red); border-color: var(--vb-red); color: #fff; }
  `],
  template: `
    <div class="mc" role="button" tabindex="0" [attr.aria-label]="movie().title"
      (click)="open.emit()" (keydown.enter)="open.emit()" (keydown.space)="open.emit()">
      <div class="mc__poster">
        <img [src]="movie().poster_path | tmdbImage: 'poster'" [alt]="movie().title" loading="lazy" />
        <div class="mc__badges">
          @if (streamable()) {
            <span class="badge badge--stream">Stream</span>
          }
          @if (purchasable()) {
            <span class="badge badge--instore">En tienda</span>
          }
        </div>
        @if (rank()) {
          <div class="mc__rank">{{ rank() }}</div>
        }
        <div class="mc__overlay">
          <div class="mc__quick">
            <button type="button" [class.is-active]="wished()" aria-label="Wishlist"
              (click)="wish.emit(); $event.stopPropagation()">
              <ion-icon [name]="wished() ? 'heart' : 'heart-outline'"></ion-icon>
            </button>
            <button type="button" [class.is-active]="bookmarked()" aria-label="Ver más tarde"
              (click)="watchLater.emit(); $event.stopPropagation()">
              <ion-icon [name]="bookmarked() ? 'bookmark' : 'bookmark-outline'"></ion-icon>
            </button>
            @if (purchasable()) {
              <button type="button" aria-label="Carrito" (click)="addToCart.emit(); $event.stopPropagation()">
                <ion-icon name="bag-outline"></ion-icon>
              </button>
            }
          </div>
        </div>
      </div>
      <h3 class="mc__title">{{ movie().title }}</h3>
      <div class="mc__meta">
        <span>{{ movie().release_year }}</span>
        @if (movie().tmdb_rating !== null) {
          <span class="mc__rating" style="margin-left:auto">★ {{ movie().tmdb_rating | number: '1.1-1' }}</span>
        }
      </div>
    </div>
  `,
})
export class MovieCardComponent {

  readonly movie = input.required<MovieCard>();
  readonly streamable = input(false);
  readonly purchasable = input(false);
  readonly wished = input(false);
  readonly bookmarked = input(false);
  readonly rank = input<number | null>(null);

  readonly open = output<void>();
  readonly wish = output<void>();
  readonly watchLater = output<void>();
  readonly addToCart = output<void>();
}
