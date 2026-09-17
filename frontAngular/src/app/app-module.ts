import { NgModule, provideBrowserGlobalErrorListeners } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { HTTP_INTERCEPTORS, HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { AppRoutingModule } from './app-routing-module';
import { App } from './app';
import { Dashboard } from './pages/dashboard/dashboard';
import { Stations } from './pages/stations/stations';
import { Inventory } from './pages/inventory/inventory';
import { Articles } from './pages/articles/articles';
import { Dropdown } from './shared/dropdown/dropdown';
import { MoneyInputDirective } from './shared/money-input.directive';
import { MoneyPipe } from './shared/money.pipe';
import { Fuel } from './pages/fuel/fuel';
import { FuelConfig } from './pages/fuel-config/fuel-config';
import { Suppliers } from './pages/suppliers/suppliers';
import { FuelStock } from './pages/fuel-stock/fuel-stock';
import { FuelSales } from './pages/fuel-sales/fuel-sales';
import { FuelDeliveries } from './pages/fuel-deliveries/fuel-deliveries';
import { FuelPayments } from './pages/fuel-payments/fuel-payments';
import { Login } from './pages/login/login';
import { Create } from './pages/user/create/create';
import { Profile } from './pages/profile/profile';
import { Customers } from './pages/customers/customers';
import { AuthInterceptor } from './services/auth.interceptor';

@NgModule({
  declarations: [
    App,
    Dashboard,
    Stations,
    Inventory,
    Articles,
    Dropdown,
    MoneyInputDirective,
    MoneyPipe,
    Fuel,
    FuelConfig,
    Suppliers,
    FuelStock,
    FuelSales,
    FuelDeliveries,
    FuelPayments,
    Login,
    Create,
    Profile,
    Customers,
  ],
  imports: [BrowserModule, HttpClientModule, FormsModule, ReactiveFormsModule, AppRoutingModule],
  providers: [
    provideBrowserGlobalErrorListeners(),
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true },
  ],
  bootstrap: [App],
})
export class AppModule {}
