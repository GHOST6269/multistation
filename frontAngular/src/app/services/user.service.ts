import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { AppUser, RoleOption, UserInput } from '../models/user';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class UserService {
  constructor(private readonly api: ApiService) {}
  list(): Observable<AppUser[]> { return this.api.get<AppUser[]>('users'); }
  roles(): Observable<{ roles: RoleOption[] }> { return this.api.get<{ roles: RoleOption[] }>('users/roles'); }
  create(data: UserInput): Observable<AppUser> { return this.api.post<AppUser>('users', data); }
  update(id: number, data: UserInput): Observable<AppUser> { return this.api.put<AppUser>(`users/${id}`, data); }
  toggle(id: number): Observable<AppUser> { return this.api.patch<AppUser>(`users/${id}/toggle`, {}); }
}
