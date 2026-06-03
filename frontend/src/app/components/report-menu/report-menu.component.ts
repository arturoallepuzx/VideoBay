import { ChangeDetectionStrategy, Component, computed, input, output, signal } from '@angular/core';
import { IonIcon } from '@ionic/angular/standalone';

export interface ReportReasonOption {
  value: string;
  label: string;
}

@Component({
  selector: 'vb-report-menu',
  imports: [IonIcon],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <span class="reportmenu">
      <button type="button" [class]="triggerClasses()" (click)="toggle($event)"
        [attr.aria-label]="label() || 'Reportar'">
        <ion-icon [name]="icon()"></ion-icon>@if (label()) {<span>{{ label() }}</span>}
      </button>

      @if (open()) {
        <div class="reportmenu__backdrop" (click)="open.set(false)"></div>
        <div class="reportmenu__pop" role="dialog"
          [class.reportmenu__pop--right]="alignRight()" [class.reportmenu__pop--up]="alignUp()"
          (click)="$event.stopPropagation()">
          <div class="reportmenu__head">
            <span>{{ title() }}</span>
            <button type="button" class="reportmenu__x" (click)="open.set(false)" aria-label="Cerrar">
              <ion-icon name="close"></ion-icon>
            </button>
          </div>
          @for (reason of reasons(); track reason.value) {
            <button type="button" class="reportmenu__item" (click)="pick(reason.value)">{{ reason.label }}</button>
          }
        </div>
      }
    </span>
  `,
})
export class ReportMenuComponent {

  readonly reasons = input<ReportReasonOption[]>([]);
  readonly title = input('¿Por qué lo reportas?');
  readonly icon = input('flag-outline');
  readonly label = input('');
  readonly triggerClass = input('');

  readonly picked = output<string>();

  protected readonly open = signal(false);
  protected readonly alignRight = signal(false);
  protected readonly alignUp = signal(false);
  protected readonly triggerClasses = computed(() => `reportmenu__trigger ${this.triggerClass()}`.trim());

  protected toggle(event: MouseEvent): void {
    if (this.open()) {
      this.open.set(false);
      return;
    }

    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const width = 244;
    const height = 60 + this.reasons().length * 44;

    this.alignRight.set(rect.left + width > window.innerWidth - 12);
    this.alignUp.set(rect.bottom + height > window.innerHeight - 12);
    this.open.set(true);
  }

  protected pick(value: string): void {
    this.picked.emit(value);
    this.open.set(false);
  }
}
