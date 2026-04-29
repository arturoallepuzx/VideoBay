import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpEvent, HttpHandler, HttpInterceptor, HttpRequest } from '@angular/common/http';

@Injectable()
export class InterceptorProvider implements HttpInterceptor {

  /**
   * Intercepta las peticiones HTTP y les añade las cabeceras por defecto
   * 
   */
  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return next.handle(this.setHeader(request));
  }


  /**
   * Clona la petición añadiendo las cabeceras
   * 
   */
  private setHeader(request: HttpRequest<any>): HttpRequest<any> {
    return request.clone({
      setHeaders: {
        Accept: 'application/json',
        'Accept-Language': 'es',
      }
    });
  }

}
