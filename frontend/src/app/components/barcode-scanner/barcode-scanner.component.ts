import { ChangeDetectionStrategy, Component, ElementRef, OnDestroy, output, signal, viewChild } from '@angular/core';
import { IonIcon } from '@ionic/angular/standalone';
import { BrowserMultiFormatReader, IScannerControls } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

@Component({
  selector: 'vb-barcode-scanner',
  imports: [IonIcon],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (scanning()) {
      <div class="scanner scanner--inline">
        <video #video class="scanner__cam" style="width:100%;height:100%;object-fit:cover"></video>
        <div class="scanner__viewfinder">
          <div class="scanner__corners"></div>
          <div class="scanner__line"></div>
        </div>
        <div class="scanner__hint">Buscando código…</div>
      </div>
      <button type="button" class="btn" style="margin-top:10px" (click)="stop()">Detener escáner</button>
    } @else {
      <button type="button" class="btn" (click)="start()">
        <ion-icon name="barcode-outline"></ion-icon> Escanear código de barras
      </button>
    }
    @if (error()) {
      <div class="tiny" style="color:var(--vb-red);margin-top:8px">{{ error() }}</div>
    }
  `,
})
export class BarcodeScannerComponent implements OnDestroy {

  readonly scanned = output<string>();

  protected readonly scanning = signal(false);
  protected readonly error = signal<string | null>(null);

  private readonly videoRef = viewChild<ElementRef<HTMLVideoElement>>('video');
  private reader?: BrowserMultiFormatReader;
  private controls?: IScannerControls;

  async start(): Promise<void> {
    this.error.set(null);
    this.scanning.set(true);

    const video = await this.waitForVideo();
    if (!video) {
      this.scanning.set(false);
      this.error.set('No se pudo iniciar la cámara.');
      return;
    }

    try {
      const hints = new Map<DecodeHintType, unknown>();
      hints.set(DecodeHintType.POSSIBLE_FORMATS, [
        BarcodeFormat.EAN_13,
        BarcodeFormat.EAN_8,
        BarcodeFormat.UPC_A,
        BarcodeFormat.UPC_E,
      ]);
      hints.set(DecodeHintType.TRY_HARDER, true);

      this.reader = new BrowserMultiFormatReader(hints, { delayBetweenScanAttempts: 100 });

      const constraints: MediaStreamConstraints = {
        video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
      };

      this.controls = await this.reader.decodeFromConstraints(constraints, video, (decoded) => {
        if (decoded) {
          const text = decoded.getText();
          this.stop();
          this.scanned.emit(text);
        }
      });
    } catch {
      this.scanning.set(false);
      this.error.set('No se pudo acceder a la cámara.');
    }
  }

  stop(): void {
    this.controls?.stop();
    this.controls = undefined;
    this.scanning.set(false);
  }

  ngOnDestroy(): void {
    this.stop();
  }

  private waitForVideo(): Promise<HTMLVideoElement | null> {
    return new Promise((resolve) => {
      let tries = 0;
      const tick = (): void => {
        const video = this.videoRef()?.nativeElement;
        if (video) {
          resolve(video);
        } else if (tries++ > 12) {
          resolve(null);
        } else {
          requestAnimationFrame(tick);
        }
      };
      requestAnimationFrame(tick);
    });
  }
}
