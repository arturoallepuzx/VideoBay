import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import { Order, OrdersPage, OrderStatusCounts } from './order.models';

@Injectable({ providedIn: 'root' })
export class OrderService extends BaseApiService {

  listMine(page = 1): Observable<OrdersPage> {
    return this.get<OrdersPage>('/orders', { page });
  }

  markPickedUp(orderId: string): Observable<unknown> {
    return this.post(`/orders/${orderId}/pickup`);
  }

  listAll(status?: string, page = 1): Observable<OrdersPage> {
    return this.get<OrdersPage>('/admin/orders', { status, page });
  }

  statusCounts(): Observable<OrderStatusCounts> {
    return this.get<OrderStatusCounts>('/admin/orders/counts');
  }

  getForPickup(pickupCode: string): Observable<Order> {
    return this.get<Order>(`/admin/orders/pickup/${pickupCode}`);
  }

  markPickedUpByCode(pickupCode: string): Observable<Order> {
    return this.post<Order>(`/admin/orders/pickup/${pickupCode}`);
  }
}
