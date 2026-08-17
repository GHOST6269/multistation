import { NgModule, provideBrowserGlobalErrorListeners } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { AppRoutingModule } from './app-routing-module';
import { App } from './app';
import { Dashboard } from './pages/dashboard/dashboard';
import { Stations } from './pages/stations/stations';
import { Inventory } from './pages/inventory/inventory';
import { Articles } from './pages/articles/articles';
import { Dropdown } from './shared/dropdown/dropdown';
import { MoneyInputDirective } from './shared/money-input.directive';
import { Fuel } from './pages/fuel/fuel';
import { FuelConfig } from './pages/fuel-config/fuel-config';
import { Suppliers } from './pages/suppliers/suppliers';
import { FuelStock } from './pages/fuel-stock/fuel-stock';

@NgModule({
  declarations: [
    App,
    Dashboard,
    Stations,
    Inventory,
    Articles,
    Dropdown,
    MoneyInputDirective,
    Fuel,
    FuelConfig,
    Suppliers,
    FuelStock,
  ],
  imports: [BrowserModule, HttpClientModule, FormsModule, ReactiveFormsModule, AppRoutingModule],
  providers: [provideBrowserGlobalErrorListeners()],
  bootstrap: [App],
})
export class AppModule {}
