import {environment} from '../../../environments/environment';
import {Injectable, Injector} from '@angular/core';
import {HttpClient, HttpErrorResponse} from '@angular/common/http';
import {catchError, Observable, throwError} from 'rxjs';

export type HttpMethod = 'get' | 'post' | 'patch' | 'put' | 'delete';

@Injectable({
  providedIn: 'root',
})
export abstract class BaseApiService {

  protected apiUrl: string = environment.apiUrl;

  public http: HttpClient;

  protected constructor(
    protected injector: Injector
  ) {
    this.http = this.injector.get<HttpClient>(HttpClient);
  }


  /**
   * Hacer una llamada http
   *
   */
  public httpCall(endpoint: string, params: any = null, method: HttpMethod): Observable<ApiResponse> {
    return this.makeHttpCall(endpoint, params, method);
  }


  /**
   * Ejecuta una petición HTTP
   *
   */
  makeHttpCall(endpoint: string, params: any = null, method: HttpMethod): Observable<ApiResponse> {
    switch (method) {
      case 'get':
        return this.getHttpCall(endpoint, params);

      case 'post':
        return this.postHttpCall(endpoint, params);

      case 'patch':
        return this.patchHttpCall(endpoint, params);

      case 'put':
        return this.putHttpCall(endpoint, params);

      case 'delete':
        return this.deleteHttpCall(endpoint, params);

      default:
        console.warn(`Unknown HTTP method received: ${method}`);
        break;
    }

    return this.getHttpCall(endpoint, params); // Use GET request as a default callback
  }


  /**
   * Llamada http tipo 'post'
   *
   */
  private postHttpCall(endpoint: string, params: any): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(this.apiUrl + endpoint, params)
      .pipe(catchError((error) => this.handleError(error)));
  }


  /**
   * Llamada http tipo 'put'
   *
   */
  private putHttpCall(endpoint: string, params: any): Observable<ApiResponse> {
    return this.http.put<ApiResponse>(this.apiUrl + endpoint, params)
      .pipe(catchError((error) => this.handleError(error)));
  }


  /**
   * Llamada http tipo 'patch'
   *
   */
  private patchHttpCall(endpoint: string, params?: any): Observable<ApiResponse> {
    return this.http.patch<ApiResponse>(this.apiUrl + endpoint, params)
      .pipe(catchError((error) => this.handleError(error)));
  }


  /**
   * Llamada http tipo 'delete'
   *
   */
  private deleteHttpCall(endpoint: string, params?: any): Observable<ApiResponse> {
    return this.http.delete<ApiResponse>(this.apiUrl + endpoint, {params})
      .pipe(catchError((error) => this.handleError(error)));
  }


  /**
   * Llamada http tipo 'get'
   *
   */
  private getHttpCall(endpoint: string, params?: any): Observable<ApiResponse> {
    return this.http.get<ApiResponse>(this.apiUrl + endpoint, {params})
      .pipe(catchError((error) => this.handleError(error)));
  }


  /**
   * Manejar errores HTTP
   *
   */
  private handleError(error: HttpErrorResponse): Observable<never> {
    return throwError(() => new Error(error.message));
  }

}

export interface ApiResponse {
  data: any;
  status: number;
  message: string;
}
