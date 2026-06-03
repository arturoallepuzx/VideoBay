import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { AuthService } from '../../../services/auth/auth.service';
import { WishlistService } from '../../../services/wishlist/wishlist.service';
import { FavoriteSlot } from '../../../services/wishlist/wishlist.models';
import { ToastService } from '../../../services/ui/toast.service';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';

@Component({
  selector: 'app-profile',
  imports: [FormsModule, IonIcon, TmdbImagePipe],
  templateUrl: 'profile.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProfilePage {

  protected readonly auth = inject(AuthService);
  protected readonly favorites = signal<FavoriteSlot[]>([]);
  protected readonly editing = signal(false);
  protected readonly saving = signal(false);

  protected form = { name: '', email: '', avatar_url: '' };

  private readonly wishlist = inject(WishlistService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  constructor() {
    this.wishlist.listFavorites().subscribe({
      next: (response) => this.favorites.set(response.slots),
      error: () => this.favorites.set([]),
    });
  }

  protected startEdit(): void {
    const user = this.auth.user();
    if (!user) {
      return;
    }
    this.form = { name: user.name, email: user.email, avatar_url: user.avatar_url ?? '' };
    this.editing.set(true);
  }

  protected cancelEdit(): void {
    this.editing.set(false);
  }

  protected save(): void {
    if (!this.form.name.trim() || !this.form.email.trim()) {
      this.toast.show('Nombre y email son obligatorios');
      return;
    }

    this.saving.set(true);
    this.auth
      .updateProfile({
        name: this.form.name.trim(),
        email: this.form.email.trim(),
        avatar_url: this.form.avatar_url.trim() || null,
      })
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.editing.set(false);
          this.toast.show('Perfil actualizado');
        },
        error: () => {
          this.saving.set(false);
          this.toast.show('No se pudo actualizar el perfil');
        },
      });
  }

  protected go(path: string): void {
    this.router.navigate([path]);
  }

  protected openMovie(id: string): void {
    this.router.navigate(['/movie', id]);
  }
}
