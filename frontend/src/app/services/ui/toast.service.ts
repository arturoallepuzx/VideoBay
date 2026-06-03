import { Injectable, signal } from '@angular/core';

export interface Toast {
  id: number;
  text: string;
}

@Injectable({ providedIn: 'root' })
export class ToastService {

  readonly toasts = signal<Toast[]>([]);

  show(text: string): void {
    const id = Date.now() + Math.random();
    this.toasts.update((list) => [...list, { id, text }]);
    setTimeout(() => this.toasts.update((list) => list.filter((toast) => toast.id !== id)), 2400);
  }
}
