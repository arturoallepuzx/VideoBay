import { Routes } from '@angular/router';
import { authGuard } from './providers/auth.guard';
import { adminGuard } from './providers/admin.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./pages/auth/login/login.page').then((m) => m.LoginPage),
  },
  {
    path: 'home',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/core/home/home.page').then((m) => m.HomePage),
  },
  {
    path: 'stream',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/catalog/stream/stream.page').then((m) => m.StreamPage),
  },
  {
    path: 'search',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/catalog/search/search.page').then((m) => m.SearchPage),
  },
  {
    path: 'movie/:id',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/catalog/movie/movie.page').then((m) => m.MoviePage),
  },
  {
    path: 'person/:id',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/catalog/person/person.page').then((m) => m.PersonPage),
  },
  {
    path: 'marketplace',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/shop/marketplace/marketplace.page').then((m) => m.MarketplacePage),
  },
  {
    path: 'copy/:id',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/shop/copy/copy.page').then((m) => m.CopyPage),
  },
  {
    path: 'cart',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/shop/cart/cart.page').then((m) => m.CartPage),
  },
  {
    path: 'checkout',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/shop/checkout/checkout.page').then((m) => m.CheckoutPage),
  },
  {
    path: 'success',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/shop/success/success.page').then((m) => m.SuccessPage),
  },
  {
    path: 'orders',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/shop/orders/orders.page').then((m) => m.OrdersPage),
  },
  {
    path: 'wishlist',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/me/wishlist/wishlist.page').then((m) => m.WishlistPage),
  },
  {
    path: 'notifications',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/me/notifications/notifications.page').then((m) => m.NotificationsPage),
  },
  {
    path: 'profile',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/me/profile/profile.page').then((m) => m.ProfilePage),
  },
  {
    path: 'settings',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/me/settings/settings.page').then((m) => m.SettingsPage),
  },
  {
    path: 'sell',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/shop/sell/sell.page').then((m) => m.SellPage),
  },
  {
    path: 'player/:id',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/app/player/player.page').then((m) => m.PlayerPage),
  },
  {
    path: 'scanner',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/app/scanner/scanner.page').then((m) => m.ScannerPage),
  },
  {
    path: 'admin',
    canActivate: [adminGuard],
    loadComponent: () => import('./pages/admin/dashboard/dashboard.page').then((m) => m.AdminDashboardPage),
  },
  {
    path: 'admin/orders',
    canActivate: [adminGuard],
    loadComponent: () => import('./pages/admin/orders/admin-orders.page').then((m) => m.AdminOrdersPage),
  },
  {
    path: 'admin/pickup',
    canActivate: [adminGuard],
    loadComponent: () => import('./pages/admin/pickup/pickup.page').then((m) => m.AdminPickupPage),
  },
  {
    path: 'admin/inventory',
    canActivate: [adminGuard],
    loadComponent: () => import('./pages/admin/inventory/admin-inventory.page').then((m) => m.AdminInventoryPage),
  },
  {
    path: 'admin/pricing',
    canActivate: [adminGuard],
    loadComponent: () => import('./pages/admin/pricing/admin-pricing.page').then((m) => m.AdminPricingPage),
  },
  {
    path: 'admin/streaming',
    canActivate: [adminGuard],
    loadComponent: () => import('./pages/admin/streaming/admin-streaming.page').then((m) => m.AdminStreamingPage),
  },
  {
    path: 'admin/users',
    canActivate: [adminGuard],
    loadComponent: () => import('./pages/admin/users/admin-users.page').then((m) => m.AdminUsersPage),
  },
  {
    path: 'admin/moderation',
    canActivate: [adminGuard],
    loadComponent: () => import('./pages/admin/moderation/admin-moderation.page').then((m) => m.AdminModerationPage),
  },
  {
    path: '',
    redirectTo: 'home',
    pathMatch: 'full',
  },
  {
    path: '**',
    redirectTo: 'home',
  },
];
