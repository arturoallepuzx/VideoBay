import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { InventoryService } from '../../../services/inventory/inventory.service';
import { Copy } from '../../../services/inventory/inventory.models';
import { CartService } from '../../../services/cart/cart.service';
import { ToastService } from '../../../services/ui/toast.service';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { InfiniteScrollDirective } from '../../../components/infinite-scroll/infinite-scroll.directive';
import { MoneyPipe } from '../../../pipes/money.pipe';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';

@Component({
  selector: 'app-marketplace',
  imports: [FormsModule, IonIcon, EmptyStateComponent, InfiniteScrollDirective, MoneyPipe, TmdbImagePipe],
  templateUrl: 'marketplace.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MarketplacePage {

  protected readonly copies = signal<Copy[]>([]);
  protected readonly loaded = signal(false);
  protected readonly loadingMore = signal(false);
  protected readonly skeletons = Array.from({ length: 12 });
  protected readonly view = signal<'grid' | 'list'>('grid');
  protected readonly selectedFormats = signal(new Set<string>());
  protected readonly selectedConditions = signal(new Set<string>());

  private readonly page = signal(1);
  private readonly totalPages = signal(1);

  protected readonly hasMore = computed(() => this.page() < this.totalPages());

  protected readonly formats = computed(() =>
    Array.from(new Set(this.copies().map((copy) => copy.format))).sort(),
  );

  protected readonly conditions = computed(() =>
    Array.from(new Set(this.copies().map((copy) => copy.condition))).sort(),
  );

  protected readonly filtered = computed(() => {
    const formats = this.selectedFormats();
    const conditions = this.selectedConditions();

    return this.copies().filter(
      (copy) =>
        (formats.size === 0 || formats.has(copy.format)) &&
        (conditions.size === 0 || conditions.has(copy.condition)),
    );
  });

  private readonly inventory = inject(InventoryService);
  protected readonly cart = inject(CartService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  constructor() {
    this.inventory.listCopies({ page: 1, per_page: 24 }).subscribe({
      next: (page) => {
        this.copies.set(page.copies);
        this.totalPages.set(page.total_pages);
        this.loaded.set(true);
      },
      error: () => this.loaded.set(true),
    });
  }

  protected loadMore(): void {
    if (this.loadingMore() || !this.hasMore()) {
      return;
    }

    this.loadingMore.set(true);
    const next = this.page() + 1;

    this.inventory.listCopies({ page: next, per_page: 24 }).subscribe({
      next: (page) => {
        this.copies.update((current) => [...current, ...page.copies]);
        this.page.set(next);
        this.totalPages.set(page.total_pages);
        this.loadingMore.set(false);
      },
      error: () => this.loadingMore.set(false),
    });
  }

  protected countByFormat(format: string): number {
    return this.copies().filter((copy) => copy.format === format).length;
  }

  protected toggleFormat(format: string): void {
    const set = new Set(this.selectedFormats());
    if (set.has(format)) {
      set.delete(format);
    } else {
      set.add(format);
    }
    this.selectedFormats.set(set);
  }

  protected toggleCondition(condition: string): void {
    const set = new Set(this.selectedConditions());
    if (set.has(condition)) {
      set.delete(condition);
    } else {
      set.add(condition);
    }
    this.selectedConditions.set(set);
  }

  protected open(copy: Copy): void {
    this.router.navigate(['/copy', copy.id]);
  }

  protected add(copy: Copy): void {
    this.cart.add(copy.id, 1, copy).subscribe({
      error: () => this.toast.show('No se pudo añadir al carrito'),
    });
  }
}
