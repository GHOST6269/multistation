import { Injectable } from '@angular/core';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class CustomerService {
  constructor(private readonly api: ApiService) {}
  list(station: number) { return this.api.get<{ customers: any[] }>('customers', { station }); }
  create(data: any) { return this.api.post<any>('customers', data); }
  update(id: number, data: any) { return this.api.put<any>(`customers/${id}`, data); }
  deactivate(id: number) { return this.api.patch<any>(`customers/${id}/deactivate`, {}); }
}
