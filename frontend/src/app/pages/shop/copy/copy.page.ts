import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';
import { InventoryService } from '../../../services/inventory/inventory.service';
import { CopyDetail } from '../../../services/inventory/inventory.models';
import { CartService } from '../../../services/cart/cart.service';
import { ToastService } from '../../../services/ui/toast.service';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { MoneyPipe } from '../../../pipes/money.pipe';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';

@Component({
  selector: 'app-copy',
  imports: [IonIcon, EmptyStateComponent, MoneyPipe, TmdbImagePipe],
  templateUrl: 'copy.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CopyPage {

  protected readonly copy = signal<CopyDetail | null>(null);
  protected readonly notFound = signal(false);

  private readonly inventory = inject(InventoryService);
  protected readonly cart = inject(CartService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly toast = inject(ToastService);

  constructor() {
    this.route.paramMap.subscribe((params) => {
      const id = params.get('id');
      if (id) {
        this.load(id);
      }
    });
  }

  protected add(): void {
    const current = this.copy();
    if (!current) {
      return;
    }

    this.cart.add(current.id, 1, current).subscribe({
      error: () => this.toast.show('No se pudo añadir al carrito'),
    });
  }

  protected openMovie(): void {
    const current = this.copy();
    if (current) {
      this.router.navigate(['/movie', current.movie_id]);
    }
  }

  protected goMarketplace(): void {
    this.router.navigate(['/marketplace']);
  }

  private load(id: string): void {
    this.notFound.set(false);
    this.inventory.getCopy(id).subscribe({
      next: (copy) => this.copy.set(copy),
      error: () => {
        this.copy.set(null);
        this.notFound.set(true);
      },
    });
  }
}
