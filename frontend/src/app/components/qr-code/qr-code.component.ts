import { ChangeDetectionStrategy, Component, ElementRef, effect, input, viewChild } from '@angular/core';
import { BrowserQRCodeSvgWriter } from '@zxing/browser';

@Component({
  selector: 'vb-qr',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: '<div #host class="qr"></div>',
  styles: [`
    .qr {
      display: inline-flex;
      background: #fff;
      padding: 10px;
      border-radius: 10px;
      line-height: 0;
    }
    .qr svg { display: block; width: 100%; height: 100%; }
  `],
})
export class QrCodeComponent {

  readonly value = input.required<string>();
  readonly size = input(150);

  private readonly host = viewChild.required<ElementRef<HTMLDivElement>>('host');

  constructor() {
    effect(() => {
      const host = this.host().nativeElement;
      const value = this.value();
      const size = this.size();

      host.innerHTML = '';
      if (value) {
        new BrowserQRCodeSvgWriter().writeToDom(host, value, size, size);
      }
    });
  }
}
