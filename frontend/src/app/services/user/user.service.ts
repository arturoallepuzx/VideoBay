import { Injectable } from '@angular/core';
import { Observable, map } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import { AdminUser, CreateUserPayload, UpdateUserPayload } from './user.models';

@Injectable({ providedIn: 'root' })
export class UserService extends BaseApiService {

  listUsers(): Observable<AdminUser[]> {
    return this.get<{ users: AdminUser[] }>('/users/active-sessions').pipe(map((response) => response.users));
  }

  createUser(payload: CreateUserPayload): Observable<AdminUser> {
    return this.post<AdminUser>('/users', payload);
  }

  updateUser(userId: string, payload: UpdateUserPayload): Observable<AdminUser> {
    return this.put<AdminUser>(`/users/${userId}`, payload);
  }

  deleteUser(userId: string): Observable<unknown> {
    return this.delete(`/users/${userId}`);
  }

  forceLogout(userId: string): Observable<unknown> {
    return this.post(`/users/${userId}/force-logout`);
  }
}
