import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { DashboardData, InventoryItem, StockAdjustment } from '../models/inventory.model';
import { Station as StationModel, StationInput } from '../models/station.model';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class Station {
  constructor(private readonly api: ApiService) {}
  dashboard(station?: number, from?: string, to?: string): Observable<DashboardData> {
    const params: Record<string, string | number> = {};
    if (station) params['station'] = station;
    if (from) params['from'] = from;
    if (to) params['to'] = to;
    return this.api.get<DashboardData>('dashboard', params);
  }
  getAll(search = ''): Observable<StationModel[]> { return this.api.get<StationModel[]>('stations', search ? { search } : undefined); }
  create(station: StationInput): Observable<StationModel> { return this.api.post<StationModel>('stations', station); }
  update(id: number, station: StationInput): Observable<StationModel> { return this.api.put<StationModel>(`stations/${id}`, station); }
  deactivate(id: number): Observable<StationModel> { return this.api.patch<StationModel>(`stations/${id}/deactivate`, {}); }
  activate(id: number): Observable<StationModel> { return this.api.patch<StationModel>(`stations/${id}/activate`, {}); }
  inventory(stationId?: number): Observable<InventoryItem[]> { return this.api.get<InventoryItem[]>('inventory', stationId ? { station: stationId } : undefined); }
  adjustStock(id:number,data:StockAdjustment):Observable<InventoryItem>{return this.api.post<InventoryItem>(`inventory/${id}/adjust`,data)}
  validateStocktake(data:{stationId:number;reference:string;reason:string;lines:{id:number;counts:{articleUnitId:number;quantity:number}[]}[]}):Observable<InventoryItem[]>{return this.api.post<InventoryItem[]>('inventory/stocktake',data)}
}
