import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { DatePipe, SlicePipe } from '@angular/common';
import { Router } from '@angular/router';
import { OrderService } from '../../../services/order/order.service';
import { Order } from '../../../services/order/order.models';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { QrCodeComponent } from '../../../components/qr-code/qr-code.component';
import { MoneyPipe } from '../../../pipes/money.pipe';

@Component({
  selector: 'app-orders',
  imports: [DatePipe, SlicePipe, EmptyStateComponent, QrCodeComponent, MoneyPipe],
  templateUrl: 'orders.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class OrdersPage {

  protected readonly orders = signal<Order[]>([]);
  protected readonly loaded = signal(false);
  protected readonly skeletons = Array.from({ length: 3 });

  private readonly orderService = inject(OrderService);
  private readonly router = inject(Router);

  constructor() {
    this.orderService.listMine().subscribe({
      next: (page) => {
        this.orders.set(page.orders);
        this.loaded.set(true);
      },
      error: () => this.loaded.set(true),
    });
  }

  protected statusLabel(status: string): string {
    const labels: Record<string, string> = {
      pending_payment: 'Pendiente de pago',
      paid: 'Pagado',
      ready_for_pickup: 'Lista para recoger',
      picked_up: 'Recogido',
      cancelled: 'Cancelado',
      expired: 'Caducado',
    };

    return labels[status] ?? status;
  }

  protected statusClass(status: string): string {
    if (status === 'ready_for_pickup') {
      return 'status-pill--ready';
    }
    if (status === 'picked_up') {
      return 'status-pill--picked';
    }
    if (status === 'paid') {
      return 'status-pill--paid';
    }

    return '';
  }

  protected goMarketplace(): void {
    this.router.navigate(['/marketplace']);
  }
}
