import { Injectable } from '@angular/core';
import { ApiService } from './api.service';
@Injectable({ providedIn: 'root' }) export class ExpenseService { constructor(private api: ApiService) {} list(station: number) { return this.api.get<{ expenses: any[] }>('expenses', { station }); } create(data: any) { return this.api.post<any>('expenses', data); } }
