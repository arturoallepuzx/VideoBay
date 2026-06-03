import { ChangeDetectionStrategy, Component, ElementRef, OnDestroy, computed, inject, signal, viewChild } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { BrowserMultiFormatReader, IScannerControls } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';
import { CatalogService } from '../../../services/catalog/catalog.service';
import { BarcodeResolveResult } from '../../../services/catalog/catalog.models';
import { InventoryService } from '../../../services/inventory/inventory.service';
import { Copy } from '../../../services/inventory/inventory.models';
import { ToastService } from '../../../services/ui/toast.service';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';
import { MoneyPipe } from '../../../pipes/money.pipe';

@Component({
  selector: 'app-scanner',
  imports: [FormsModule, IonIcon, TmdbImagePipe, MoneyPipe],
  templateUrl: 'scanner.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ScannerPage implements OnDestroy {

  protected readonly scanning = signal(false);
  protected readonly resolving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly result = signal<BarcodeResolveResult | null>(null);
  protected readonly copies = signal<Copy[] | null>(null);
  protected readonly loadingCopies = signal(false);
  protected readonly scannedCode = signal('');
  protected readonly posterPath = computed(() => this.result()?.movie?.poster_path ?? null);
  protected manualCode = '';

  private readonly videoRef = viewChild<ElementRef<HTMLVideoElement>>('video');
  private readonly catalog = inject(CatalogService);
  private readonly inventory = inject(InventoryService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  private reader?: BrowserMultiFormatReader;
  private controls?: IScannerControls;

  async startCamera(): Promise<void> {
    const video = this.videoRef()?.nativeElement;
    if (!video) {
      return;
    }

    this.error.set(null);

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
      this.scanning.set(true);

      const constraints: MediaStreamConstraints = {
        video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
      };

      this.controls = await this.reader.decodeFromConstraints(constraints, video, (decoded) => {
        if (decoded) {
          this.stopCamera();
          this.resolve(decoded.getText());
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
      this.resolve(code);
    }
  }

  resolve(barcode: string): void {
    this.scannedCode.set(barcode);
    this.error.set(null);
    this.result.set(null);
    this.copies.set(null);
    this.resolving.set(true);

    this.catalog.resolveBarcode(barcode).subscribe({
      next: (response) => {
        this.resolving.set(false);
        this.result.set(response);
      },
      error: () => {
        this.resolving.set(false);
        this.toast.show('No se pudo resolver el código');
      },
    });
  }

  scanAnother(): void {
    this.result.set(null);
    this.copies.set(null);
    this.error.set(null);
    this.manualCode = '';
  }

  loadStock(movieId: string): void {
    if (this.copies() !== null || this.loadingCopies()) {
      return;
    }

    this.loadingCopies.set(true);

    this.inventory.listCopies({ movie_id: movieId }).subscribe({
      next: (page) => {
        this.copies.set(page.copies);
        this.loadingCopies.set(false);
      },
      error: () => {
        this.copies.set([]);
        this.loadingCopies.set(false);
      },
    });
  }

  sell(): void {
    const resolved = this.result();
    if (!resolved) {
      return;
    }

    this.router.navigate(['/sell'], {
      queryParams: {
        barcode: resolved.barcode || this.scannedCode(),
        movie: resolved.movie?.uuid ?? null,
        title: resolved.movie?.title ?? resolved.external_title ?? null,
      },
    });
  }

  openMovie(id: string | number | null): void {
    if (id) {
      this.router.navigate(['/movie', id]);
    }
  }

  openCopy(copyId: string): void {
    this.router.navigate(['/copy', copyId]);
  }

  search(term: string): void {
    this.router.navigate(['/search'], { queryParams: { q: term } });
  }

  ngOnDestroy(): void {
    this.stopCamera();
  }
}
