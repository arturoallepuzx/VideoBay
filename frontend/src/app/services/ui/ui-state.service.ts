import { Injectable, signal } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class UiStateService {

  readonly drawerOpen = signal(false);
  readonly cartCount = signal(0);
  readonly wishlistCount = signal(0);
  readonly watchLaterCount = signal(0);
  readonly cartFlyoutOpen = signal(false);
  readonly notifFlyoutOpen = signal(false);

  openDrawer(): void {
    this.drawerOpen.set(true);
  }

  closeDrawer(): void {
    this.drawerOpen.set(false);
  }

  openCartFlyout(): void {
    this.notifFlyoutOpen.set(false);
    this.cartFlyoutOpen.set(true);
  }

  closeCartFlyout(): void {
    this.cartFlyoutOpen.set(false);
  }

  toggleCartFlyout(): void {
    this.notifFlyoutOpen.set(false);
    this.cartFlyoutOpen.update((open) => !open);
  }

  closeNotifFlyout(): void {
    this.notifFlyoutOpen.set(false);
  }

  toggleNotifFlyout(): void {
    this.cartFlyoutOpen.set(false);
    this.notifFlyoutOpen.update((open) => !open);
  }
}
