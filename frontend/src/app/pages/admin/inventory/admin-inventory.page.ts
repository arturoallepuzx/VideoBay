import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { IonIcon } from '@ionic/angular/standalone';
import { AdminSidebarComponent } from '../../../components/admin-sidebar/admin-sidebar.component';
import { MoviePickerComponent } from '../../../components/movie-picker/movie-picker.component';
import { BarcodeScannerComponent } from '../../../components/barcode-scanner/barcode-scanner.component';
import { InventoryService } from '../../../services/inventory/inventory.service';
import { Copy } from '../../../services/inventory/inventory.models';
import { CatalogService } from '../../../services/catalog/catalog.service';
import { MovieSearchResult } from '../../../services/catalog/catalog.models';
import { ToastService } from '../../../services/ui/toast.service';
import { MoneyPipe } from '../../../pipes/money.pipe';

interface CreateForm {
  movie_id: string;
  sku: string;
  format: string;
  condition: string;
  barcode: string;
  region: string;
  price_euros: number | null;
  stock_available: number | null;
  cover_photo_url: string;
}

interface EditForm {
  condition: string;
  barcode: string;
  price_euros: number | null;
  active: boolean;
}

@Component({
  selector: 'app-admin-inventory',
  imports: [FormsModule, IonIcon, AdminSidebarComponent, MoviePickerComponent, BarcodeScannerComponent, MoneyPipe],
  templateUrl: 'admin-inventory.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminInventoryPage {

  protected readonly formats = ['DVD', 'BLURAY', 'UHD_4K', 'VHS'];
  protected readonly conditions = [
    { value: 'new', label: 'Nuevo' },
    { value: 'like_new', label: 'Como nuevo' },
    { value: 'good', label: 'Bueno' },
    { value: 'fair', label: 'Aceptable' },
  ];

  protected readonly copies = signal<Copy[]>([]);
  protected readonly loading = signal(false);
  protected readonly skeletons = Array.from({ length: 6 });
  protected readonly page = signal(1);
  protected readonly totalPages = signal(1);
  protected readonly total = signal(0);
  protected readonly saving = signal(false);
  protected readonly showCreate = signal(false);
  protected readonly editingId = signal<string | null>(null);
  protected readonly resolvingMovie = signal(false);
  protected readonly pickedTitle = signal<string | null>(null);

  protected createForm: CreateForm = this.emptyCreate();
  protected editForm: EditForm = { condition: 'good', barcode: '', price_euros: null, active: true };

  private readonly inventory = inject(InventoryService);
  private readonly catalog = inject(CatalogService);
  private readonly toast = inject(ToastService);

  constructor() {
    this.load(true);
  }

  protected formatLabel(value: string): string {
    return { DVD: 'DVD', BLURAY: 'Blu-ray', UHD_4K: '4K UHD', VHS: 'VHS' }[value] ?? value;
  }

  protected conditionLabel(value: string): string {
    return this.conditions.find((item) => item.value === value)?.label ?? value;
  }

  protected toggleCreate(): void {
    this.showCreate.update((open) => !open);
    if (this.showCreate()) {
      this.editingId.set(null);
      this.createForm = this.emptyCreate();
      this.pickedTitle.set(null);
    }
  }

  protected onBarcodeScanned(code: string): void {
    this.createForm.barcode = code;
    this.resolvingMovie.set(true);
    this.pickedTitle.set(null);

    this.catalog.resolveBarcode(code).subscribe({
      next: (result) => {
        this.resolvingMovie.set(false);
        if (result.movie) {
          this.createForm.movie_id = result.movie.uuid;
          this.pickedTitle.set(result.movie.title);
          this.toast.show('Película detectada: ' + result.movie.title);
        } else {
          this.toast.show('Código leído, pero la película no está en el catálogo. Selecciónala a mano.');
        }
      },
      error: () => {
        this.resolvingMovie.set(false);
        this.toast.show('No se pudo resolver el código');
      },
    });
  }

  protected onMoviePicked(movie: MovieSearchResult): void {
    if (movie.tmdb_id === null) {
      return;
    }

    this.resolvingMovie.set(true);
    this.pickedTitle.set(movie.title);

    this.catalog.getMovie(String(movie.tmdb_id)).subscribe({
      next: (detail) => {
        this.createForm.movie_id = detail.uuid;
        this.resolvingMovie.set(false);
      },
      error: () => {
        this.resolvingMovie.set(false);
        this.toast.show('No se pudo importar la película');
      },
    });
  }

  protected submitCreate(): void {
    const form = this.createForm;
    if (!form.movie_id.trim() || !form.sku.trim() || form.price_euros === null || form.stock_available === null) {
      this.toast.show('Completa película, SKU, precio y stock');
      return;
    }

    this.saving.set(true);
    this.inventory.addCopy({
      movie_id: form.movie_id.trim(),
      sku: form.sku.trim(),
      barcode: form.barcode.trim() || null,
      format: form.format,
      region: form.region.trim() || null,
      condition: form.condition,
      cover_photo_url: form.cover_photo_url.trim() || null,
      price_cents: Math.round(form.price_euros * 100),
      stock_available: form.stock_available,
    }).subscribe({
      next: () => {
        this.saving.set(false);
        this.showCreate.set(false);
        this.toast.show('Copia añadida');
        this.load(true);
      },
      error: (err: HttpErrorResponse) => {
        this.saving.set(false);
        this.toast.show(this.messageFor(err));
      },
    });
  }

  protected startEdit(copy: Copy): void {
    this.showCreate.set(false);
    this.editingId.set(copy.id);
    this.editForm = {
      condition: copy.condition,
      barcode: copy.barcode ?? '',
      price_euros: copy.price_cents / 100,
      active: true,
    };
  }

  protected cancelEdit(): void {
    this.editingId.set(null);
  }

  protected submitEdit(): void {
    const id = this.editingId();
    if (!id) {
      return;
    }

    const form = this.editForm;
    this.saving.set(true);
    this.inventory.updateCopy(id, {
      condition: form.condition,
      barcode: form.barcode.trim() || null,
      price_cents: form.price_euros !== null ? Math.round(form.price_euros * 100) : undefined,
      active: form.active,
    }).subscribe({
      next: () => {
        this.saving.set(false);
        this.editingId.set(null);
        this.toast.show('Copia actualizada');
        this.load(true);
      },
      error: (err: HttpErrorResponse) => {
        this.saving.set(false);
        this.toast.show(this.messageFor(err));
      },
    });
  }

  protected loadMore(): void {
    if (this.page() < this.totalPages()) {
      this.page.update((value) => value + 1);
      this.load(false);
    }
  }

  private load(reset: boolean): void {
    if (reset) {
      this.page.set(1);
    }
    this.loading.set(true);

    this.inventory.listCopies({ page: this.page() }).subscribe({
      next: (result) => {
        this.copies.set(reset ? result.copies : [...this.copies(), ...result.copies]);
        this.totalPages.set(result.total_pages);
        this.total.set(result.total);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  private emptyCreate(): CreateForm {
    return {
      movie_id: '',
      sku: '',
      format: 'DVD',
      condition: 'good',
      barcode: '',
      region: '',
      price_euros: null,
      stock_available: null,
      cover_photo_url: '',
    };
  }

  private messageFor(err: HttpErrorResponse): string {
    return (err.error?.error as string) ?? 'No se pudo guardar';
  }
}
