import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';

@Component({
  selector: 'vb-admin-sidebar',
  imports: [RouterLink, RouterLinkActive, IonIcon],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <aside class="admin-side">
      <h5>Panel</h5>
      <a routerLink="/admin" routerLinkActive="is-active" [routerLinkActiveOptions]="{ exact: true }">
        <ion-icon name="grid-outline" style="font-size:16px"></ion-icon> Dashboard
      </a>
      <a routerLink="/admin/orders" routerLinkActive="is-active">
        <ion-icon name="cube-outline" style="font-size:16px"></ion-icon> Pedidos
      </a>
      <a routerLink="/admin/pickup" routerLinkActive="is-active">
        <ion-icon name="barcode-outline" style="font-size:16px"></ion-icon> Recogida
      </a>
      <a routerLink="/admin/inventory" routerLinkActive="is-active">
        <ion-icon name="list-outline" style="font-size:16px"></ion-icon> Inventario
      </a>
      <a routerLink="/admin/pricing" routerLinkActive="is-active">
        <ion-icon name="pricetag-outline" style="font-size:16px"></ion-icon> Precios
      </a>
      <a routerLink="/admin/streaming" routerLinkActive="is-active">
        <ion-icon name="play" style="font-size:16px"></ion-icon> Streaming
      </a>
      <a routerLink="/admin/users" routerLinkActive="is-active">
        <ion-icon name="person-outline" style="font-size:16px"></ion-icon> Usuarios
      </a>
      <a routerLink="/admin/moderation" routerLinkActive="is-active">
        <ion-icon name="flag-outline" style="font-size:16px"></ion-icon> Moderación
      </a>
      <h5>Volver</h5>
      <a routerLink="/home">
        <ion-icon name="chevron-back" style="font-size:16px"></ion-icon> A la tienda
      </a>
    </aside>
  `,
})
export class AdminSidebarComponent {}
