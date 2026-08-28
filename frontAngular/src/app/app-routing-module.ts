import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { Dashboard } from './pages/dashboard/dashboard';
import { Inventory } from './pages/inventory/inventory';
import { Stations } from './pages/stations/stations';
import { Articles } from './pages/articles/articles';
import { Fuel } from './pages/fuel/fuel';
import { FuelConfig } from './pages/fuel-config/fuel-config';
import { Suppliers } from './pages/suppliers/suppliers';
import { FuelStock } from './pages/fuel-stock/fuel-stock';
import { FuelSales } from './pages/fuel-sales/fuel-sales';
import { FuelDeliveries } from './pages/fuel-deliveries/fuel-deliveries';
import { FuelPayments } from './pages/fuel-payments/fuel-payments';
import { Login } from './pages/login/login';
import { Create } from './pages/user/create/create';
import { AuthGuard } from './services/auth.guard';
import { RoleGuard } from './services/role.guard';

const routes: Routes = [
  { path: 'connexion', component: Login, title: 'Connexion · StationFlow' },
  { path: '', component: Dashboard, title: 'Vue d’ensemble · StationFlow', canActivate: [AuthGuard] },
  { path: 'stations', component: Stations, title: 'Stations · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_SUPER_ADMIN'] } },
  { path: 'inventaire', component: Inventory, title: 'Inventaire · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL'] } },
  { path: 'articles', component: Articles, title: 'Articles · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL'] } },
  { path: 'carburants/ventes', component: FuelSales, title: 'Ventes & relevés · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT', 'ROLE_ASSISTANT'] } },
  { path: 'carburants/livraisons', component: FuelDeliveries, title: 'Livraisons carburant · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT', 'ROLE_ASSISTANT'] } },
  { path: 'carburants/encaissements', component: FuelPayments, title: 'Encaissements carburant · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT', 'ROLE_ASSISTANT'] } },
  { path: 'carburants', component: Fuel, title: 'Carburants & Pompes · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT', 'ROLE_ASSISTANT'] } },
  { path: 'stock-carburant', component: FuelStock, title: 'Stock carburant · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL'] } },
  {
    path: 'carburants/configuration',
    component: FuelConfig,
    title: 'Configuration carburants · StationFlow',
    canActivate: [AuthGuard, RoleGuard],
    data: { roles: ['ROLE_GERANT', 'ROLE_QUALITY_MARSHALL'] },
  },
  { path: 'fournisseurs', redirectTo: 'fournisseurs/factures', pathMatch: 'full' },
  { path: 'fournisseurs/factures', component: Suppliers, title: 'Factures fournisseur · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT'], view: 'invoices' } },
  { path: 'fournisseurs/historique', component: Suppliers, title: 'Historique fournisseurs · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_GERANT'], view: 'payments' } },
  { path: 'utilisateurs', component: Create, title: 'Utilisateurs · StationFlow', canActivate: [AuthGuard, RoleGuard], data: { roles: ['ROLE_SUPER_ADMIN'] } },
  { path: 'equipe', redirectTo: 'utilisateurs' },
  { path: '**', redirectTo: '' },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
