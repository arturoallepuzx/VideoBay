import { Injectable, computed, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import { AppNotification } from './notification.models';

@Injectable({ providedIn: 'root' })
export class NotificationService extends BaseApiService {

  readonly notifications = signal<AppNotification[]>([]);
  readonly unread = computed(() => this.notifications().filter((notification) => !notification.read_at).length);

  load(): void {
    this.get<{ items: AppNotification[] }>('/notifications').subscribe({
      next: (response) => this.notifications.set(response.items),
      error: () => undefined,
    });
  }

  markRead(uuid: string): Observable<unknown> {
    return this.post(`/notifications/${uuid}/read`).pipe(tap(() => this.applyRead(uuid)));
  }

  markAllRead(): Observable<unknown> {
    return this.post('/notifications/read-all').pipe(tap(() => this.applyReadAll()));
  }

  private applyRead(uuid: string): void {
    const now = new Date().toISOString();
    this.notifications.update((list) =>
      list.map((notification) => (notification.uuid === uuid ? { ...notification, read_at: now } : notification)),
    );
  }

  private applyReadAll(): void {
    const now = new Date().toISOString();
    this.notifications.update((list) =>
      list.map((notification) => ({ ...notification, read_at: notification.read_at ?? now })),
    );
  }
}
