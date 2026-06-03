import { inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

export type QueryParams = Record<string, string | number | boolean | undefined | null>;

export abstract class BaseApiService {

  protected readonly http = inject(HttpClient);

  protected get<T>(endpoint: string, params?: QueryParams): Observable<T> {
    return this.http.get<T>(endpoint, { params: this.buildParams(params) });
  }

  protected post<T>(endpoint: string, body?: unknown): Observable<T> {
    return this.http.post<T>(endpoint, body ?? {});
  }

  protected put<T>(endpoint: string, body?: unknown): Observable<T> {
    return this.http.put<T>(endpoint, body ?? {});
  }

  protected delete<T>(endpoint: string): Observable<T> {
    return this.http.delete<T>(endpoint);
  }

  private buildParams(params?: QueryParams): HttpParams {
    let httpParams = new HttpParams();

    if (!params) {
      return httpParams;
    }

    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null) {
        httpParams = httpParams.set(key, String(value));
      }
    }

    return httpParams;
  }
}
