import { Injectable, inject } from '@angular/core';
import { Observable, catchError, filter, finalize, shareReplay, switchMap, take, throwError } from 'rxjs';
import {
  HttpErrorResponse,
  HttpEvent,
  HttpHandler,
  HttpHeaders,
  HttpInterceptor,
  HttpRequest,
  HttpResponse,
} from '@angular/common/http';
import { environment } from '../../environments/environment';
import { LoadingService } from '../services/loading/loading.service';

@Injectable()
export class InterceptorProvider implements HttpInterceptor {

  private readonly loading = inject(LoadingService);

  private refresh$: Observable<HttpResponse<unknown>> | null = null;

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const prepared = this.prepareRequest(request);
    const silent = this.isSilent(request);

    if (!silent) {
      this.loading.start();
    }

    return next.handle(prepared).pipe(
      catchError((error: HttpErrorResponse) => {
        if (error.status === 401 && !this.isAuthRequest(request.url)) {
          return this.refreshAndRetry(prepared, next);
        }

        return throwError(() => error);
      }),
      finalize(() => {
        if (!silent) {
          this.loading.stop();
        }
      }),
    );
  }

  private isSilent(request: HttpRequest<unknown>): boolean {
    return (request.url.includes('/playback/') && request.method !== 'GET')
      || request.url.includes('/auth/refresh');
  }

  private refreshAndRetry(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    if (!this.refresh$) {
      this.refresh$ = next.handle(this.buildRefreshRequest()).pipe(
        filter((event): event is HttpResponse<unknown> => event instanceof HttpResponse),
        take(1),
        shareReplay(1),
        catchError((error: HttpErrorResponse) => {
          this.refresh$ = null;
          return throwError(() => error);
        }),
      );
    }

    return this.refresh$.pipe(
      take(1),
      switchMap(() => {
        this.refresh$ = null;
        return next.handle(request);
      }),
    );
  }

  private buildRefreshRequest(): HttpRequest<unknown> {
    const xsrfToken = this.readCookie('XSRF-TOKEN');
    let headers = new HttpHeaders({ Accept: 'application/json' });
    if (xsrfToken) {
      headers = headers.set('X-XSRF-TOKEN', xsrfToken);
    }

    return new HttpRequest('POST', `${environment.apiUrl}/auth/refresh`, {}, {
      headers,
      withCredentials: true,
    });
  }

  private prepareRequest(request: HttpRequest<unknown>): HttpRequest<unknown> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      'Accept-Language': 'es',
    };

    const isMutating = !['GET', 'HEAD'].includes(request.method);
    const xsrfToken = this.readCookie('XSRF-TOKEN');
    if (isMutating && xsrfToken) {
      headers['X-XSRF-TOKEN'] = xsrfToken;
    }

    return request.clone({
      url: this.resolveUrl(request.url),
      setHeaders: headers,
      withCredentials: true,
    });
  }

  private isAuthRequest(url: string): boolean {
    return url.includes('/auth/refresh') || url.includes('/auth/login') || url.includes('/auth/logout');
  }

  private resolveUrl(url: string): string {
    if (/^https?:\/\//i.test(url)) {
      return url;
    }

    return environment.apiUrl + (url.startsWith('/') ? url : `/${url}`);
  }

  private readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
  }
}
