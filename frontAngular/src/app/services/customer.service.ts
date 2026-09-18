import { Injectable } from '@angular/core';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class CustomerService {
  constructor(private readonly api: ApiService) {}
  list(station: number) { return this.api.get<{ customers: any[] }>('customers', { station }); }
  paymentHistory(station: number, filters: { from?: string; to?: string; customer?: string } = {}) { return this.api.get<{ payments: any[] }>('customers/payment-history', { station, from: filters.from ?? '', to: filters.to ?? '', customer: filters.customer ?? '' }); }
  create(data: any) { return this.api.post<any>('customers', data); }
  update(id: number, data: any) { return this.api.put<any>(`customers/${id}`, data); }
  deactivate(id: number) { return this.api.patch<any>(`customers/${id}/deactivate`, {}); }
  pay(data: any) { return this.api.post<any>('customers/payments', data); }
}
