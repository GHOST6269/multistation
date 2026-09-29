import { Component, OnInit } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { catchError, filter, forkJoin, of } from 'rxjs';
import { AppUser, UserRole } from './models/user';
import { AuthService } from './services/auth.service';
import { ArticleService } from './services/article.service';
import { FuelService } from './services/fuel.service';
import { SupplierService } from './services/supplier.service';

interface NavChild {
  label: string;
  route: string;
  queryParams?: Record<string, string>;
  roles?: UserRole[];
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
  notificationsOpen = false;
  notifications: { type: 'tank' | 'supplier' | 'customer'; title: string; detail: string; route: string }[] = [];
  notificationFilter: 'ALL' | 'tank' | 'supplier' | 'customer' = 'ALL';
  notificationPage = 1;
  readonly notificationPageSize = 5;
  private notificationTimer?: ReturnType<typeof setInterval>;
  readonly navigation: NavItem[] = [
    { label: 'Vue d’ensemble', route: '/', icon: '⌂', roles: ['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL', 'ROLE_ASSISTANT'] },
    { label: 'Stations', route: '/stations', icon: '◇', roles: ['ROLE_SUPER_ADMIN'] },
    {
      label: 'Opérations',
      route: '/carburants',
      icon: '⛽',
      roles: ['ROLE_GERANT', 'ROLE_ASSISTANT', 'ROLE_QUALITY_MARSHALL'],
      children: [
        { label: 'Ventes & relevés', route: '/carburants/ventes', roles: ['ROLE_GERANT', 'ROLE_ASSISTANT'] },
        { label: 'Versements', route: '/carburants/versements', roles: ['ROLE_GERANT', 'ROLE_ASSISTANT'] },
        { label: 'Livraisons carburant', route: '/carburants/livraisons', roles: ['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL', 'ROLE_ASSISTANT'] },
        { label: 'Encaissements', route: '/carburants/encaissements', roles: ['ROLE_GERANT', 'ROLE_ASSISTANT'] },
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
        { label: 'Prélèvements', route: '/fournisseurs/prelevements' },
      ],
    },
    {
      label: 'Clients', route: '/clients', icon: '♧', roles: ['ROLE_GERANT'],
      children: [
        { label: 'Compte client', route: '/clients/credit' },
        { label: 'Relevé client', route: '/clients/releve' },
        { label: 'Historique des paiements', route: '/clients/historique' },
      ],
    },
    { label: 'Dépenses', route: '/depenses', icon: '◒', roles: ['ROLE_GERANT', 'ROLE_ASSISTANT'] },
    { label: 'Utilisateurs', route: '/utilisateurs', icon: '♙', roles: ['ROLE_SUPER_ADMIN', 'ROLE_GERANT'] },
  ];

  constructor(public readonly auth: AuthService, private readonly router: Router, private readonly articles: ArticleService, private readonly fuel: FuelService, private readonly suppliers: SupplierService) {}

  ngOnInit(): void {
    this.loginPage = this.router.url.startsWith('/connexion');
    this.auth.loadMe();
    this.auth.user$.subscribe((user) => { this.user = user; this.notifications = []; if (user) this.loadNotifications(); });
    this.notificationTimer = setInterval(() => this.loadNotifications(), 5 * 60 * 1000);
    this.router.events.pipe(filter((event) => event instanceof NavigationEnd)).subscribe((event) => {
      this.loginPage = (event as NavigationEnd).urlAfterRedirects.startsWith('/connexion');
    });
  }

  loadNotifications(): void {
    if (!this.user || this.loginPage || !this.auth.hasAnyRole(['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL', 'ROLE_ASSISTANT'])) { this.notifications = []; return; }
    this.articles.options().subscribe({
      next: options => {
        const stationIds = options.stations.map(station => Number(station.id));
        if (!stationIds.length) { this.notifications = []; return; }
        const canSeeSupplierInvoices = this.auth.hasAnyRole(['ROLE_GERANT']);
        const requests = stationIds.map(stationId => forkJoin({
          fuel: this.fuel.workspace(stationId).pipe(catchError(() => of({ tanks: [] } as any))),
          suppliers: canSeeSupplierInvoices ? this.suppliers.list(stationId).pipe(catchError(() => of({ invoices: [] }))) : of({ invoices: [] }),
        }));
        forkJoin(requests).subscribe(results => {
          const now = new Date();
          const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
          const items: typeof this.notifications = [];
          results.forEach((data, index) => {
            const stationName = options.stations[index].name;
            for (const tank of data.fuel.tanks ?? []) if (Number(tank.stock) <= Number(tank.minimum)) items.push({ type: 'tank', title: `Cuve au seuil · ${tank.name}`, detail: `${stationName} · ${tank.stock} / ${tank.minimum} L`, route: '/stock-carburant' });
            for (const invoice of data.suppliers.invoices ?? []) if (invoice.dueDate && invoice.dueDate <= today && Number(invoice.remaining) > 0.01) items.push({ type: 'supplier', title: `Paiement fournisseur · ${invoice.supplier}`, detail: `Facture ${invoice.number} · Échéance ${invoice.dueDate}`, route: '/fournisseurs/factures' });
            for (const reading of data.fuel.readings ?? []) {
              const remaining = Number(reading.totalAmount ?? 0) - (reading.payments ?? []).reduce((sum: number, payment: any) => sum + Number(payment.amount ?? 0), 0);
              if (this.auth.canViewCustomers() && Number(reading.customerId ?? 0) > 0 && reading.dueDate && reading.dueDate <= today && remaining > 0.01) items.push({ type: 'customer', title: `Paiement client en retard · ${reading.customerName}`, detail: `Relevé ${reading.invoiceNumber || '#' + reading.id} · Échéance ${reading.dueDate} · Reste ${remaining.toLocaleString('fr-FR')} Ar`, route: '/clients/credit' });
            }
          });
          this.notifications = items;
          this.notificationPage = Math.min(this.notificationPage, this.notificationTotalPages);
        });
      },
      error: () => this.notifications = [],
    });
  }

  get filteredNotifications() { return this.notificationFilter === 'ALL' ? this.notifications : this.notifications.filter(item => item.type === this.notificationFilter); }
  get notificationTotalPages(): number { return Math.max(1, Math.ceil(this.filteredNotifications.length / this.notificationPageSize)); }
  get pagedNotifications() { const start = (this.notificationPage - 1) * this.notificationPageSize; return this.filteredNotifications.slice(start, start + this.notificationPageSize); }
  setNotificationFilter(value: 'ALL' | 'tank' | 'supplier' | 'customer'): void { this.notificationFilter = value; this.notificationPage = 1; }

  visible(item: { visible?: boolean; roles?: UserRole[]; route?: string }): boolean {
    if (item.visible === false) return false;
    if (item.route?.startsWith('/clients')) return this.auth.canViewCustomers();
    return !item.roles || this.auth.hasAnyRole(item.roles);
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
