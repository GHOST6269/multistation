import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ArticleService } from '../../services/article.service';
import { FuelService } from '../../services/fuel.service';

@Component({
  selector: 'app-attendants',
  standalone: false,
  templateUrl: './attendants.html',
  styleUrl: './attendants.scss',
})
export class Attendants implements OnInit {
  stations: any[] = [];
  stationId = 0;
  nozzles: any[] = [];
  attendants: any[] = [];
  history: any[] = [];
  nozzleId = 0;
  attendantId = 0;
  historyFrom = '';
  historyTo = '';
  historyAttendantId = 0;
  historyNozzle = '';
  historyPage = 1;
  readonly historyPageSize = 10;
  loading = false;
  saving = false;
  error = '';
  success = '';

  constructor(private stationsApi: ArticleService, private fuel: FuelService, private cdr: ChangeDetectorRef) {}

  ngOnInit(): void {
    this.stationsApi.options().subscribe({
      next: data => { this.stations = data.stations ?? []; this.stationId = Number(this.stations[0]?.id ?? 0); this.load(); },
      error: () => { this.error = 'Impossible de charger les stations.'; },
    });
  }

  load(): void {
    if (!this.stationId) return;
    this.loading = true;
    this.error = '';
    this.fuel.attendantAssignments(this.stationId).subscribe({
      next: data => {
        this.nozzles = data.nozzles ?? [];
        this.attendants = data.attendants ?? [];
        this.history = data.history ?? [];
        if (!this.nozzles.some(row => Number(row.id) === Number(this.nozzleId))) this.nozzleId = 0;
        if (!this.attendants.some(row => Number(row.id) === Number(this.attendantId))) this.attendantId = 0;
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: e => { this.error = e.error?.message ?? 'Impossible de charger les attributions.'; this.loading = false; this.cdr.detectChanges(); },
    });
  }

  get availableNozzles(): any[] { return this.nozzles.filter(row => !row.attendantId); }
  get assignedNozzles(): any[] { return this.nozzles.filter(row => row.attendantId); }
  get historyAttendants(): any[] {
    const options = new Map<number, string>();
    for (const row of this.history) if (Number(row.attendantId)) options.set(Number(row.attendantId), String(row.attendant ?? 'Pompiste'));
    return [...options].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
  }
  get historyNozzles(): string[] { return [...new Set(this.history.map(row => String(row.nozzle ?? '')).filter(Boolean))].sort((a, b) => a.localeCompare(b)); }
  get filteredHistory(): any[] {
    const from = this.historyFrom ? new Date(this.historyFrom).getTime() : null;
    const to = this.historyTo ? new Date(this.historyTo).getTime() : null;
    return this.history.filter(row => {
      if (this.historyAttendantId && Number(row.attendantId) !== Number(this.historyAttendantId)) return false;
      if (this.historyNozzle && String(row.nozzle ?? '') !== this.historyNozzle) return false;
      if (from === null && to === null) return true;
      const moments = [row.assignedAt, row.removedAt].filter(Boolean).map((value: string) => new Date(value).getTime());
      return moments.some((moment: number) => (from === null || moment >= from) && (to === null || moment <= to));
    });
  }
  get historyTotalPages(): number { return Math.max(1, Math.ceil(this.filteredHistory.length / this.historyPageSize)); }
  get historyPageEnd(): number { return Math.min(this.historyPage * this.historyPageSize, this.filteredHistory.length); }
  get pagedHistory(): any[] {
    const page = Math.min(this.historyPage, this.historyTotalPages);
    const start = (page - 1) * this.historyPageSize;
    return this.filteredHistory.slice(start, start + this.historyPageSize);
  }
  resetHistoryPage(): void { this.historyPage = 1; }
  changeStation(): void { this.historyPage = 1; this.load(); }
  changeHistoryPage(page: number): void { this.historyPage = Math.min(this.historyTotalPages, Math.max(1, page)); }
  resetHistoryFilters(): void { this.historyFrom = ''; this.historyTo = ''; this.historyAttendantId = 0; this.historyNozzle = ''; this.resetHistoryPage(); }

  assign(): void {
    if (this.saving || !this.stationId || !this.nozzleId || !this.attendantId) return;
    this.saving = true; this.error = ''; this.success = '';
    this.fuel.assignNozzle({ stationId: this.stationId, nozzleId: this.nozzleId, attendantId: this.attendantId }).subscribe({
      next: () => { this.saving = false; this.nozzleId = 0; this.attendantId = 0; this.success = 'Le pistolet a été attribué.'; this.load(); },
      error: e => { this.saving = false; this.error = e.error?.message ?? 'L’attribution a échoué.'; this.cdr.detectChanges(); },
    });
  }

  remove(nozzle: any): void {
    if (this.saving) return;
    this.saving = true; this.error = ''; this.success = '';
    this.fuel.removeNozzleAssignment(Number(nozzle.id), this.stationId).subscribe({
      next: () => { this.saving = false; this.success = 'L’attribution a été terminée.'; this.load(); },
      error: e => { this.saving = false; this.error = e.error?.message ?? 'Le retrait a échoué.'; this.cdr.detectChanges(); },
    });
  }

  dateTime(value: string | null): string {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    const pad = (part: number) => String(part).padStart(2, '0');
    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
  }
}
