import { Injectable, computed, signal } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class LoadingService {

  private readonly count = signal(0);

  readonly active = computed(() => this.count() > 0);

  start(): void {
    this.count.update((value) => value + 1);
  }

  stop(): void {
    this.count.update((value) => Math.max(0, value - 1));
  }
}
