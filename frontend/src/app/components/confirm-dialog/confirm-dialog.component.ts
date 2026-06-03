import { ChangeDetectionStrategy, Component, input, output } from '@angular/core';
import { IonIcon } from '@ionic/angular/standalone';

@Component({
  selector: 'vb-confirm-dialog',
  imports: [IonIcon],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (open()) {
      <div class="confirm__backdrop" (click)="cancel.emit()">
        <div class="confirm__box" role="dialog" aria-modal="true" (click)="$event.stopPropagation()">
          <div class="confirm__icon" [class.confirm__icon--danger]="danger()">
            <ion-icon [name]="danger() ? 'trash-outline' : 'information-circle-outline'"></ion-icon>
          </div>
          <h3 class="confirm__title">{{ title() }}</h3>
          @if (message()) {
            <p class="confirm__msg">{{ message() }}</p>
          }
          <div class="confirm__actions">
            <button class="btn" (click)="cancel.emit()">{{ cancelLabel() }}</button>
            <button class="btn" [class.confirm__danger]="danger()" [class.btn--primary]="!danger()"
              (click)="confirm.emit()">{{ confirmLabel() }}</button>
          </div>
        </div>
      </div>
    }
  `,
})
export class ConfirmDialogComponent {

  readonly open = input(false);
  readonly title = input('¿Estás seguro?');
  readonly message = input('');
  readonly confirmLabel = input('Confirmar');
  readonly cancelLabel = input('Cancelar');
  readonly danger = input(false);

  readonly confirm = output<void>();
  readonly cancel = output<void>();
}
