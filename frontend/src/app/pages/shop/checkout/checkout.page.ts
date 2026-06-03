import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { CartService } from '../../../services/cart/cart.service';
import { ToastService } from '../../../services/ui/toast.service';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { MoneyPipe } from '../../../pipes/money.pipe';

@Component({
  selector: 'app-checkout',
  imports: [FormsModule, IonIcon, EmptyStateComponent, MoneyPipe],
  templateUrl: 'checkout.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CheckoutPage {

  protected agree = true;
  protected readonly loading = signal(false);

  private readonly cartService = inject(CartService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  protected readonly cart = this.cartService.cart;

  constructor() {
    this.cartService.load();
  }

  protected pay(): void {
    this.loading.set(true);

    this.cartService.checkout().subscribe({
      next: (session) => {
        window.location.href = session.checkout_url;
      },
      error: () => {
        this.loading.set(false);
        this.toast.show('No se pudo iniciar el pago');
      },
    });
  }

  protected goMarketplace(): void {
    this.router.navigate(['/marketplace']);
  }
}
