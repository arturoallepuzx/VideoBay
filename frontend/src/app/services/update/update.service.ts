import { Injectable, inject } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { SwUpdate, VersionReadyEvent } from '@angular/service-worker';
import { filter } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class UpdateService {

  private readonly updates = inject(SwUpdate);
  private readonly router = inject(Router);

  private pending = false;

  init(): void {
    if (!this.updates.isEnabled) {
      return;
    }

    this.updates.versionUpdates
      .pipe(filter((event): event is VersionReadyEvent => event.type === 'VERSION_READY'))
      .subscribe(() => {
        if (this.router.url.startsWith('/player')) {
          this.pending = true;
        } else {
          document.location.reload();
        }
      });

    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe((event) => {
        if (this.pending && !event.urlAfterRedirects.startsWith('/player')) {
          document.location.reload();
        }
      });
  }
}
