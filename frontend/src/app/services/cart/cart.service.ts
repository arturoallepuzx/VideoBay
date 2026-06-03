import { Injectable, inject, signal } from '@angular/core';
import { Observable, catchError, tap, throwError } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import { UiStateService } from '../ui/ui-state.service';
import { Cart, CartItem, CheckoutSession } from './cart.models';
import { Copy } from '../inventory/inventory.models';

@Injectable({ providedIn: 'root' })
export class CartService extends BaseApiService {

  readonly cart = signal<Cart | null>(null);
  readonly lastAdded = signal<string | null>(null);

  private readonly ui = inject(UiStateService);
  private addedTimer?: ReturnType<typeof setTimeout>;

  load(): void {
    this.fetch().subscribe({ error: () => undefined });
  }

  fetch(): Observable<Cart> {
    return this.get<Cart>('/cart').pipe(tap((cart) => this.store(cart)));
  }

  add(copyId: string, quantity = 1, preview?: Copy): Observable<unknown> {
    this.optimisticAdd(copyId, quantity, preview);
    this.ui.openCartFlyout();

    this.lastAdded.set(copyId);
    clearTimeout(this.addedTimer);
    this.addedTimer = setTimeout(() => this.lastAdded.set(null), 1600);

    return this.post('/cart/items', { physical_copy_id: copyId, quantity }).pipe(
      tap(() => this.load()),
      catchError((error) => {
        this.load();
        return throwError(() => error);
      }),
    );
  }

  private optimisticAdd(copyId: string, quantity: number, preview?: Copy): void {
    const current = this.cart() ?? { items: [], total_cents: 0, has_unavailable_items: false };
    const existing = current.items.find((item) => item.physical_copy_id === copyId);

    let items: CartItem[];

    if (existing) {
      items = current.items.map((item) =>
        item.physical_copy_id === copyId
          ? { ...item, quantity: item.quantity + quantity, subtotal_cents: (item.unit_price_cents ?? 0) * (item.quantity + quantity) }
          : item,
      );
    } else if (preview) {
      items = [
        ...current.items,
        {
          physical_copy_id: copyId,
          quantity,
          available: true,
          movie_id: preview.movie_id,
          movie_title: preview.movie_title,
          poster_path: preview.poster_path,
          format: preview.format,
          condition: preview.condition,
          unit_price_cents: preview.price_cents,
          stock_available: preview.stock_available,
          subtotal_cents: preview.price_cents * quantity,
        },
      ];
    } else {
      this.ui.cartCount.update((count) => count + quantity);
      return;
    }

    this.store({
      ...current,
      items,
      total_cents: items.reduce((sum, item) => sum + item.subtotal_cents, 0),
    });
  }

  updateQuantity(copyId: string, quantity: number): Observable<unknown> {
    this.patchLocal(copyId, quantity);

    return this.put(`/cart/items/${copyId}`, { quantity }).pipe(
      tap(() => this.load()),
      catchError((error) => {
        this.load();
        return throwError(() => error);
      }),
    );
  }

  remove(copyId: string): Observable<unknown> {
    this.patchLocal(copyId, 0);

    return this.delete(`/cart/items/${copyId}`).pipe(
      tap(() => this.load()),
      catchError((error) => {
        this.load();
        return throwError(() => error);
      }),
    );
  }

  private patchLocal(copyId: string, quantity: number): void {
    const current = this.cart();
    if (!current) {
      return;
    }

    let items = current.items.map((item) =>
      item.physical_copy_id === copyId
        ? { ...item, quantity, subtotal_cents: (item.unit_price_cents ?? 0) * quantity }
        : item,
    );

    if (quantity <= 0) {
      items = items.filter((item) => item.physical_copy_id !== copyId);
    }

    this.store({
      ...current,
      items,
      total_cents: items.reduce((sum, item) => sum + item.subtotal_cents, 0),
    });
  }

  checkout(): Observable<CheckoutSession> {
    return this.post<CheckoutSession>('/checkout');
  }

  private store(cart: Cart): void {
    this.cart.set(cart);
    this.ui.cartCount.set(cart.items.reduce((sum, item) => sum + item.quantity, 0));
  }
}
