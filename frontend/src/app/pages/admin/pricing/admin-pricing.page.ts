import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { AdminSidebarComponent } from '../../../components/admin-sidebar/admin-sidebar.component';
import { InventoryService } from '../../../services/inventory/inventory.service';
import { PricingRules } from '../../../services/inventory/inventory.models';
import { ToastService } from '../../../services/ui/toast.service';
import { MoneyPipe } from '../../../pipes/money.pipe';

@Component({
  selector: 'app-admin-pricing',
  imports: [FormsModule, IonIcon, AdminSidebarComponent, MoneyPipe],
  templateUrl: 'admin-pricing.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminPricingPage {

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly currency = signal('EUR');
  protected readonly formatKeys = signal<string[]>([]);
  protected readonly conditionKeys = signal<string[]>([]);

  protected basePriceEuros: Record<string, number> = {};
  protected conditionMultipliers: Record<string, number> = {};
  protected buyMargin = 40;
  protected previewFormat = '';
  protected previewCondition = '';

  private readonly inventory = inject(InventoryService);
  private readonly toast = inject(ToastService);

  constructor() {
    this.inventory.getPricing().subscribe({
      next: (rules) => this.apply(rules),
      error: () => {
        this.loading.set(false);
        this.toast.show('No se pudieron cargar los precios');
      },
    });
  }

  protected formatLabel(value: string): string {
    return { DVD: 'DVD', BLURAY: 'Blu-ray', UHD_4K: '4K UHD', VHS: 'VHS' }[value] ?? value;
  }

  protected conditionLabel(value: string): string {
    return { new: 'Nuevo', like_new: 'Como nuevo', good: 'Bueno', fair: 'Aceptable' }[value] ?? value;
  }

  protected previewSaleCents(): number {
    const baseCents = Math.round((this.basePriceEuros[this.previewFormat] ?? 0) * 100);
    const multiplier = this.conditionMultipliers[this.previewCondition] ?? 0;

    return Math.round(baseCents * multiplier);
  }

  protected previewBuyCents(): number {
    return Math.round(this.previewSaleCents() * (this.buyMargin / 100));
  }

  protected save(): void {
    this.saving.set(true);

    const basePricesCents: Record<string, number> = {};
    for (const [key, euros] of Object.entries(this.basePriceEuros)) {
      basePricesCents[key] = Math.max(0, Math.round((euros || 0) * 100));
    }

    this.inventory
      .updatePricing({
        base_prices_cents: basePricesCents,
        condition_multipliers: this.conditionMultipliers,
        buy_margin_percent: this.buyMargin,
      })
      .subscribe({
        next: (rules) => {
          this.apply(rules);
          this.saving.set(false);
          this.toast.show('Precios actualizados');
        },
        error: () => {
          this.saving.set(false);
          this.toast.show('No se pudieron guardar los precios');
        },
      });
  }

  private apply(rules: PricingRules): void {
    const euros: Record<string, number> = {};
    for (const [key, cents] of Object.entries(rules.base_prices_cents)) {
      euros[key] = cents / 100;
    }

    this.basePriceEuros = euros;
    this.conditionMultipliers = { ...rules.condition_multipliers };
    this.buyMargin = rules.buy_margin_percent;
    this.currency.set(rules.currency);
    this.formatKeys.set(Object.keys(euros));
    this.conditionKeys.set(Object.keys(rules.condition_multipliers));
    this.previewFormat = this.formatKeys()[0] ?? '';
    this.previewCondition = this.conditionKeys()[0] ?? '';
    this.loading.set(false);
  }
}
