import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ArticleService } from '../../services/article.service';
import { FuelService } from '../../services/fuel.service';
import { AuthService } from '../../services/auth.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';

@Component({ selector: 'app-fuel-payments', standalone: false, templateUrl: './fuel-payments.html', styleUrl: './fuel-payments.scss' })
export class FuelPayments implements OnInit {
  stations: DropdownOption[] = []; stationId = 0; payments: any[] = []; fromDate = ''; toDate = ''; page = 1; readonly pageSize = 10; loading = true;
  constructor(private articles: ArticleService, private fuel: FuelService, private cdr: ChangeDetectorRef, public auth: AuthService) {}
  ngOnInit() { this.articles.options().subscribe(data => { this.stations = data.stations.map(s => ({ value: s.id, label: s.name })); this.stationId = data.stations[0]?.id ?? 0; this.load(); }); }
  load() { if (!this.stationId) return; this.loading = true; this.page = 1; this.fuel.paymentHistory(this.stationId).subscribe({ next: data => { this.payments = data.payments ?? []; this.loading = false; this.cdr.detectChanges(); }, error: () => { this.payments = []; this.loading = false; } }); }
  get filtered() { return this.payments.filter(row => (!this.fromDate || row.date >= this.fromDate) && (!this.toDate || row.date <= this.toDate)); }
  get totalAmount() { return this.filtered.reduce((sum, row) => sum + Number(row.amount || 0), 0); }
  get methods() { return new Set(this.filtered.map(row => row.method)).size; }
  get totalPages() { return Math.max(1, Math.ceil(this.filtered.length / this.pageSize)); } get pages() { return Array.from({ length: this.totalPages }, (_, index) => index + 1); } get rows() { const page = Math.min(this.page, this.totalPages); return this.filtered.slice((page - 1) * this.pageSize, page * this.pageSize); } resetPage() { this.page = 1; }
  money(value: number) { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value); }
}
