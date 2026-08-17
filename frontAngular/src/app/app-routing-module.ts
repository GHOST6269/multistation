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

const routes: Routes = [
  { path: '', component: Dashboard, title: 'Vue d’ensemble · StationFlow' },
  { path: 'stations', component: Stations, title: 'Stations · StationFlow' },
  { path: 'inventaire', component: Inventory, title: 'Inventaire · StationFlow' },
  { path: 'articles', component: Articles, title: 'Articles · StationFlow' },
  { path: 'carburants', component: Fuel, title: 'Carburants & Pompes · StationFlow' },
  { path: 'stock-carburant', component: FuelStock, title: 'Stock carburant · StationFlow' },
  {
    path: 'carburants/configuration',
    component: FuelConfig,
    title: 'Configuration carburants · StationFlow',
  },
  { path: 'fournisseurs', component: Suppliers, title: 'Fournisseurs · StationFlow' },
  { path: 'equipe', redirectTo: 'stations' },
  { path: '**', redirectTo: '' },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
