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
  attendantFilter = 0;
  nozzleFilter = 0;
  paymentFilter: 'ALL' | 'DUE' | 'PARTIAL' | 'PAID' | 'CREDIT' = 'ALL';
  page = 1;
  readonly pageSize = 10;
  loading = true;
  modalOpen = false;
  paymentOpen = false;
  paymentHistoryOpen = false;
  quickCustomerOpen = false;
  saving = false;
  savingCustomer = false;
  selectedPaymentReading: any = null;
  paymentDate = new Date().toISOString().slice(0, 10);
  error = '';
  nozzles: any[] = [];
  attendants: any[] = [];
  paymentMethods: DropdownOption[] = [];
  customers: DropdownOption[] = [];
  creditMode = false;
  settlementView = false;
  form; customerForm; settlementForm;
  constructor(private articles: ArticleService, private fuel: FuelService, private customersApi: CustomerService, private fb: FormBuilder, private cdr: ChangeDetectorRef, public auth: AuthService, private readonly route: ActivatedRoute) {
    this.form = fb.group({ date: [new Date().toISOString().slice(0, 10), Validators.required], attendantId: [0, Validators.min(1)], nozzleId: [0, Validators.min(1)], startIndex: [0, Validators.min(0)], endIndex: [0, Validators.min(0)], returnToTank: [0, Validators.min(0)], unitPrice: [0, Validators.min(0)] });
    this.settlementForm = fb.group({ attendantId: [0], nozzleId: [0], customerId: [0], payments: fb.array([]) });
    this.customerForm = fb.group({ code: [''], name: ['', Validators.required], contactPerson: [''], phone: [''] });
    if (!this.auth.hasAnyRole(['ROLE_GERANT'])) this.form.controls.unitPrice.disable({ emitEvent: false });
  }
  ngOnInit() {
    this.route.data.subscribe((data) => {
      this.creditMode = data['creditMode'] === true;
      this.settlementView = data['settlementView'] === true;
      if (this.settlementView) this.paymentFilter = 'DUE';
      this.loadPaymentMethods();
    });
    this.articles.options().subscribe(data => { this.stations = data.stations.map(s => ({ value: s.id, label: s.name })); this.stationId = data.stations[0]?.id ?? 0; this.load(); });
  }
  load() { if (!this.stationId) return; this.loading = true; this.page = 1; this.fuel.workspace(this.stationId).subscribe({ next: data => { this.readings = data.readings ?? []; this.nozzles = data.nozzles ?? []; this.attendants = data.attendants ?? []; this.loading = false; this.cdr.detectChanges(); }, error: () => { this.readings = []; this.loading = false; } }); this.loadPaymentMethods(); this.customersApi.list(this.stationId).subscribe(data => { this.customers = [{ value: 0, label: 'Aucun client' }, ...(data.customers ?? []).filter((customer: any) => customer.active).map((customer: any) => ({ value: customer.id, label: customer.name, hint: customer.code ?? '' }))]; }); }
  private loadPaymentMethods() { this.fuel.paymentMethods(this.stationId, false, this.creditMode ? 'credit' : 'simple').subscribe(data => { this.paymentMethods = (data.methods ?? []).filter((method: any) => method.active).map((method: any) => ({ value: method.id, label: method.name, hint: method.code })); if (!this.paymentMethods.length) { this.payments.clear(); this.addPayment(); return; } const preferred = this.creditMode ? this.paymentMethods.find((method) => (method.hint ?? '').toUpperCase() === 'CLIENT_VOUCHER') ?? this.paymentMethods[0] : this.paymentMethods[0]; if (this.payments.length) { const current = this.payments.at(0)?.get('paymentMethodId')?.value; if (current === undefined || !this.paymentMethods.some((method) => Number(method.value) === Number(current))) { this.payments.at(0)?.patchValue({ paymentMethodId: Number(preferred.value) }); } } else { this.payments.clear(); this.addPayment(); } }); }
  get attendantOptions(): DropdownOption[] {
    return [{ value: 0, label: 'Tous les pompistes' }, ...this.attendants.map(attendant => ({ value: attendant.id, label: attendant.name }))];
  }
  get paymentFilterOptions(): DropdownOption[] {
    return this.creditMode
      ? [{ value: 'ALL', label: 'Tous les versements' }, { value: 'CREDIT', label: 'Compte client' }]
      : [
          { value: 'ALL', label: 'Tous les versements' },
          { value: 'DUE', label: 'Reste à verser' },
          { value: 'PARTIAL', label: 'Versement partiel' },
          { value: 'PAID', label: 'Soldés' },
        ];
  }
  get filtered() {
    const filteredRows = this.readings.filter((row) => {
      const matchesDate = (!this.fromDate || row.date >= this.fromDate) && (!this.toDate || row.date <= this.toDate);
      const hasCustomerVoucher = Array.isArray(row.payments) && row.payments.some((payment: any) => String(payment.type ?? '').toUpperCase() === 'CLIENT_VOUCHER');
      const customerSale = Number(row.customerId ?? 0) > 0;
      const matchesCustomer = !this.creditMode || (customerSale && (hasCustomerVoucher || (!Array.isArray(row.payments) || row.payments.length === 0)));
      const nozzle = this.nozzles.find((item) => item.code === row.nozzle);
      const matchesAttendant = !this.attendantFilter || Number(nozzle?.attendantId ?? 0) === Number(this.attendantFilter);
      const matchesNozzle = !this.nozzleFilter || Number(nozzle?.id ?? 0) === Number(this.nozzleFilter);
      const due = this.canCollect(row);
      const matchesPayment = this.paymentFilter === 'ALL' ||
        (this.paymentFilter === 'DUE' && due) ||
        (this.paymentFilter === 'PARTIAL' && due && this.amountPaid(row) > 0) ||
        (this.paymentFilter === 'PAID' && !due && row.paymentStatus !== 'CUSTOMER_CREDIT') ||
        (this.paymentFilter === 'CREDIT' && row.paymentStatus === 'CUSTOMER_CREDIT');
      return matchesDate && matchesCustomer && matchesAttendant && matchesNozzle && matchesPayment;
    });

    const runningTotals = new Map<number, number>();
    return filteredRows
      .slice()
      .sort((a, b) => String(a.date ?? '').localeCompare(String(b.date ?? '')) || Number(a.id ?? 0) - Number(b.id ?? 0))
      .map((row) => {
        const customerId = Number(row.customerId ?? 0);
        const previousBalance = customerId > 0 ? (runningTotals.get(customerId) ?? 0) : 0;
        const settled = (row.payments ?? []).reduce((sum: number, payment: any) => sum + Number(payment.amount ?? 0), 0);
        const amount = Math.max(0, Number(row.totalAmount ?? 0) - settled);
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
  get totalGap() { return this.filtered.reduce((sum, row) => sum + Math.max(0, this.paymentGap(row)), 0); }
  get totalPages() { return Math.max(1, Math.ceil(this.filtered.length / this.pageSize)); }
  get pages() { return Array.from({ length: this.totalPages }, (_, index) => index + 1); }
  get rows() { const page = Math.min(this.page, this.totalPages); return this.filtered.slice((page - 1) * this.pageSize, page * this.pageSize); }
  resetPage() { this.page = 1; }
  get nozzleOptions(): DropdownOption[] { return this.nozzles.map(nozzle => ({ value: nozzle.id, label: `${nozzle.code} · ${nozzle.fuel ?? ''}`, hint: nozzle.tank ?? '' })); }
  get readingAttendantOptions(): DropdownOption[] { return this.attendants.map((attendant) => ({ value: attendant.id, label: attendant.name, hint: attendant.code ?? '' })); }
  get settlementAttendantOptions(): DropdownOption[] { return this.readingAttendantOptions; }
  get settlementReadings(): any[] { const attendant = this.attendants.find(item => Number(item.id) === Number(this.settlementForm.value.attendantId)); const customerId = Number(this.settlementForm.value.customerId || 0); return this.readings.filter(row => attendant && row.responsible === attendant.name && (!customerId || !row.customerId || Number(row.customerId) === customerId) && this.canCollect(row)); }
  get settlementTotalDue(): number { return this.settlementReadings.reduce((sum, row) => sum + this.amountRemaining(row), 0); }
  settlementAttendantChanged() { this.selectedPaymentReading = null; this.payments.clear(); if (this.settlementForm.value.attendantId) this.addPayment(); }
  openSettlementModal() { this.error = ''; this.selectedPaymentReading = null; this.settlementForm.reset({ attendantId: 0, nozzleId: 0, customerId: 0 }); this.payments.clear(); this.paymentDate = new Date().toISOString().slice(0, 10); this.paymentOpen = true; }
  get filterNozzleOptions(): DropdownOption[] { return this.nozzles.filter(nozzle => !this.attendantFilter || Number(nozzle.attendantId) === Number(this.attendantFilter)).map(nozzle => ({ value: nozzle.id, label: `${nozzle.code} · ${nozzle.fuel ?? ''}`, hint: nozzle.tank ?? '' })); }
  get assignedNozzles(): any[] { return this.nozzles.filter(nozzle => Number(nozzle.attendantId) === Number(this.form.value.attendantId)); }
  attendantChanged() { this.form.patchValue({ nozzleId: 0, startIndex: 0, endIndex: 0, unitPrice: 0 }); }
  get assignedNozzleOptions(): DropdownOption[] { return this.assignedNozzles.map(nozzle => ({ value: nozzle.id, label: `${nozzle.code} · ${nozzle.fuel ?? ''}`, hint: nozzle.tank ?? '' })); }
  get selectedNozzleAttendant(): string { return this.nozzles.find(nozzle => Number(nozzle.id) === Number(this.form.value.nozzleId))?.attendant ?? 'Aucun pompiste associé'; }
  get sold() { return Math.max(0, Number(this.form.value.endIndex) - Number(this.form.value.startIndex) - Number(this.form.value.returnToTank)); }
  get total() { return this.sold * Number(this.form.getRawValue().unitPrice); }
  get payments(): FormArray { return this.settlementForm.get('payments') as FormArray; }
  get paid() { return this.payments.controls.reduce((sum, payment) => sum + Number(payment.value.amount || 0), 0); }
  addPayment() {
    const initialAmount = this.payments.length === 0 && this.selectedPaymentReading
      ? this.amountRemaining(this.selectedPaymentReading)
      : 0;
    const defaultMethod = this.creditMode
      ? this.paymentMethods.find((method) => (method.hint ?? '').toUpperCase() === 'CLIENT_VOUCHER')?.value ?? this.paymentMethods[0]?.value ?? 0
      : this.paymentMethods[0]?.value ?? 0;

    this.payments.push(this.fb.group({
      paymentMethodId: [Number(defaultMethod), Validators.min(1)],
      amount: [initialAmount, Validators.min(0.01)],
      reference: [''],
    }));
  }
  removePayment(index: number) { if (this.payments.length > 1) this.payments.removeAt(index); }
  openReading() { this.error = ''; this.form.reset({ date: new Date().toISOString().slice(0, 10), attendantId: 0, nozzleId: 0, startIndex: 0, endIndex: 0, returnToTank: 0, unitPrice: 0 }); this.modalOpen = true; }
  openQuickCustomer() { if (this.saving) return; this.customerForm.reset({ code: '', name: '', contactPerson: '', phone: '' }); this.quickCustomerOpen = true; }
  saveQuickCustomer() { if (this.customerForm.invalid || this.savingCustomer) { this.customerForm.markAllAsTouched(); return; } this.savingCustomer = true; this.customersApi.create({ ...this.customerForm.getRawValue(), stationId: this.stationId }).subscribe({ next: customer => { const option = { value: customer.id, label: customer.name, hint: customer.code ?? '' }; this.customers = [...this.customers, option]; this.settlementForm.patchValue({ customerId: customer.id }); this.savingCustomer = false; this.quickCustomerOpen = false; this.cdr.detectChanges(); }, error: error => { this.error = error.error?.message ?? 'Le client n’a pas pu être créé.'; this.savingCustomer = false; this.cdr.detectChanges(); } }); }
  selectNozzle() { const nozzle = this.nozzles.find(item => item.id === Number(this.form.value.nozzleId)); if (nozzle) this.form.patchValue({ startIndex: nozzle.currentIndex, endIndex: nozzle.currentIndex, unitPrice: nozzle.unitPrice }); }
  save() {
    if (this.saving) return;
    this.error = this.readingError();
    if (this.form.invalid || this.error) { this.form.markAllAsTouched(); return; }
    this.saving = true;
    this.fuel.createSimpleReading({ ...this.form.getRawValue(), stationId: this.stationId, creditMode: false }).subscribe({
      next: () => { this.saving = false; this.modalOpen = false; this.load(); },
      error: (e) => { this.error = e.error?.message ?? 'Le relevé n’a pas pu être enregistré.'; this.saving = false; this.cdr.detectChanges(); },
    });
  }
  private readingError(): string {
    if (this.form.invalid) return 'Veuillez renseigner tous les champs obligatoires avec des valeurs valides.';
    const start = Number(this.form.value.startIndex || 0);
    const end = Number(this.form.value.endIndex || 0);
    const returnToTank = Number(this.form.value.returnToTank || 0);
    if (end < start) return 'L’index final ne peut pas être inférieur à l’index de départ.';
    if (returnToTank > end - start) return 'Le retour cuve ne peut pas être supérieur à la sortie de pompe.';
    return '';
  }
  amountPaid(reading: any): number { return (reading.payments ?? []).reduce((sum: number, payment: any) => sum + Number(payment.amount ?? 0), 0); }
  amountRemaining(reading: any): number { return Math.max(0, Number(reading.totalAmount ?? 0) - this.amountPaid(reading)); }
  canCollect(reading: any): boolean { return !this.creditMode && reading.paymentStatus !== 'CUSTOMER_CREDIT' && this.amountRemaining(reading) > 0.01; }
  paymentState(reading: any): string { if (reading.paymentStatus === 'CUSTOMER_CREDIT') return 'Compte client'; const remaining = this.amountRemaining(reading); return remaining <= 0.01 ? 'Versé' : (this.amountPaid(reading) > 0 ? 'Partiel' : 'À verser'); }
  openPaymentEntry(reading: any) {
    if (!this.canCollect(reading)) return;
    this.selectedPaymentReading = reading;
    this.paymentDate = new Date().toISOString().slice(0, 10);
    this.payments.clear();
    this.addPayment();
    this.error = '';
    this.paymentOpen = true;
  }
  openPaymentHistory(reading: any) { this.selectedPaymentReading = reading; this.paymentHistoryOpen = true; }
  get priorPayments(): any[] { return this.selectedPaymentReading?.payments ?? []; }
  paymentGap(reading: any): number { return Number(reading?.totalAmount ?? 0) - this.amountPaid(reading); }
  get paymentEntryAmount(): number { return this.paid; }
  get paymentEntryGap(): number { return this.settlementTotalDue - this.paymentEntryAmount; }
  savePaymentEntry() {
    if (this.saving || this.payments.invalid || !this.settlementForm.value.attendantId) return;
    if (this.paymentEntryAmount <= 0 || this.paymentEntryAmount > this.settlementTotalDue + .01) { this.error = 'Le versement doit être positif et ne peut pas dépasser le total restant dû.'; return; }
    this.saving = true;
    this.error = '';
    this.fuel.addAttendantSettlement({ stationId: this.stationId, attendantId: this.settlementForm.value.attendantId, customerId: this.settlementForm.value.customerId, date: this.paymentDate, payments: this.payments.getRawValue() }).subscribe({
      next: () => {
        this.saving = false;
        this.paymentOpen = false;
        this.paymentHistoryOpen = false;
        this.selectedPaymentReading = null;
        this.cdr.detectChanges();
        this.load();
      },
      error: (e) => { this.error = e.error?.message ?? 'Le versement n’a pas pu être enregistré.'; this.saving = false; this.cdr.detectChanges(); },
    });
  }
  money(value: number) { return formatMoney(value); }
}
