import { Pipe, PipeTransform } from '@angular/core';
import { environment } from '../../environments/environment';

export type TmdbImageKind = 'poster' | 'backdrop' | 'profile';

@Pipe({ name: 'tmdbImage' })
export class TmdbImagePipe implements PipeTransform {

  transform(path: string | null | undefined, kind: TmdbImageKind = 'poster'): string | null {
    if (!path) {
      return null;
    }

    if (/^https?:\/\//i.test(path)) {
      return path;
    }

    return environment.tmdbImage[kind] + path;
  }
}
