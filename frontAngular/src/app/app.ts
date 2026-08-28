import { Component, OnInit } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs';
import { AppUser, UserRole } from './models/user';
import { AuthService } from './services/auth.service';

interface NavChild {
  label: string;
  route: string;
  queryParams?: Record<string, string>;
}

interface NavItem {
  label: string;
  route: string;
  icon: string;
  badge?: string;
  roles?: UserRole[];
  visible?: boolean;
  children?: NavChild[];
}

@Component({
  selector: 'app-root',
  templateUrl: './app.html',
  standalone: false,
  styleUrl: './app.scss',
})
export class App implements OnInit {
  // Desktop keeps the sidebar visible through CSS; on mobile it must start closed after a refresh.
  menuOpen = false;
  expandedMenu = '/carburants';
  loginPage = false;
  user: AppUser | null = null;
  readonly navigation: NavItem[] = [
    { label: 'Vue d’ensemble', route: '/', icon: '⌂', roles: ['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL', 'ROLE_ASSISTANT'] },
    { label: 'Stations', route: '/stations', icon: '◇', roles: ['ROLE_SUPER_ADMIN'] },
    {
      label: 'Opérations',
      route: '/carburants',
      icon: '⛽',
      roles: ['ROLE_GERANT', 'ROLE_ASSISTANT', 'ROLE_QUALITY_MARSHALL'],
      children: [
        { label: 'Ventes & relevés', route: '/carburants/ventes' },
        { label: 'Livraisons carburant', route: '/carburants/livraisons' },
        { label: 'Encaissements', route: '/carburants/encaissements' },
        { label: 'État des cuves', route: '/stock-carburant' },
      ],
    },
    { label: 'Paramètres', route: '/carburants/configuration', icon: '⚙', roles: ['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL'] },
    { label: 'Articles', route: '/articles', icon: '▤', visible: false },
    {
      label: 'Fournisseurs', route: '/fournisseurs', icon: '▱', roles: ['ROLE_GERANT'],
      children: [
        { label: 'Factures fournisseur', route: '/fournisseurs/factures' },
        { label: 'Historique des paiements', route: '/fournisseurs/historique' },
      ],
    },
    { label: 'Utilisateurs', route: '/utilisateurs', icon: '♙', roles: ['ROLE_SUPER_ADMIN'] },
  ];

  constructor(public readonly auth: AuthService, private readonly router: Router) {}

  ngOnInit(): void {
    this.loginPage = this.router.url.startsWith('/connexion');
    this.auth.loadMe();
    this.auth.user$.subscribe((user) => this.user = user);
    this.router.events.pipe(filter((event) => event instanceof NavigationEnd)).subscribe((event) => {
      this.loginPage = (event as NavigationEnd).urlAfterRedirects.startsWith('/connexion');
    });
  }

  visible(item: { visible?: boolean; roles?: UserRole[] }): boolean {
    return item.visible !== false && (!item.roles || this.auth.hasAnyRole(item.roles));
  }

  isMenuOpen(item: NavItem): boolean {
    return this.expandedMenu === item.route;
  }

  toggleMenu(item: NavItem): void {
    this.expandedMenu = this.isMenuOpen(item) ? '' : item.route;
  }

  initials(): string {
    if (!this.user) return '--';
    return `${this.user.firstName?.[0] ?? ''}${this.user.lastName?.[0] ?? ''}`.toUpperCase() || this.user.email.slice(0, 2).toUpperCase();
  }

  logout(): void {
    this.auth.logout();
  }
}
