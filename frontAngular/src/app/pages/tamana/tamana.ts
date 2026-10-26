import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ArticleService } from '../../services/article.service';
import { TamanaService } from '../../services/tamana.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { formatMoney } from '../../shared/money-format';

@Component({ selector: 'app-tamana', standalone: false, templateUrl: './tamana.html', styleUrl: './tamana.scss' })
export class Tamana implements OnInit {
  stations: DropdownOption[] = []; stationId = 0; customers: any[] = []; cards: any[] = []; transactions: any[] = []; error = ''; success = ''; busy = false;
  cardForm; transactionForm;
  cardNumberFilter = ''; customerFilter = ''; dateFilter = '';
  constructor(private readonly articles: ArticleService, private readonly api: TamanaService, fb: FormBuilder, private readonly cdr: ChangeDetectorRef) {
    this.cardForm = fb.group({ cardNumber: ['', Validators.required], customerId: [0, Validators.required] });
    this.transactionForm = fb.group({ cardId: [0, Validators.required], amount: [0, [Validators.required, Validators.min(.01)]], date: [this.today(), Validators.required], reference: [''], note: [''] });
  }
  ngOnInit(): void { this.articles.options().subscribe((data: any) => { this.stations = data.stations.map((s: any) => ({ value: s.id, label: s.name })); this.stationId = Number(this.stations[0]?.value ?? 0); this.load(); }); }
  get totalBalance(): number { return this.cards.reduce((sum, card) => sum + Number(card.balance || 0), 0); }
  get filteredTransactions(): any[] { const cardNumber = this.cardNumberFilter.trim().toLocaleLowerCase(); return this.transactions.filter(tx => (!cardNumber || String(tx.cardNumber).toLocaleLowerCase().includes(cardNumber)) && (!this.customerFilter || String(tx.customerId ?? '') === this.customerFilter) && (!this.dateFilter || tx.date === this.dateFilter)); }
  today(): string { return new Date().toISOString().slice(0, 10); }
  load(): void { if (!this.stationId) return; this.api.list(this.stationId).subscribe({ next: data => { this.customers = data.customers ?? []; this.cards = data.cards ?? []; this.transactions = data.transactions ?? []; if (!this.customers.some((c: any) => Number(c.id) === Number(this.cardForm.value.customerId))) this.cardForm.patchValue({ customerId: this.customers[0]?.id ?? 0 }); if (!this.cards.some((c: any) => Number(c.id) === Number(this.transactionForm.value.cardId))) this.transactionForm.patchValue({ cardId: this.cards[0]?.id ?? 0 }); this.cdr.detectChanges(); }, error: (e: any) => { this.error = e.error?.message ?? 'Les données TAMANA+ ne sont pas disponibles.'; this.cdr.detectChanges(); } }); }
  createCard(): void { if (this.cardForm.invalid || this.busy) return; this.submit(this.api.createCard({ stationId: this.stationId, ...this.cardForm.value }), 'Carte créée.'); this.cardForm.reset(); }
  saveTransaction(): void { if (this.transactionForm.invalid || this.busy) return; const data = { ...this.transactionForm.value, stationId: this.stationId, type: 'TOP_UP', amount: Number(this.transactionForm.value.amount) }; this.submit(this.api.transact(data), 'Recharge enregistrée et créditée sur la carte.'); this.transactionForm.patchValue({ amount: 0, date: this.today(), reference: '', note: '' }); }
  private submit(request: any, message: string): void { this.busy = true; this.error = ''; request.subscribe({ next: () => { this.busy = false; this.success = message; this.load(); }, error: (e: any) => { this.busy = false; this.error = e.error?.message ?? 'Enregistrement impossible.'; this.cdr.detectChanges(); } }); }
  money(value: number): string { return formatMoney(value, 2); }
}
