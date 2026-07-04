import { Component, computed, effect, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { filter } from 'rxjs';
import { CartService } from './services/cart/cart.service';
import { WishlistService } from './services/wishlist/wishlist.service';
import { NotificationService } from './services/notification/notification.service';
import { TopNavComponent } from './components/top-nav/top-nav.component';
import { NavDrawerComponent } from './components/nav-drawer/nav-drawer.component';
import { MobileTabsComponent } from './components/mobile-tabs/mobile-tabs.component';
import { ToastStackComponent } from './components/toast-stack/toast-stack.component';
import { CartFlyoutComponent } from './components/cart-flyout/cart-flyout.component';
import { NotificationFlyoutComponent } from './components/notification-flyout/notification-flyout.component';
import { LogoComponent } from './components/logo/logo.component';
import { ThemeService } from './services/theme/theme.service';
import { AuthService } from './services/auth/auth.service';
import { LoadingService } from './services/loading/loading.service';
import { UpdateService } from './services/update/update.service';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  imports: [
    RouterOutlet,
    TopNavComponent,
    NavDrawerComponent,
    MobileTabsComponent,
    ToastStackComponent,
    CartFlyoutComponent,
    NotificationFlyoutComponent,
    LogoComponent,
  ],
})
export class AppComponent {

  protected readonly ready = signal(false);

  private readonly auth = inject(AuthService);
  private readonly cart = inject(CartService);
  private readonly wishlist = inject(WishlistService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);
  private readonly theme = inject(ThemeService);
  private readonly updates = inject(UpdateService);
  protected readonly loading = inject(LoadingService);

  private readonly url = signal(this.router.url);
  private readonly chromelessPrefixes = ['/login', '/player'];

  protected readonly showChrome = computed(
    () => this.ready() && !this.chromelessPrefixes.some((prefix) => this.url().startsWith(prefix)),
  );

  constructor() {
    this.theme.init();
    this.updates.init();
    this.auth.ensureSession().subscribe();

    effect(() => {
      if (this.auth.isAuthenticated()) {
        this.cart.load();
        this.wishlist.loadCounts();
        this.notifications.load();
      }
    });

    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe((event) => {
        this.url.set(event.urlAfterRedirects);
        this.ready.set(true);
      });
  }
}
