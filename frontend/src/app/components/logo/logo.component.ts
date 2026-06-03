import { ChangeDetectionStrategy, Component, input } from '@angular/core';

@Component({
  selector: 'vb-logo',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (compact()) {
      <svg [attr.height]="size()" viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <circle cx="20" cy="20" r="17" stroke="var(--vb-red)" stroke-width="2" />
        <circle cx="20" cy="20" r="3.5" fill="var(--vb-red)" />
      </svg>
    } @else {
      <svg [attr.height]="size()" viewBox="0 0 172 40" fill="none" aria-hidden="true" style="display:block">
        <text x="0" y="30" font-family="Fraunces, Georgia, serif" font-size="32" font-weight="500"
          fill="currentColor" letter-spacing="-1">Video</text>
        <circle cx="104" cy="20" r="4" fill="var(--vb-red)" />
        <text x="116" y="30" font-family="Fraunces, Georgia, serif" font-size="32" font-weight="500"
          font-style="italic" fill="var(--vb-red)" letter-spacing="-1">Bay</text>
      </svg>
    }
  `,
})
export class LogoComponent {

  readonly size = input(28);
  readonly compact = input(false);
}
