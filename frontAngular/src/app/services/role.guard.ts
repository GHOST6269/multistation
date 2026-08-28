import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, UrlTree } from '@angular/router';
import { AuthService } from './auth.service';
import { UserRole } from '../models/user';
import { Observable, map } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class RoleGuard implements CanActivate {
  constructor(private readonly auth: AuthService, private readonly router: Router) {}

  canActivate(route: ActivatedRouteSnapshot): Observable<boolean | UrlTree> {
    const roles = (route.data['roles'] ?? []) as UserRole[];
    return this.auth.ensureUser().pipe(
      map((user) => user && this.auth.hasAnyRole(roles) ? true : this.router.parseUrl('/connexion')),
    );
  }
}
