import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { DatePipe } from '@angular/common';
import { Router } from '@angular/router';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { NotificationService } from '../../../services/notification/notification.service';
import { AppNotification } from '../../../services/notification/notification.models';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';
import { IonIcon } from '@ionic/angular/standalone';

@Component({
  selector: 'app-notifications',
  imports: [DatePipe, EmptyStateComponent, TmdbImagePipe, IonIcon],
  templateUrl: 'notifications.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NotificationsPage {

  protected readonly service = inject(NotificationService);
  private readonly router = inject(Router);

  constructor() {
    this.service.load();
  }

  protected open(notification: AppNotification): void {
    if (!notification.read_at) {
      this.service.markRead(notification.uuid).subscribe({ error: () => undefined });
    }
    if (notification.action_url) {
      this.router.navigateByUrl(notification.action_url);
    }
  }

  protected markAll(): void {
    this.service.markAllRead().subscribe({ error: () => undefined });
  }
}
