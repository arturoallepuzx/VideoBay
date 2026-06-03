import { ChangeDetectionStrategy, Component, ElementRef, OnDestroy, OnInit, inject, signal, viewChild } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { IonIcon } from '@ionic/angular/standalone';
import { BrowserMultiFormatReader, IScannerControls } from '@zxing/browser';
import { AdminSidebarComponent } from '../../../components/admin-sidebar/admin-sidebar.component';
import { OrderService } from '../../../services/order/order.service';
import { Order } from '../../../services/order/order.models';
import { ToastService } from '../../../services/ui/toast.service';
import { MoneyPipe } from '../../../pipes/money.pipe';

@Component({
  selector: 'app-admin-pickup',
  imports: [DatePipe, FormsModule, IonIcon, AdminSidebarComponent, MoneyPipe],
  templateUrl: 'pickup.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminPickupPage implements OnInit, OnDestroy {

  protected readonly scanning = signal(false);
  protected readonly loading = signal(false);
  protected readonly marking = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly order = signal<Order | null>(null);
  protected manualCode = '';

  private readonly videoRef = viewChild<ElementRef<HTMLVideoElement>>('video');
  private readonly orders = inject(OrderService);
  private readonly toast = inject(ToastService);
  private readonly route = inject(ActivatedRoute);

  private reader?: BrowserMultiFormatReader;
  private controls?: IScannerControls;

  ngOnInit(): void {
    const code = this.route.snapshot.queryParamMap.get('code');
    if (code) {
      this.lookup(code);
    }
  }

  async startCamera(): Promise<void> {
    const video = this.videoRef()?.nativeElement;
    if (!video) {
      return;
    }

    this.error.set(null);

    try {
      this.reader = new BrowserMultiFormatReader();
      this.scanning.set(true);
      this.controls = await this.reader.decodeFromVideoDevice(undefined, video, (decoded) => {
        if (decoded) {
          this.stopCamera();
          this.lookup(decoded.getText());
        }
      });
    } catch {
      this.scanning.set(false);
      this.error.set('No se pudo acceder a la cámara. Usa el código manual.');
    }
  }

  stopCamera(): void {
    this.controls?.stop();
    this.controls = undefined;
    this.scanning.set(false);
  }

  submitManual(): void {
    const code = this.manualCode.trim();
    if (code) {
      this.lookup(code);
    }
  }

  lookup(raw: string): void {
    const code = this.extractCode(raw);
    this.error.set(null);
    this.order.set(null);
    this.loading.set(true);

    this.orders.getForPickup(code).subscribe({
      next: (order) => {
        this.order.set(order);
        this.loading.set(false);
      },
      error: (err: HttpErrorResponse) => {
        this.loading.set(false);
        this.error.set(this.messageFor(err));
      },
    });
  }

  confirm(): void {
    const order = this.order();
    if (!order || !order.pickup_code) {
      return;
    }

    this.marking.set(true);

    this.orders.markPickedUpByCode(order.pickup_code).subscribe({
      next: (updated) => {
        this.order.set({ ...order, status: updated.status, picked_up_at: updated.picked_up_at });
        this.marking.set(false);
        this.toast.show('Pedido marcado como recogido');
      },
      error: (err: HttpErrorResponse) => {
        this.marking.set(false);
        this.toast.show(this.messageFor(err));
      },
    });
  }

  protected units(order: Order): number {
    return order.items.reduce((sum, item) => sum + item.quantity, 0);
  }

  ngOnDestroy(): void {
    this.stopCamera();
  }

  private extractCode(raw: string): string {
    const trimmed = raw.trim();
    const parts = trimmed.split('/').filter((part) => part.length > 0);
    return parts.length ? parts[parts.length - 1] : trimmed;
  }

  private messageFor(err: HttpErrorResponse): string {
    return (err.error?.error as string) ?? 'No se pudo encontrar el pedido';
  }
}
