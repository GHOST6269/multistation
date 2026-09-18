import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ArticleService } from '../../services/article.service';
import { FuelService } from '../../services/fuel.service';
import { CustomerService } from '../../services/customer.service';
import { AuthService } from '../../services/auth.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { FormArray, FormBuilder, Validators } from '@angular/forms';
import { formatMoney } from '../../shared/money-format';

@Component({ selector: 'app-fuel-sales', standalone: false, templateUrl: './fuel-sales.html', styleUrl: './fuel-sales.scss' })
export class FuelSales implements OnInit {
  readonly Math = Math;
  stations: DropdownOption[] = [];
  stationId = 0;
  readings: any[] = [];
  fromDate = '';
  toDate = '';
  page = 1;
  readonly pageSize = 10;
  loading = true;
  modalOpen = false;
  quickCustomerOpen = false;
  saving = false;
  savingCustomer = false;
  error = '';
  nozzles: any[] = [];
  attendants: any[] = [];
  paymentMethods: DropdownOption[] = [];
  customers: DropdownOption[] = [];
  creditMode = false;
  form; customerForm;
  constructor(private articles: ArticleService, private fuel: FuelService, private customersApi: CustomerService, private fb: FormBuilder, private cdr: ChangeDetectorRef, public auth: AuthService, private readonly route: ActivatedRoute) {
    this.form = fb.group({ date: [new Date().toISOString().slice(0, 10), Validators.required], nozzleId: [0, Validators.min(1)], attendantId: [0, Validators.min(1)], customerId: [0], startIndex: [0, Validators.min(0)], endIndex: [0, Validators.min(0)], returnToTank: [0, Validators.min(0)], unitPrice: [0, Validators.min(0)], payments: fb.array([]) });
    this.customerForm = fb.group({ code: [''], name: ['', Validators.required], contactPerson: [''], phone: [''] });
    if (!this.auth.hasAnyRole(['ROLE_GERANT'])) this.form.controls.unitPrice.disable({ emitEvent: false });
  }
  ngOnInit() {
    this.route.data.subscribe((data) => {
      this.creditMode = data['creditMode'] === true;
      const customerControl = this.form.get('customerId');
      if (this.creditMode) {
        customerControl?.setValidators([Validators.required, Validators.min(1)]);
      } else {
        customerControl?.clearValidators();
      }
      customerControl?.updateValueAndValidity();
      this.loadPaymentMethods();
    });
    this.articles.options().subscribe(data => { this.stations = data.stations.map(s => ({ value: s.id, label: s.name })); this.stationId = data.stations[0]?.id ?? 0; this.load(); });
  }
  load() { if (!this.stationId) return; this.loading = true; this.page = 1; this.fuel.workspace(this.stationId).subscribe({ next: data => { this.readings = data.readings ?? []; this.nozzles = data.nozzles ?? []; this.attendants = data.attendants ?? []; this.loading = false; this.cdr.detectChanges(); }, error: () => { this.readings = []; this.loading = false; } }); this.loadPaymentMethods(); this.customersApi.list(this.stationId).subscribe(data => { this.customers = [{ value: 0, label: 'Aucun client' }, ...(data.customers ?? []).filter((customer: any) => customer.active).map((customer: any) => ({ value: customer.id, label: customer.name, hint: customer.code ?? '' }))]; }); }
  private loadPaymentMethods() { this.fuel.paymentMethods(this.stationId, false, this.creditMode ? 'credit' : 'simple').subscribe(data => { this.paymentMethods = (data.methods ?? []).filter((method: any) => method.active).map((method: any) => ({ value: method.id, label: method.name, hint: method.code })); if (!this.paymentMethods.length) { this.payments.clear(); this.addPayment(); return; } const preferred = this.creditMode ? this.paymentMethods.find((method) => (method.hint ?? '').toUpperCase() === 'CLIENT_VOUCHER') ?? this.paymentMethods[0] : this.paymentMethods[0]; if (this.payments.length) { const current = this.payments.at(0)?.get('paymentMethodId')?.value; if (current === undefined || !this.paymentMethods.some((method) => Number(method.value) === Number(current))) { this.payments.at(0)?.patchValue({ paymentMethodId: Number(preferred.value) }); } } else { this.payments.clear(); this.addPayment(); } }); }
  get filtered() {
    const filteredRows = this.readings.filter((row) => {
      const matchesDate = (!this.fromDate || row.date >= this.fromDate) && (!this.toDate || row.date <= this.toDate);
      const hasCustomerVoucher = Array.isArray(row.payments) && row.payments.some((payment: any) => String(payment.type ?? '').toUpperCase() === 'CLIENT_VOUCHER');
      const customerSale = Number(row.customerId ?? 0) > 0;
      const matchesCustomer = !this.creditMode || (customerSale && (hasCustomerVoucher || (!Array.isArray(row.payments) || row.payments.length === 0)));
      return matchesDate && matchesCustomer;
    });

    const runningTotals = new Map<number, number>();
    return filteredRows
      .slice()
      .sort((a, b) => String(a.date ?? '').localeCompare(String(b.date ?? '')) || Number(a.id ?? 0) - Number(b.id ?? 0))
      .map((row) => {
        const customerId = Number(row.customerId ?? 0);
        const previousBalance = customerId > 0 ? (runningTotals.get(customerId) ?? 0) : 0;
        const amount = Number(row.totalAmount ?? 0);
        if (customerId > 0) {
          runningTotals.set(customerId, previousBalance + amount);
        }
        return {
          ...row,
          previousBalance,
          remainingToPay: customerId > 0 ? previousBalance + amount : 0,
        };
      });
  }
  get totalVolume() { return this.filtered.reduce((sum, row) => sum + Number(row.quantitySold || 0), 0); }
  get totalAmount() { return this.filtered.reduce((sum, row) => sum + Number(row.totalAmount || 0), 0); }
  get totalPages() { return Math.max(1, Math.ceil(this.filtered.length / this.pageSize)); }
  get pages() { return Array.from({ length: this.totalPages }, (_, index) => index + 1); }
  get rows() { const page = Math.min(this.page, this.totalPages); return this.filtered.slice((page - 1) * this.pageSize, page * this.pageSize); }
  resetPage() { this.page = 1; }
  get nozzleOptions(): DropdownOption[] { return this.nozzles.map(nozzle => ({ value: nozzle.id, label: `${nozzle.code} · ${nozzle.fuel ?? ''}`, hint: nozzle.tank ?? '' })); }
  get attendantOptions(): DropdownOption[] { return this.attendants.map(attendant => ({ value: attendant.id, label: attendant.name })); }
  get sold() { return Math.max(0, Number(this.form.value.endIndex) - Number(this.form.value.startIndex) - Number(this.form.value.returnToTank)); }
  get total() { return this.sold * Number(this.form.getRawValue().unitPrice); }
  get payments(): FormArray { return this.form.get('payments') as FormArray; }
  get paid() { return this.payments.controls.reduce((sum, payment) => sum + Number(payment.value.amount || 0), 0); }
  addPayment() {
    const defaultMethod = this.creditMode
      ? this.paymentMethods.find((method) => (method.hint ?? '').toUpperCase() === 'CLIENT_VOUCHER')?.value ?? this.paymentMethods[0]?.value ?? 0
      : this.paymentMethods[0]?.value ?? 0;

    this.payments.push(this.fb.group({
      paymentMethodId: [Number(defaultMethod), Validators.min(1)],
      amount: [0, this.creditMode ? Validators.min(0) : Validators.min(0.01)],
      reference: [''],
    }));
  }
  removePayment(index: number) { if (this.payments.length > 1) this.payments.removeAt(index); }
  openReading() { this.error = ''; this.form.reset({ date: new Date().toISOString().slice(0, 10), nozzleId: 0, attendantId: 0, customerId: 0, startIndex: 0, endIndex: 0, returnToTank: 0, unitPrice: 0, payments: [] }); this.payments.clear(); this.addPayment(); this.modalOpen = true; }
  openQuickCustomer() { if (this.saving) return; this.customerForm.reset({ code: '', name: '', contactPerson: '', phone: '' }); this.quickCustomerOpen = true; }
  saveQuickCustomer() { if (this.customerForm.invalid || this.savingCustomer) { this.customerForm.markAllAsTouched(); return; } this.savingCustomer = true; this.customersApi.create({ ...this.customerForm.getRawValue(), stationId: this.stationId }).subscribe({ next: customer => { const option = { value: customer.id, label: customer.name, hint: customer.code ?? '' }; this.customers = [...this.customers, option]; this.form.patchValue({ customerId: customer.id }); this.savingCustomer = false; this.quickCustomerOpen = false; this.cdr.detectChanges(); }, error: error => { this.error = error.error?.message ?? 'Le client n’a pas pu être créé.'; this.savingCustomer = false; this.cdr.detectChanges(); } }); }
  selectNozzle() { const nozzle = this.nozzles.find(item => item.id === Number(this.form.value.nozzleId)); if (nozzle) this.form.patchValue({ startIndex: nozzle.currentIndex, endIndex: nozzle.currentIndex, unitPrice: nozzle.unitPrice }); }
  save() {
    if (this.saving) return;
    this.error = this.readingError();
    if (this.form.invalid || this.error) { this.form.markAllAsTouched(); return; }
    this.saving = true;
    this.fuel.createSimpleReading({ ...this.form.getRawValue(), stationId: this.stationId }).subscribe({
      next: () => { this.saving = false; this.modalOpen = false; this.load(); },
      error: (e) => { this.error = e.error?.message ?? 'Le relevé n’a pas pu être enregistré.'; this.saving = false; this.cdr.detectChanges(); },
    });
  }
  private readingError(): string {
    if (this.creditMode && Number(this.form.value.customerId || 0) <= 0) return 'Veuillez choisir un client pour enregistrer un relevé de compte client.';
    if (this.form.invalid) return 'Veuillez renseigner tous les champs obligatoires avec des valeurs valides.';
    const start = Number(this.form.value.startIndex || 0);
    const end = Number(this.form.value.endIndex || 0);
    const returnToTank = Number(this.form.value.returnToTank || 0);
    if (end < start) return 'L’index final ne peut pas être inférieur à l’index de départ.';
    if (returnToTank > end - start) return 'Le retour cuve ne peut pas être supérieur à la sortie de pompe.';
    if (this.paid > this.total + 0.01) return `La somme des paiements ne peut pas dépasser le montant théorique (${this.money(this.total)} Ar).`;
    if (!this.creditMode && Math.abs(this.paid - this.total) > 0.01) return `La somme des paiements doit correspondre au montant théorique (${this.money(this.total)} Ar).`;
    return '';
  }
  money(value: number) { return formatMoney(value); }
}
