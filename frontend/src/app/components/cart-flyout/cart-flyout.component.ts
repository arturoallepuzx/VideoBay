import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs';
import { IonIcon } from '@ionic/angular/standalone';
import { CartService } from '../../services/cart/cart.service';
import { CartItem } from '../../services/cart/cart.models';
import { UiStateService } from '../../services/ui/ui-state.service';
import { ToastService } from '../../services/ui/toast.service';
import { MoneyPipe } from '../../pipes/money.pipe';
import { TmdbImagePipe } from '../../pipes/tmdb-image.pipe';

@Component({
  selector: 'vb-cart-flyout',
  imports: [IonIcon, MoneyPipe, TmdbImagePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (ui.cartFlyoutOpen()) {
      <div class="cartfly__overlay" (click)="ui.closeCartFlyout()"></div>
      <aside class="cartfly" role="dialog" aria-label="Carrito">
        <div class="cartfly__head">
          <span class="cartfly__title"><ion-icon name="bag-outline"></ion-icon> Tu carrito</span>
          <button class="iconbtn" (click)="ui.closeCartFlyout()" aria-label="Cerrar">
            <ion-icon name="close"></ion-icon>
          </button>
        </div>

        @if (cart.cart(); as c) {
          @if (c.items.length) {
            <div class="cartfly__items">
              @for (item of c.items; track item.physical_copy_id) {
                <div class="cartfly__item">
                  <div class="cartfly__thumb">
                    @if (item.poster_path | tmdbImage: 'poster'; as poster) {
                      <img [src]="poster" alt="" />
                    } @else {
                      <ion-icon name="film-outline"></ion-icon>
                    }
                  </div>
                  <div class="cartfly__item-main">
                    <div class="cartfly__item-title">{{ item.movie_title ?? 'Copia' }}</div>
                    <div class="mono tiny" style="color:var(--text-3)">{{ item.format }} · {{ item.subtotal_cents | money }}</div>
                  </div>
                  <div class="cartfly__qty">
                    <button (click)="dec(item)" aria-label="Quitar uno">−</button>
                    <span>{{ item.quantity }}</span>
                    <button (click)="inc(item)"
                      [disabled]="item.stock_available !== null && item.quantity >= item.stock_available"
                      aria-label="Añadir uno">+</button>
                  </div>
                  <button class="cartfly__remove" (click)="remove(item)" aria-label="Quitar">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              }
            </div>
            <div class="cartfly__total"><span>Total</span><strong>{{ c.total_cents | money }}</strong></div>
            <div class="cartfly__actions">
              <button class="btn" (click)="go('/cart')">Ver carrito</button>
              <button class="btn btn--primary" (click)="go('/checkout')">Finalizar</button>
            </div>
          } @else {
            <div class="cartfly__empty">
              <ion-icon name="bag-outline"></ion-icon>
              <p>Tu carrito está vacío.</p>
              <button class="btn btn--primary" (click)="go('/marketplace')">Ir a la tienda</button>
            </div>
          }
        }
      </aside>
    }
  `,
})
export class CartFlyoutComponent {

  protected readonly ui = inject(UiStateService);
  protected readonly cart = inject(CartService);

  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  constructor() {
    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe(() => this.ui.closeCartFlyout());
  }

  protected inc(item: CartItem): void {
    this.cart.updateQuantity(item.physical_copy_id, item.quantity + 1).subscribe({
      error: () => this.toast.show('No se pudo actualizar el carrito'),
    });
  }

  protected dec(item: CartItem): void {
    if (item.quantity <= 1) {
      this.remove(item);
      return;
    }
    this.cart.updateQuantity(item.physical_copy_id, item.quantity - 1).subscribe({
      error: () => this.toast.show('No se pudo actualizar el carrito'),
    });
  }

  protected remove(item: CartItem): void {
    this.cart.remove(item.physical_copy_id).subscribe({
      error: () => this.toast.show('No se pudo quitar la copia'),
    });
  }

  protected go(path: string): void {
    this.ui.closeCartFlyout();
    this.router.navigate([path]);
  }
}
