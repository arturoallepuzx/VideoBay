import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { IonIcon } from '@ionic/angular/standalone';
import { ToastService } from '../../services/ui/toast.service';

@Component({
  selector: 'vb-toast-stack',
  imports: [IonIcon],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="toast-wrap" aria-live="polite">
      @for (toast of toastService.toasts(); track toast.id) {
        <div class="toast toast--ok">
          <ion-icon name="checkmark"></ion-icon>
          <span>{{ toast.text }}</span>
        </div>
      }
    </div>
  `,
})
export class ToastStackComponent {

  protected readonly toastService = inject(ToastService);
}
