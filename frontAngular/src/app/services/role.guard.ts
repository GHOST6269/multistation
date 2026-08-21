import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, UrlTree } from '@angular/router';
import { AuthService } from './auth.service';
import { UserRole } from '../models/user';

@Injectable({ providedIn: 'root' })
export class RoleGuard implements CanActivate {
  constructor(private readonly auth: AuthService, private readonly router: Router) {}

  canActivate(route: ActivatedRouteSnapshot): boolean | UrlTree {
    const roles = (route.data['roles'] ?? []) as UserRole[];
    if (this.auth.token && !this.auth.user) return true;
    if (this.auth.hasAnyRole(roles)) return true;
    return this.router.parseUrl('/');
  }
}
