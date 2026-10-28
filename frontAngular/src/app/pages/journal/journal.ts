import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ArticleService } from '../../services/article.service';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { formatMoney } from '../../shared/money-format';

@Component({ selector: 'app-journal', standalone: false, templateUrl: './journal.html', styleUrl: './journal.scss' })
export class Journal implements OnInit {
  stations: DropdownOption[] = [];
  stationId = 0;
  type: 'CASH' | 'BANK' | 'CREDIT' | 'SUPPLIER' = 'CASH';
  from = '';
  to = '';
  clientId: number | '' = '';
  detailQuery = '';
  directionFilter: 'ALL' | 'IN' | 'OUT' = 'ALL';
  rows: any[] = [];
  incoming = 0;
  outgoing = 0;
  balance = 0;
  loading = false;
  error = '';
  constructor(private articles: ArticleService, private api: ApiService, private route: ActivatedRoute, private cdr: ChangeDetectorRef, public auth: AuthService) {}
  ngOnInit(): void {
    this.route.data.subscribe(data => { this.type = data['journalType'] ?? 'CASH'; this.load(); });
    this.articles.options().subscribe(data => { this.stations = data.stations.map(s => ({ value: s.id, label: s.name })); this.stationId = data.stations[0]?.id ?? 0; this.load(); });
  }
  get title(): string { return ({ CASH: 'Journal de caisse', BANK: 'Journal de banque', CREDIT: 'Journal de crédit', SUPPLIER: 'Journal des prélèvements fournisseurs' } as Record<string, string>)[this.type]; }
  get description(): string { return ({ CASH: 'Ventes et dépenses payées avec un mode associé à la caisse.', BANK: 'Versements et dépenses payés avec un mode associé à la banque.', CREDIT: 'Crédits accordés aux clients, leurs règlements et dépenses associées.', SUPPLIER: 'Versements et dépenses associés aux prélèvements fournisseurs.' } as Record<string, string>)[this.type]; }
  get usesFlowFilter(): boolean { return this.type === 'CASH' || this.type === 'BANK'; }
  get clientOptions(): DropdownOption[] {
    const clients = new Map<number, string>();
    for (const row of this.rows) if (row.customerId && row.customerName) clients.set(Number(row.customerId), String(row.customerName));
    return [{ value: '', label: 'Tous les clients' }, ...Array.from(clients, ([value, label]) => ({ value, label })).sort((a, b) => a.label.localeCompare(b.label, 'fr'))];
  }
  get filteredRows(): any[] {
    const query = this.detailQuery.trim().toLocaleLowerCase('fr');
    return this.rows.filter(row => (!this.usesFlowFilter || this.directionFilter === 'ALL' || row.direction === this.directionFilter) && (this.clientId === '' || Number(row.customerId) === Number(this.clientId)) && (!query || String(row.detail ?? '').toLocaleLowerCase('fr').includes(query)));
  }
  get displayIncoming(): number { return this.usesFlowFilter && this.directionFilter !== 'ALL' ? this.filteredRows.filter(row => row.direction === 'IN').reduce((sum, row) => sum + Number(row.amount || 0), 0) : this.incoming; }
  get displayOutgoing(): number { return this.usesFlowFilter && this.directionFilter !== 'ALL' ? this.filteredRows.filter(row => row.direction === 'OUT').reduce((sum, row) => sum + Number(row.amount || 0), 0) : this.outgoing; }
  get displayBalance(): number { return this.usesFlowFilter && this.directionFilter !== 'ALL' ? this.displayIncoming - this.displayOutgoing : this.balance; }
  load(): void {
    if (!this.stationId) return;
    this.loading = true; this.error = '';
    this.api.get<any>('journals', { station: this.stationId, type: this.type, from: this.from, to: this.to }).subscribe({
      next: data => { this.rows = data.rows ?? []; this.incoming = Number(data.incoming ?? 0); this.outgoing = Number(data.outgoing ?? 0); this.balance = Number(data.balance ?? 0); this.loading = false; this.cdr.detectChanges(); },
      error: error => { this.rows = []; this.error = error.error?.message ?? 'Le journal n’a pas pu être chargé.'; this.loading = false; this.cdr.detectChanges(); },
    });
  }
  clearDates(): void { this.from = ''; this.to = ''; this.clientId = ''; this.detailQuery = ''; this.directionFilter = 'ALL'; this.load(); }
  money(value: number): string { return formatMoney(value); }
}
