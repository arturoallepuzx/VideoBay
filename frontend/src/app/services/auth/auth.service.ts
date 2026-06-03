import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, catchError, map, of, shareReplay, tap } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import { ThemeService } from '../theme/theme.service';
import { AccessibilitySettings, AuthSession, AuthUser } from './auth.models';

@Injectable({ providedIn: 'root' })
export class AuthService extends BaseApiService {

  private readonly theme = inject(ThemeService);

  private readonly currentUser = signal<AuthUser | null>(null);
  private readonly resolved = signal(false);

  readonly user = this.currentUser.asReadonly();
  readonly isAuthenticated = computed(() => this.currentUser() !== null);
  readonly isAdmin = computed(() => this.currentUser()?.role === 'admin');
  readonly sessionResolved = this.resolved.asReadonly();

  private session$?: Observable<boolean>;

  ensureSession(): Observable<boolean> {
    if (this.resolved()) {
      return of(this.isAuthenticated());
    }

    if (!this.session$) {
      this.session$ = this.loadSession().pipe(
        map(() => true),
        catchError(() => of(false)),
        tap(() => this.resolved.set(true)),
        shareReplay(1),
      );
    }

    return this.session$;
  }

  loadSession(): Observable<AuthSession> {
    return this.get<AuthSession>('/auth/me').pipe(tap((session) => this.applySession(session)));
  }

  login(email: string, password: string): Observable<AuthSession> {
    return this.post<AuthSession>('/auth/login', { email, password }).pipe(
      tap((session) => {
        this.applySession(session);
        this.resolved.set(true);
      }),
    );
  }

  logout(): Observable<unknown> {
    return this.post('/auth/logout').pipe(tap(() => this.currentUser.set(null)));
  }

  logoutEverywhere(): Observable<unknown> {
    return this.post('/auth/logout-all').pipe(tap(() => this.currentUser.set(null)));
  }

  updateProfile(payload: Partial<Pick<AuthUser, 'name' | 'email' | 'avatar_url'>>): Observable<AuthSession> {
    return this.put<AuthSession>('/auth/me', payload).pipe(tap((session) => this.applySession(session)));
  }

  updateAccessibility(settings: AccessibilitySettings): Observable<{ accessibility_settings: AccessibilitySettings }> {
    return this.put<{ accessibility_settings: AccessibilitySettings }>(
      '/auth/me/accessibility-settings',
      { accessibility_settings: settings },
    ).pipe(
      tap((response) => {
        const user = this.currentUser();
        if (user) {
          this.currentUser.set({ ...user, accessibility_settings: response.accessibility_settings });
        }
      }),
    );
  }

  changePassword(currentPassword: string, newPassword: string): Observable<unknown> {
    return this.post('/auth/me/password', {
      current_password: currentPassword,
      password: newPassword,
      password_confirmation: newPassword,
    });
  }

  deleteMyAccount(): Observable<unknown> {
    return this.delete('/auth/me').pipe(tap(() => this.currentUser.set(null)));
  }

  private applySession(session: AuthSession): void {
    this.currentUser.set(session.user);
    this.theme.applySettings(session.user.accessibility_settings);
  }
}
