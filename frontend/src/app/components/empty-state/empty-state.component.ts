import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { IonIcon } from '@ionic/angular/standalone';

@Component({
  selector: 'vb-empty-state',
  imports: [IonIcon],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="empty">
      <div class="icon"><ion-icon [name]="icon()" style="font-size:32px"></ion-icon></div>
      <h3>{{ title() }}</h3>
      @if (hint()) {
        <p style="margin:6px 0 12px;font-size:14px">{{ hint() }}</p>
      }
      <ng-content></ng-content>
    </div>
  `,
})
export class EmptyStateComponent {

  readonly icon = input('information-circle-outline');
  readonly title = input.required<string>();
  readonly hint = input('');
}
