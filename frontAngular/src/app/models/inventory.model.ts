export interface InventoryItem {
  id: number; stationId: number; station: string; articleId: number; article: string;
  category: string; currentStock: number; minimumStock: number; active: boolean; alert: boolean; unit?:string; symbol?:string|null;
  units?: { articleUnitId:number; name:string; symbol:string|null; conversionFactor:number; isBaseUnit:boolean }[];
}
export interface StockAdjustment { quantity:number; type:'ADD'|'REMOVE'|'SET'; reason:string }

export interface DashboardData {
  metrics: { stations: number; activeStations: number; users: number };
  stationsForSelection: { id: number; name: string; status: string | null }[];
  selectedStationId: number | null;
  finance: {
    stationId: number; stationName: string; from: string; to: string;
    customerReceivable: number | null; supplierPayable: number | null;
    revenue: number; expenses: number;
    expenseRows: { id: number; date: string; category: string; description: string; amount: number }[];
  } | null;
  stations: StationActivity[];
}

export interface StationActivity { id: number; name: string; city: string | null; status: string; manager: string | null; }
