import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'money' })
export class MoneyPipe implements PipeTransform {

  transform(cents: number | null | undefined, currency = 'EUR'): string {
    const amount = (cents ?? 0) / 100;

    return new Intl.NumberFormat('es-ES', { style: 'currency', currency }).format(amount);
  }
}
