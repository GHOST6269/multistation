import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
@Injectable({ providedIn: 'root' })
export class SupplierService {
  constructor(private api: ApiService) {}
  list(station: number): Observable<any> {
    return this.api.get('suppliers', { station });
  }
  paymentHistory(station: number): Observable<any> {
    return this.api.get('suppliers/payment-history', { station });
  }
  create(data: any) {
    return this.api.post<any>('suppliers', data);
  }
  delivery(data: any) {
    return this.api.post<any>('suppliers/delivery', data);
  }
  pay(data: any) {
    return this.api.post<any>('suppliers/payments', data);
  }
  deactivate(id: number) {
    return this.api.patch(`suppliers/${id}/deactivate`, {});
  }
}
