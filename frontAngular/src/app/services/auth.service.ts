import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { BehaviorSubject, Observable, catchError, finalize, map, of, shareReplay, tap } from 'rxjs';
import { AppUser, UserRole } from '../models/user';
import { ApiService } from './api.service';

const TOKEN_KEY = 'stationflow_token';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly userSubject = new BehaviorSubject<AppUser | null>(null);
  private sessionRequest?: Observable<AppUser | null>;
  readonly user$ = this.userSubject.asObservable();

  constructor(private readonly api: ApiService, private readonly router: Router) {}

  get token(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }

  get user(): AppUser | null {
    return this.userSubject.value;
  }

  login(email: string, password: string): Observable<{ token: string; user: AppUser }> {
    return this.api.post<{ token: string; user: AppUser }>('auth/login', { email, password }).pipe(
      tap((session) => {
        localStorage.setItem(TOKEN_KEY, session.token);
        this.userSubject.next(session.user);
      }),
    );
  }

  ensureUser(): Observable<AppUser | null> {
    if (!this.token) return of(null);
    if (this.user) return of(this.user);
    if (!this.sessionRequest) {
      this.sessionRequest = this.api.get<{ user: AppUser }>('auth/me').pipe(
        map((data) => data.user),
        tap((user) => this.userSubject.next(user)),
        catchError(() => {
          this.logout(false);
          return of(null);
        }),
        finalize(() => this.sessionRequest = undefined),
        shareReplay(1),
      );
    }
    return this.sessionRequest;
  }

  loadMe(): void {
    this.ensureUser().subscribe();
  }

  logout(navigate = true): void {
    localStorage.removeItem(TOKEN_KEY);
    this.userSubject.next(null);
    if (navigate) this.router.navigate(['/connexion']);
  }

  hasAnyRole(roles: UserRole[]): boolean {
    const user = this.user;
    if (!user) return false;
    if (user.role === 'ROLE_SUPER_ADMIN') return true;
    return roles.includes(user.role);
  }

  isSuperAdmin(): boolean {
    return this.user?.role === 'ROLE_SUPER_ADMIN';
  }

  stationLocked(): boolean {
    return !!this.user && this.user.role !== 'ROLE_SUPER_ADMIN';
  }
}
