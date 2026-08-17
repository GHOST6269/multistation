export interface InventoryItem {
  id: number; stationId: number; station: string; articleId: number; article: string;
  category: string; currentStock: number; minimumStock: number; active: boolean; alert: boolean; unit?:string; symbol?:string|null;
  units?: { articleUnitId:number; name:string; symbol:string|null; conversionFactor:number; isBaseUnit:boolean }[];
}
export interface StockAdjustment { quantity:number; type:'ADD'|'REMOVE'|'SET'; reason:string }

export interface DashboardData {
  metrics: { stations: number; activeStations: number; articles: number; users: number; lowStock: number };
  stations: StationActivity[];
  lowStock: InventoryItem[];
}

export interface StationActivity { id: number; name: string; city: string | null; status: string; articlesCount: number; manager: string | null; }
