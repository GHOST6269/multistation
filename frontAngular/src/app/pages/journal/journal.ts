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
  get description(): string { return ({ CASH: 'Mouvements réglés en espèces : ventes, règlements clients et dépenses.', BANK: 'Versements par banque. Les paiements des factures fournisseurs sont exclus.', CREDIT: 'Crédits accordés aux clients et règlements de leurs comptes.', SUPPLIER: 'Versements enregistrés avec un mode marqué « prélèvement fournisseur ».' } as Record<string, string>)[this.type]; }
  load(): void {
    if (!this.stationId) return;
    this.loading = true; this.error = '';
    this.api.get<any>('journals', { station: this.stationId, type: this.type, from: this.from, to: this.to }).subscribe({
      next: data => { this.rows = data.rows ?? []; this.incoming = Number(data.incoming ?? 0); this.outgoing = Number(data.outgoing ?? 0); this.balance = Number(data.balance ?? 0); this.loading = false; this.cdr.detectChanges(); },
      error: error => { this.rows = []; this.error = error.error?.message ?? 'Le journal n’a pas pu être chargé.'; this.loading = false; this.cdr.detectChanges(); },
    });
  }
  clearDates(): void { this.from = ''; this.to = ''; this.load(); }
  money(value: number): string { return formatMoney(value); }
}
