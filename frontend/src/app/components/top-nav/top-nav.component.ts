import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';
import { LogoComponent } from '../logo/logo.component';
import { ThemeService } from '../../services/theme/theme.service';
import { AuthService } from '../../services/auth/auth.service';
import { UiStateService } from '../../services/ui/ui-state.service';
import { NotificationService } from '../../services/notification/notification.service';

@Component({
  selector: 'vb-top-nav',
  imports: [RouterLink, RouterLinkActive, IonIcon, LogoComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <nav class="topnav" aria-label="Principal">
      <button class="topnav__burger iconbtn" aria-label="Abrir menú" (click)="ui.openDrawer()">
        <ion-icon name="menu-outline" style="font-size:20px"></ion-icon>
      </button>

      <a class="topnav__brand" routerLink="/home" aria-label="VideoBay, ir al inicio">
        <vb-logo [size]="28"></vb-logo>
      </a>

      <div class="topnav__links">
        <a class="topnav__link" routerLink="/stream" routerLinkActive="is-active">
          <span class="topnav__link-dot"></span> Streaming
        </a>
        <a class="topnav__link" routerLink="/marketplace" routerLinkActive="is-active">
          <span class="topnav__link-dot topnav__link-dot--store"></span> Tienda física
        </a>
        <a class="topnav__link" routerLink="/sell" routerLinkActive="is-active">Vender</a>
      </div>

      <span style="flex:1"></span>

      <div class="topnav__actions">
        <a class="iconbtn topnav__search-btn" routerLink="/search" routerLinkActive="is-active" aria-label="Buscar">
          <ion-icon name="search-outline" style="font-size:18px"></ion-icon>
        </a>
        <button class="iconbtn" aria-label="Cambiar tema" (click)="theme.toggleTheme()">
          <ion-icon [name]="theme.resolvedTheme() === 'light' ? 'moon-outline' : 'sunny-outline'"
            style="font-size:18px"></ion-icon>
        </button>

        @if (auth.isAuthenticated()) {
          <button class="iconbtn iconbtn--has-badge" aria-label="Notificaciones" (click)="ui.toggleNotifFlyout()">
            <ion-icon name="notifications-outline" style="font-size:18px"></ion-icon>
            @if (notifications.unread() > 0) {
              <span class="badge">{{ notifications.unread() }}</span>
            }
          </button>
        }

        <button class="iconbtn iconbtn--has-badge" aria-label="Carrito" (click)="ui.toggleCartFlyout()">
          <ion-icon name="bag-outline" style="font-size:18px"></ion-icon>
          @if (ui.cartCount() > 0) {
            <span class="badge">{{ ui.cartCount() }}</span>
          }
        </button>

        @if (auth.isAuthenticated()) {
          <a class="avatar" routerLink="/profile" aria-label="Mi cuenta">
            @if (auth.user()?.avatar_url; as photo) {
              <img [src]="photo" alt="" />
            } @else {
              {{ initial() }}
            }
          </a>
        } @else {
          <a class="btn btn--primary" routerLink="/login">Entrar</a>
        }
      </div>
    </nav>
  `,
})
export class TopNavComponent {

  protected readonly theme = inject(ThemeService);
  protected readonly auth = inject(AuthService);
  protected readonly ui = inject(UiStateService);
  protected readonly notifications = inject(NotificationService);

  protected initial(): string {
    return (this.auth.user()?.name ?? '?').charAt(0).toUpperCase();
  }
}
