import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { DatePipe } from '@angular/common';
import { NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs';
import { IonIcon } from '@ionic/angular/standalone';
import { NotificationService } from '../../services/notification/notification.service';
import { AppNotification } from '../../services/notification/notification.models';
import { UiStateService } from '../../services/ui/ui-state.service';
import { TmdbImagePipe } from '../../pipes/tmdb-image.pipe';

@Component({
  selector: 'vb-notification-flyout',
  imports: [DatePipe, IonIcon, TmdbImagePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (ui.notifFlyoutOpen()) {
      <div class="cartfly__overlay" (click)="ui.closeNotifFlyout()"></div>
      <aside class="cartfly" role="dialog" aria-label="Notificaciones">
        <div class="cartfly__head">
          <span class="cartfly__title"><ion-icon name="notifications-outline"></ion-icon> Notificaciones</span>
          <div style="display:flex;gap:4px;align-items:center">
            @if (service.unread() > 0) {
              <button class="btn btn--ghost" style="padding:5px 9px;font-size:12px" (click)="markAll()">Marcar leídas</button>
            }
            <button class="iconbtn" (click)="ui.closeNotifFlyout()" aria-label="Cerrar">
              <ion-icon name="close"></ion-icon>
            </button>
          </div>
        </div>

        @if (service.notifications().length) {
          <div class="cartfly__items">
            @for (notification of service.notifications(); track notification.uuid) {
              <button class="notiffly__item" (click)="open(notification)">
                <span class="notiffly__thumb" [class.notiffly__thumb--round]="!!notification.metadata?.actor">
                  @if (notification.metadata?.actor; as actor) {
                    @if (actor.avatar_url) {
                      <img [src]="actor.avatar_url" alt="" />
                    } @else {
                      {{ (actor.name || '?').charAt(0) }}
                    }
                  } @else if (notification.metadata?.movie?.poster_path; as poster) {
                    <img [src]="poster | tmdbImage: 'poster'" alt="" />
                  } @else {
                    <ion-icon name="notifications-outline"></ion-icon>
                  }
                </span>
                <span class="notiffly__body">
                  <span class="notiffly__title">{{ notification.title }}</span>
                  @if (notification.body) {
                    <span class="notiffly__sub">{{ notification.body }}</span>
                  }
                  <span class="notiffly__time">{{ notification.created_at | date: 'dd MMM · HH:mm' }}</span>
                </span>
                @if (!notification.read_at) {
                  <span class="notiffly__unread"></span>
                }
              </button>
            }
          </div>
          <div class="cartfly__actions">
            <button class="btn" style="flex:1;justify-content:center" (click)="go('/notifications')">Ver todas</button>
          </div>
        } @else {
          <div class="cartfly__empty">
            <ion-icon name="notifications-outline"></ion-icon>
            <p>No tienes notificaciones.</p>
          </div>
        }
      </aside>
    }
  `,
})
export class NotificationFlyoutComponent {

  protected readonly ui = inject(UiStateService);
  protected readonly service = inject(NotificationService);

  private readonly router = inject(Router);

  constructor() {
    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe(() => this.ui.closeNotifFlyout());
  }

  protected open(notification: AppNotification): void {
    if (!notification.read_at) {
      this.service.markRead(notification.uuid).subscribe({ error: () => undefined });
    }
    if (notification.action_url) {
      this.ui.closeNotifFlyout();
      this.router.navigateByUrl(notification.action_url);
    }
  }

  protected markAll(): void {
    this.service.markAllRead().subscribe({ error: () => undefined });
  }

  protected go(path: string): void {
    this.ui.closeNotifFlyout();
    this.router.navigate([path]);
  }
}
