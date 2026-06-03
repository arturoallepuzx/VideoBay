import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { ThemeService, ThemeChoice, SubtitleSize } from '../../../services/theme/theme.service';
import { AuthService } from '../../../services/auth/auth.service';
import { ToastService } from '../../../services/ui/toast.service';
import { ConfirmDialogComponent } from '../../../components/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-settings',
  imports: [FormsModule, IonIcon, ConfirmDialogComponent],
  templateUrl: 'settings.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SettingsPage {

  protected readonly theme = inject(ThemeService);
  protected readonly auth = inject(AuthService);

  protected readonly editing = signal(false);
  protected readonly saving = signal(false);
  protected readonly changingPassword = signal(false);
  protected readonly confirmDeleteOpen = signal(false);
  protected form = { name: '', email: '', avatar_url: '' };
  protected pwForm = { current: '', next: '', confirm: '' };

  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  protected setTheme(choice: ThemeChoice): void {
    this.theme.setTheme(choice);
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

  protected saveProfile(): void {
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

  protected changePassword(): void {
    if (!this.pwForm.current || !this.pwForm.next) {
      this.toast.show('Rellena la contraseña actual y la nueva');
      return;
    }
    if (this.pwForm.next.length < 8) {
      this.toast.show('La nueva contraseña debe tener al menos 8 caracteres');
      return;
    }
    if (this.pwForm.next !== this.pwForm.confirm) {
      this.toast.show('Las contraseñas nuevas no coinciden');
      return;
    }

    this.changingPassword.set(true);
    this.auth.changePassword(this.pwForm.current, this.pwForm.next).subscribe({
      next: () => {
        this.changingPassword.set(false);
        this.pwForm = { current: '', next: '', confirm: '' };
        this.toast.show('Contraseña cambiada');
      },
      error: () => {
        this.changingPassword.set(false);
        this.toast.show('No se pudo cambiar la contraseña (¿la actual es correcta?)');
      },
    });
  }

  protected setSubSize(value: SubtitleSize): void {
    this.theme.setSubtitleSize(value);
    this.persist();
  }

  protected setSubBg(on: boolean): void {
    this.theme.setSubtitleBackground(on);
    this.persist();
  }

  protected logout(): void {
    this.auth.logout().subscribe({
      next: () => this.router.navigateByUrl('/login'),
      error: () => this.router.navigateByUrl('/login'),
    });
  }

  protected logoutEverywhere(): void {
    this.auth.logoutEverywhere().subscribe({
      next: () => {
        this.toast.show('Sesión cerrada en todos los dispositivos');
        this.router.navigateByUrl('/login');
      },
      error: () => this.router.navigateByUrl('/login'),
    });
  }

  protected confirmDelete(): void {
    this.confirmDeleteOpen.set(false);
    this.auth.deleteMyAccount().subscribe({
      next: () => {
        this.toast.show('Cuenta eliminada');
        this.router.navigateByUrl('/login');
      },
      error: () => this.toast.show('No se pudo eliminar la cuenta'),
    });
  }

  private persist(): void {
    if (this.auth.isAuthenticated()) {
      this.auth.updateAccessibility(this.theme.currentSettings()).subscribe({ error: () => undefined });
    }
  }
}
