import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';
import { CartService } from '../../../services/cart/cart.service';
import { ToastService } from '../../../services/ui/toast.service';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { MoneyPipe } from '../../../pipes/money.pipe';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';

@Component({
  selector: 'app-cart',
  imports: [IonIcon, EmptyStateComponent, MoneyPipe, TmdbImagePipe],
  templateUrl: 'cart.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CartPage {

  private readonly cartService = inject(CartService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  protected readonly cart = this.cartService.cart;

  constructor() {
    this.cartService.load();
  }

  protected remove(copyId: string): void {
    this.cartService.remove(copyId).subscribe({ error: () => this.toast.show('No se pudo quitar la copia') });
  }

  protected setQuantity(copyId: string, quantity: number): void {
    if (quantity < 1) {
      return;
    }
    this.cartService.updateQuantity(copyId, quantity).subscribe({ error: () => this.toast.show('No se pudo actualizar') });
  }

  protected goCheckout(): void {
    this.router.navigate(['/checkout']);
  }

  protected goMarketplace(): void {
    this.router.navigate(['/marketplace']);
  }
}
