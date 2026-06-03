import { ChangeDetectionStrategy, Component, HostListener, effect, inject } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { Params } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';
import { LogoComponent } from '../logo/logo.component';
import { AuthService } from '../../services/auth/auth.service';
import { UiStateService } from '../../services/ui/ui-state.service';

interface DrawerItem {
  label: string;
  icon: string;
  link: string;
  query?: Params;
  hint?: string;
  countKey?: 'cart' | 'wishlist' | 'watchLater';
  adminOnly?: boolean;
}

interface DrawerGroup {
  title: string;
  items: DrawerItem[];
}

@Component({
  selector: 'vb-nav-drawer',
  imports: [RouterLink, RouterLinkActive, IonIcon, LogoComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="drawer__overlay" [class.is-open]="ui.drawerOpen()" (click)="ui.closeDrawer()" aria-hidden="true"></div>

    <aside class="drawer" [class.is-open]="ui.drawerOpen()" role="dialog" aria-modal="true" aria-label="Menú de navegación">
      <div class="drawer__head">
        <a class="drawer__brand" routerLink="/home" (click)="ui.closeDrawer()">
          <vb-logo [size]="28"></vb-logo>
        </a>
        <button class="iconbtn" (click)="ui.closeDrawer()" aria-label="Cerrar menú">
          <ion-icon name="close" style="font-size:18px"></ion-icon>
        </button>
      </div>

      <nav class="drawer__nav">
        @for (group of groups; track group.title) {
          <div class="drawer__group">
            <div class="drawer__group-title">{{ group.title }}</div>
            @for (item of group.items; track item.label) {
              @if (!item.adminOnly || auth.isAdmin()) {
                <a class="drawer__item" [routerLink]="item.link" [queryParams]="item.query ?? null"
                  routerLinkActive="is-active" (click)="ui.closeDrawer()">
                  <ion-icon [name]="item.icon" style="font-size:18px"></ion-icon>
                  <span class="drawer__item-label">
                    {{ item.label }}
                    @if (item.hint) {
                      <span class="drawer__item-hint">{{ item.hint }}</span>
                    }
                  </span>
                  @if (countFor(item.countKey) > 0) {
                    <span class="drawer__item-count">{{ countFor(item.countKey) }}</span>
                  }
                </a>
              }
            }
          </div>
        }
      </nav>

      <div class="drawer__foot">
        <div class="mono tiny" style="color:var(--text-3)">VideoBay · Recogida en tienda</div>
      </div>
    </aside>
  `,
})
export class NavDrawerComponent {

  protected readonly ui = inject(UiStateService);
  protected readonly auth = inject(AuthService);

  protected readonly groups: DrawerGroup[] = [
    {
      title: 'EXPLORAR',
      items: [
        { label: 'Inicio', icon: 'home-outline', link: '/home' },
        { label: 'Catálogo de streaming', icon: 'play', link: '/stream', hint: 'Películas para ver online' },
        { label: 'Catálogo de tienda', icon: 'cube-outline', link: '/marketplace', hint: 'Comprar copias físicas' },
        { label: 'Buscar', icon: 'search-outline', link: '/search' },
      ],
    },
    {
      title: 'MI VIDEOBAY',
      items: [
        { label: 'Wishlist', icon: 'heart-outline', link: '/wishlist', query: { tab: 'wishlist' } },
        { label: 'Ver más tarde', icon: 'bookmark-outline', link: '/wishlist', query: { tab: 'later' } },
        { label: 'Carrito', icon: 'bag-outline', link: '/cart', countKey: 'cart' },
        { label: 'Mis pedidos', icon: 'cube-outline', link: '/orders' },
      ],
    },
    {
      title: 'TIENDA',
      items: [
        { label: 'Vender mi copia', icon: 'cloud-upload-outline', link: '/sell' },
        { label: 'Escanear código de barras', icon: 'barcode-outline', link: '/scanner' },
      ],
    },
    {
      title: 'CUENTA',
      items: [
        { label: 'Mi perfil', icon: 'person-outline', link: '/profile' },
        { label: 'Ajustes y accesibilidad', icon: 'settings-outline', link: '/settings' },
        { label: 'Panel de administración', icon: 'grid-outline', link: '/admin', adminOnly: true },
      ],
    },
  ];

  constructor() {
    effect(() => {
      document.body.style.overflow = this.ui.drawerOpen() ? 'hidden' : '';
    });
  }

  @HostListener('document:keydown.escape')
  protected onEscape(): void {
    this.ui.closeDrawer();
  }

  protected countFor(key?: DrawerItem['countKey']): number {
    switch (key) {
      case 'cart':
        return this.ui.cartCount();
      case 'wishlist':
        return this.ui.wishlistCount();
      case 'watchLater':
        return this.ui.watchLaterCount();
      default:
        return 0;
    }
  }
}
