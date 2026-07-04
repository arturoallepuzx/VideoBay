import { bootstrapApplication } from '@angular/platform-browser';
import { provideHttpClient, withInterceptorsFromDi, withFetch, HTTP_INTERCEPTORS } from '@angular/common/http';
import { RouteReuseStrategy, provideRouter, withPreloading, PreloadAllModules, withInMemoryScrolling } from '@angular/router';
import { IonicRouteStrategy, provideIonicAngular } from '@ionic/angular/standalone';

import { routes } from './app/app.routes';
import { AppComponent } from './app/app.component';
import { InterceptorProvider } from './app/providers/interceptor';
import { registerAppIcons } from './app/icons';
import { isDevMode } from '@angular/core';
import { provideServiceWorker } from '@angular/service-worker';

registerAppIcons();

bootstrapApplication(AppComponent, {
  providers: [
    { provide: RouteReuseStrategy, useClass: IonicRouteStrategy },
    { provide: HTTP_INTERCEPTORS, useClass: InterceptorProvider, multi: true },
    provideIonicAngular({ mode: 'ios' }),
    provideHttpClient(withInterceptorsFromDi(), withFetch()),
    provideRouter(routes, withPreloading(PreloadAllModules), withInMemoryScrolling({ scrollPositionRestoration: 'enabled' })), provideServiceWorker('ngsw-worker.js', {
            enabled: !isDevMode(),
            registrationStrategy: 'registerWhenStable:30000'
          }),
  ],
});
