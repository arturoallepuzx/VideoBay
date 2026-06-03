import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { InventoryService } from '../../../services/inventory/inventory.service';
import { ToastService } from '../../../services/ui/toast.service';

@Component({
  selector: 'app-sell',
  imports: [FormsModule, IonIcon],
  templateUrl: 'sell.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SellPage implements OnInit {

  protected title = '';
  protected format = 'DVD';
  protected condition = 'good';
  protected barcode = '';
  protected notes = '';
  protected movieId: string | null = null;
  protected readonly submitting = signal(false);

  protected readonly formats = [
    { value: 'DVD', label: 'DVD' },
    { value: 'BLURAY', label: 'Blu-ray' },
    { value: 'UHD_4K', label: '4K UHD' },
    { value: 'VHS', label: 'VHS' },
  ];

  protected readonly conditions = [
    { value: 'new', label: 'Nuevo / Precintado' },
    { value: 'like_new', label: 'Como nuevo' },
    { value: 'good', label: 'Bueno' },
    { value: 'fair', label: 'Aceptable' },
  ];

  private readonly inventory = inject(InventoryService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly route = inject(ActivatedRoute);

  ngOnInit(): void {
    const params = this.route.snapshot.queryParamMap;
    this.barcode = params.get('barcode') ?? '';
    this.title = params.get('title') ?? '';
    this.movieId = params.get('movie');
  }

  protected submit(): void {
    this.submitting.set(true);

    this.inventory
      .proposeSale({
        movie_id: this.movieId,
        title_text: this.title.trim() || null,
        barcode: this.barcode.trim() || null,
        format: this.format,
        condition: this.condition,
        notes: this.notes.trim() || null,
      })
      .subscribe({
        next: () => {
          this.toast.show('Propuesta enviada. Te contactaremos con una oferta.');
          this.router.navigate(['/home']);
        },
        error: () => {
          this.submitting.set(false);
          this.toast.show('No se pudo enviar la propuesta');
        },
      });
  }
}
