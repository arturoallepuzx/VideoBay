import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';
import { AdminSidebarComponent } from '../../../components/admin-sidebar/admin-sidebar.component';
import { OrderService } from '../../../services/order/order.service';
import { Order, OrderStatusCounts } from '../../../services/order/order.models';
import { MoneyPipe } from '../../../pipes/money.pipe';

@Component({
  selector: 'app-admin-dashboard',
  imports: [DatePipe, RouterLink, IonIcon, AdminSidebarComponent, MoneyPipe],
  templateUrl: 'dashboard.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminDashboardPage {

  protected readonly counts = signal<OrderStatusCounts | null>(null);
  protected readonly ready = signal<Order[]>([]);
  protected readonly loaded = signal(false);
  protected readonly skeletons = Array.from({ length: 5 });

  protected readonly total = computed(() => {
    const counts = this.counts();
    return counts ? Object.values(counts).reduce((sum, value) => sum + value, 0) : 0;
  });

  private readonly orders = inject(OrderService);

  constructor() {
    this.orders.statusCounts().subscribe({ next: (counts) => this.counts.set(counts) });
    this.orders.listAll('ready_for_pickup').subscribe({
      next: (page) => {
        this.ready.set(page.orders);
        this.loaded.set(true);
      },
      error: () => this.loaded.set(true),
    });
  }

  protected units(order: Order): number {
    return order.items.reduce((sum, item) => sum + item.quantity, 0);
  }
}
