import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { IonIcon } from '@ionic/angular/standalone';
import { LogoComponent } from '../../../components/logo/logo.component';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';
import { AuthService } from '../../../services/auth/auth.service';

@Component({
  selector: 'app-login',
  imports: [FormsModule, IonIcon, LogoComponent, TmdbImagePipe],
  templateUrl: 'login.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoginPage {

  protected email = '';
  protected password = '';
  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly capsLock = signal(false);

  protected readonly artPosters = [
    '/3bhkrj58Vtu7enYsRolD1fZdja1.jpg',
    '/q6y0Go1tsGEsmtFryDOJo3dEmqu.jpg',
    '/qJ2tW6WMUDux911r6m7haRef0WH.jpg',
    '/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg',
    '/oYuLEt3zVCKq57qu2F8dT7NIa6f.jpg',
    '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
    '/arw2vcBveWOVZr6pxd9XTd1TdQa.jpg',
    '/aKuFiU82s5ISJpGZp7YkIr3kCUd.jpg',
    '/rCzpDGLbOoPwLjy3OAm5NUPOTrC.jpg',
    '/hek3koDUyRQk7FIhPXsa6mT2Zc3.jpg',
    '/3jcbDmRFiQ83drXNOvRDeKHxS0C.jpg',
    '/ow3wq89wM8qd5X7hWKxiRfsFf9C.jpg',
  ];

  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  constructor() {
    this.auth.ensureSession().subscribe((authenticated) => {
      if (authenticated) {
        this.router.navigateByUrl(this.returnUrl());
      }
    });
  }

  protected onKey(event: KeyboardEvent): void {
    this.capsLock.set(event.getModifierState('CapsLock'));
  }

  protected submit(): void {
    if (!this.email.trim() || !this.password) {
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.auth.login(this.email.trim(), this.password).subscribe({
      next: () => this.router.navigateByUrl(this.returnUrl()),
      error: () => {
        this.error.set('Email o contraseña incorrectos.');
        this.loading.set(false);
      },
    });
  }

  private returnUrl(): string {
    return this.route.snapshot.queryParamMap.get('returnUrl') ?? '/home';
  }
}
