import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';
import { AdminSidebarComponent } from '../../../components/admin-sidebar/admin-sidebar.component';
import { OrderService } from '../../../services/order/order.service';
import { Order } from '../../../services/order/order.models';
import { MoneyPipe } from '../../../pipes/money.pipe';

interface StatusTab {
  value: string;
  label: string;
}

@Component({
  selector: 'app-admin-orders',
  imports: [DatePipe, FormsModule, RouterLink, IonIcon, AdminSidebarComponent, MoneyPipe],
  templateUrl: 'admin-orders.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminOrdersPage {

  protected readonly tabs: StatusTab[] = [
    { value: '', label: 'Todos' },
    { value: 'pending_payment', label: 'Pendiente pago' },
    { value: 'paid', label: 'Pagado' },
    { value: 'ready_for_pickup', label: 'Listo recoger' },
    { value: 'picked_up', label: 'Recogido' },
    { value: 'cancelled', label: 'Cancelado' },
  ];

  protected readonly activeStatus = signal('');
  protected readonly orders = signal<Order[]>([]);
  protected readonly page = signal(1);
  protected readonly totalPages = signal(1);
  protected readonly total = signal(0);
  protected readonly loading = signal(false);
  protected readonly search = signal('');
  protected readonly skeletons = Array.from({ length: 6 });

  protected readonly filtered = computed(() => {
    const term = this.search().trim().toLowerCase();
    if (!term) {
      return this.orders();
    }
    return this.orders().filter((order) => (order.pickup_code ?? '').toLowerCase().includes(term));
  });

  private readonly orderService = inject(OrderService);

  constructor() {
    this.load(true);
  }

  protected selectStatus(status: string): void {
    if (status === this.activeStatus()) {
      return;
    }
    this.activeStatus.set(status);
    this.load(true);
  }

  protected loadMore(): void {
    if (this.page() < this.totalPages()) {
      this.page.update((value) => value + 1);
      this.load(false);
    }
  }

  protected statusLabel(status: string): string {
    const labels: Record<string, string> = {
      pending_payment: 'Pendiente de pago',
      paid: 'Pagado',
      ready_for_pickup: 'Lista',
      picked_up: 'Recogido',
      cancelled: 'Cancelado',
      refunded: 'Reembolsado',
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

  protected units(order: Order): number {
    return order.items.reduce((sum, item) => sum + item.quantity, 0);
  }

  private load(reset: boolean): void {
    if (reset) {
      this.page.set(1);
    }
    this.loading.set(true);

    this.orderService.listAll(this.activeStatus() || undefined, this.page()).subscribe({
      next: (result) => {
        this.orders.set(reset ? result.orders : [...this.orders(), ...result.orders]);
        this.totalPages.set(result.total_pages);
        this.total.set(result.total);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
}
