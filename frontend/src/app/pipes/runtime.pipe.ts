import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'runtime' })
export class RuntimePipe implements PipeTransform {

  transform(minutes: number | null | undefined): string {
    if (!minutes || minutes <= 0) {
      return '';
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return hours > 0 ? `${hours}h ${rest}m` : `${rest}m`;
  }
}
