import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';

@Component({
  selector: 'vb-mobile-tabs',
  imports: [RouterLink, RouterLinkActive, IonIcon],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <nav class="tabs" aria-label="Navegación móvil">
      <a class="tabs__btn" routerLink="/home" routerLinkActive="is-active">
        <ion-icon name="home-outline"></ion-icon><span>Inicio</span>
      </a>
      <a class="tabs__btn" routerLink="/stream" routerLinkActive="is-active">
        <ion-icon name="play"></ion-icon><span>Stream</span>
      </a>
      <a class="tabs__btn" routerLink="/search" routerLinkActive="is-active">
        <ion-icon name="search-outline"></ion-icon><span>Buscar</span>
      </a>
      <a class="tabs__btn" routerLink="/scanner" routerLinkActive="is-active">
        <ion-icon name="barcode-outline"></ion-icon><span>Escanear</span>
      </a>
      <a class="tabs__btn" routerLink="/profile" routerLinkActive="is-active">
        <ion-icon name="person-outline"></ion-icon><span>Perfil</span>
      </a>
    </nav>
  `,
})
export class MobileTabsComponent {}
