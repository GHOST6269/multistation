import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DashboardData } from '../../models/inventory.model';
import { Station } from '../../services/station';
import { AuthService } from '../../services/auth.service';

@Component({ selector: 'app-dashboard', standalone: false, templateUrl: './dashboard.html', styleUrl: './dashboard.scss' })
export class Dashboard implements OnInit {
  data?: DashboardData; loading = true; refreshing = false; error = false; errorMessage = ''; rangeError = false;
  stationId = 0;
  dateFrom = '';
  dateTo = '';
  constructor(private readonly stations: Station, private readonly cdr: ChangeDetectorRef, public readonly auth: AuthService) {}
  ngOnInit(): void {
    const today = this.localDate(new Date());
    this.dateTo = today;
    this.dateFrom = `${today.slice(0, 8)}01`;
    this.load();
  }
  load(): void {
    this.error = false;
    this.errorMessage = '';
    if (this.dateFrom && this.dateTo && this.dateFrom > this.dateTo) {
      this.rangeError = true;
      return;
    }
    this.rangeError = false;
    this.refreshing = !!this.data;
    this.loading = !this.data;
    this.stations.dashboard(this.stationId, this.dateFrom, this.dateTo).subscribe({
      next: data => {
        this.data = data;
        this.stationId = Number(data.selectedStationId ?? 0);
        if (data.finance) { this.dateFrom = data.finance.from; this.dateTo = data.finance.to; }
        this.loading = false; this.refreshing = false; this.cdr.detectChanges();
      },
      error: error => { this.error = true; this.errorMessage = error.error?.message ?? 'Impossible de charger les données financières.'; this.loading = false; this.refreshing = false; this.cdr.detectChanges(); },
    });
  }
  private localDate(date: Date): string { return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`; }
  initials(name: string): string { return name.split(' ').map(word => word[0]).join('').slice(0, 2).toUpperCase(); }
}
