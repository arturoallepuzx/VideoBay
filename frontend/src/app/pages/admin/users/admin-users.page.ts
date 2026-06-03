import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { IonIcon } from '@ionic/angular/standalone';
import { AdminSidebarComponent } from '../../../components/admin-sidebar/admin-sidebar.component';
import { UserService } from '../../../services/user/user.service';
import { AdminUser } from '../../../services/user/user.models';
import { ToastService } from '../../../services/ui/toast.service';

interface CreateForm {
  role: string;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  avatar_url: string;
}

interface EditForm {
  name: string;
  email: string;
  role: string;
  avatar_url: string;
}

@Component({
  selector: 'app-admin-users',
  imports: [DatePipe, FormsModule, IonIcon, AdminSidebarComponent],
  templateUrl: 'admin-users.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminUsersPage {

  protected readonly roles = [
    { value: 'customer', label: 'Cliente' },
    { value: 'admin', label: 'Admin' },
  ];

  protected readonly users = signal<AdminUser[]>([]);
  protected readonly loading = signal(false);
  protected readonly skeletons = Array.from({ length: 5 });
  protected readonly saving = signal(false);
  protected readonly showCreate = signal(false);
  protected readonly editingId = signal<string | null>(null);
  protected readonly confirmDeleteId = signal<string | null>(null);

  protected createForm: CreateForm = this.emptyCreate();
  protected editForm: EditForm = { name: '', email: '', role: 'customer', avatar_url: '' };

  private readonly userService = inject(UserService);
  private readonly toast = inject(ToastService);

  constructor() {
    this.load();
  }

  protected roleLabel(value: string): string {
    return this.roles.find((item) => item.value === value)?.label ?? value;
  }

  protected toggleCreate(): void {
    this.showCreate.update((open) => !open);
    if (this.showCreate()) {
      this.editingId.set(null);
      this.createForm = this.emptyCreate();
    }
  }

  protected submitCreate(): void {
    const form = this.createForm;
    if (!form.name.trim() || !form.email.trim() || form.password.length < 8) {
      this.toast.show('Nombre, email y contraseña (mín. 8) obligatorios');
      return;
    }
    if (form.password !== form.password_confirmation) {
      this.toast.show('Las contraseñas no coinciden');
      return;
    }

    this.saving.set(true);
    this.userService.createUser({
      role: form.role,
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password,
      password_confirmation: form.password_confirmation,
      avatar_url: form.avatar_url.trim() || null,
    }).subscribe({
      next: () => {
        this.saving.set(false);
        this.showCreate.set(false);
        this.toast.show('Usuario creado');
        this.load();
      },
      error: (err: HttpErrorResponse) => {
        this.saving.set(false);
        this.toast.show(this.messageFor(err));
      },
    });
  }

  protected startEdit(user: AdminUser): void {
    this.showCreate.set(false);
    this.confirmDeleteId.set(null);
    this.editingId.set(user.id);
    this.editForm = {
      name: user.name,
      email: user.email,
      role: user.role,
      avatar_url: user.avatar_url ?? '',
    };
  }

  protected cancelEdit(): void {
    this.editingId.set(null);
  }

  protected submitEdit(userId: string): void {
    const form = this.editForm;
    this.saving.set(true);
    this.userService.updateUser(userId, {
      name: form.name.trim(),
      email: form.email.trim(),
      role: form.role,
      avatar_url: form.avatar_url.trim() || null,
    }).subscribe({
      next: () => {
        this.saving.set(false);
        this.editingId.set(null);
        this.toast.show('Usuario actualizado');
        this.load();
      },
      error: (err: HttpErrorResponse) => {
        this.saving.set(false);
        this.toast.show(this.messageFor(err));
      },
    });
  }

  protected forceLogout(user: AdminUser): void {
    this.userService.forceLogout(user.id).subscribe({
      next: () => this.toast.show('Sesiones cerradas'),
      error: (err: HttpErrorResponse) => this.toast.show(this.messageFor(err)),
    });
  }

  protected askDelete(user: AdminUser): void {
    this.editingId.set(null);
    this.confirmDeleteId.set(user.id);
  }

  protected cancelDelete(): void {
    this.confirmDeleteId.set(null);
  }

  protected remove(userId: string): void {
    this.userService.deleteUser(userId).subscribe({
      next: () => {
        this.confirmDeleteId.set(null);
        this.toast.show('Usuario borrado');
        this.load();
      },
      error: (err: HttpErrorResponse) => this.toast.show(this.messageFor(err)),
    });
  }

  private load(): void {
    this.loading.set(true);
    this.userService.listUsers().subscribe({
      next: (users) => {
        this.users.set(users);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  private emptyCreate(): CreateForm {
    return { role: 'customer', name: '', email: '', password: '', password_confirmation: '', avatar_url: '' };
  }

  private messageFor(err: HttpErrorResponse): string {
    return (err.error?.error as string) ?? 'No se pudo completar la operación';
  }
}
