import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ArticleService } from '../../services/article.service';
import { CustomerService } from '../../services/customer.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { formatMoney } from '../../shared/money-format';

@Component({ selector: 'app-customers', standalone: false, templateUrl: './customers.html', styleUrl: './customers.scss' })
export class Customers implements OnInit {
  stations: DropdownOption[] = []; stationId = 0; customers: any[] = []; payments: any[] = []; loading = false; saving = false; error = '';
  editing: any = null; modalOpen = false; paymentModal = false; selectedCustomer: any = null; view: 'credit' | 'payments' = 'credit';
  paymentSearch = '';
  paymentFromDate = '';
  paymentToDate = '';
  paymentForm;
  form;
  constructor(
    private readonly articles: ArticleService,
    private readonly customersApi: CustomerService,
    fb: FormBuilder,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
    private readonly cdr: ChangeDetectorRef,
  ) {
    this.form = fb.group({ code: [''], name: ['', Validators.required], contactPerson: [''], phone: [''], email: [''], address: [''] });
    this.paymentForm = fb.group({ amount: [0, Validators.min(0)], date: [new Date().toISOString().slice(0, 10)], method: ['CASH'], reference: [''], note: [''] });
  }
  ngOnInit() {
    this.route.data.subscribe((data) => {
      this.view = data['view'] === 'payments' ? 'payments' : 'credit';
      this.load();
    });

    this.router.events.subscribe((event) => {
      if (event instanceof NavigationEnd && (event.urlAfterRedirects.startsWith('/clients') || event.urlAfterRedirects.startsWith('/fournisseurs'))) {
        this.load();
      }
    });

    this.articles.options().subscribe((data) => {
      this.stations = data.stations.map((s) => ({ value: s.id, label: s.name }));
      this.stationId = Number(this.stations[0]?.value ?? this.stationId ?? 0);
      this.load();
    });
  }
  get activeCount() { return this.customers.filter(customer => customer.active).length; }
  get inactiveCount() { return this.customers.length - this.activeCount; }
  get totalBalance() { return this.customers.reduce((sum, customer) => sum + Number(customer.balance || 0), 0); }
  get totalBilled() { return this.customers.reduce((sum, customer) => sum + Number(customer.billed || 0), 0); }
  get filteredPayments() {
    const query = this.paymentSearch.trim().toLowerCase();
    return (this.payments ?? []).filter((payment) => {
      const customerName = (payment.customer ?? '').toLowerCase();
      const matchesCustomer = !query || customerName.includes(query);
      const matchesFrom = !this.paymentFromDate || (payment.date ?? '') >= this.paymentFromDate;
      const matchesTo = !this.paymentToDate || (payment.date ?? '') <= this.paymentToDate;
      return matchesCustomer && matchesFrom && matchesTo;
    });
  }
  load() {
    if (!this.stationId) return;
    this.loading = true;
    this.customersApi.list(this.stationId).subscribe({
      next: (data) => {
        this.customers = data.customers ?? [];
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.error = 'Les clients n’ont pas pu être chargés.';
        this.loading = false;
        this.cdr.detectChanges();
      },
    });
    this.customersApi.paymentHistory(this.stationId, { from: this.paymentFromDate, to: this.paymentToDate, customer: this.paymentSearch }).subscribe({
      next: (data) => {
        this.payments = data.payments ?? [];
        this.cdr.detectChanges();
      },
      error: () => {
        this.payments = [];
        this.cdr.detectChanges();
      },
    });
  }
  open(customer?: any) { this.error = ''; this.editing = customer ?? null; this.form.reset(customer ?? { code: '', name: '', contactPerson: '', phone: '', email: '', address: '' }); this.modalOpen = true; }
  save() { if (this.form.invalid || this.saving) { this.form.markAllAsTouched(); return; } this.saving = true; const request = this.editing ? this.customersApi.update(this.editing.id, this.form.value) : this.customersApi.create({ ...this.form.value, stationId: this.stationId }); request.subscribe({ next: () => { this.saving = false; this.modalOpen = false; this.load(); }, error: e => { this.error = e.error?.message ?? 'Enregistrement impossible.'; this.saving = false; } }); }
  deactivate(customer: any) { if (!confirm(`Désactiver ${customer.name} ?`)) return; this.customersApi.deactivate(customer.id).subscribe({ next: () => this.load(), error: e => this.error = e.error?.message ?? 'Action impossible.' }); }
  openPayment(customer: any) { this.selectedCustomer = customer; this.paymentForm.reset({ amount: Math.max(0, Number(customer.balance || 0)), date: new Date().toISOString().slice(0, 10), method: 'CASH', reference: '', note: '' }); this.paymentModal = true; }
  savePayment() {
    const amount = Number(this.paymentForm.value.amount ?? 0);
    if (!this.selectedCustomer || this.paymentForm.invalid || amount < 0) {
      this.paymentForm.markAllAsTouched();
      return;
    }
    this.saving = true;
    this.customersApi.pay({ stationId: this.stationId, customerId: this.selectedCustomer.id, ...this.paymentForm.value, amount }).subscribe({
      next: () => { this.saving = false; this.paymentModal = false; this.load(); },
      error: e => { this.error = e.error?.message ?? 'Le paiement du client n’a pas pu être enregistré.'; this.saving = false; },
    });
  }
  money(value: number) { return formatMoney(value, 2); }
}
