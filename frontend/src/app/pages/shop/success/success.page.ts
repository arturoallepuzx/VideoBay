import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-success',
  templateUrl: 'success.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SuccessPage {

  private readonly router = inject(Router);

  protected goOrders(): void {
    this.router.navigate(['/orders']);
  }

  protected goHome(): void {
    this.router.navigate(['/home']);
  }
}
