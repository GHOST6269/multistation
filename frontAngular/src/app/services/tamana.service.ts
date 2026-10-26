import { Injectable } from '@angular/core';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class TamanaService {
  constructor(private readonly api: ApiService) {}
  list(station: number) { return this.api.get<any>('tamana', { station }); }
  createCard(data: any) { return this.api.post<any>('tamana/cards', data); }
  transact(data: any) { return this.api.post<any>('tamana/transactions', data); }
}
