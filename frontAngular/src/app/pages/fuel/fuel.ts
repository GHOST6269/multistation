import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, Validators } from '@angular/forms';
import { FuelWorkspace } from '../../models/fuel.model';
import { ArticleService } from '../../services/article.service';
import { FuelService } from '../../services/fuel.service';
import { SupplierService } from '../../services/supplier.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { ActivatedRoute } from '@angular/router';
import { formatMoney } from '../../shared/money-format';
import { AuthService } from '../../services/auth.service';
@Component({
  selector: 'app-fuel',
  standalone: false,
  templateUrl: './fuel.html',
  styleUrl: './fuel.scss',
})
export class Fuel implements OnInit {
  readonly Math = Math;
  data?: FuelWorkspace;
  stations: DropdownOption[] = [];
  suppliers: DropdownOption[] = [];
  paymentMethods: DropdownOption[] = [];
  paymentHistory: any[] = [];
  stationId = 0;
  salesView: 'journal' | 'attendants' = 'attendants';
  fromDate = '';
  toDate = '';
  paymentFromDate = '';
  paymentToDate = '';
  attendantFilter = 0;
  nozzleFilter = 0;
  paymentFilter: 'ALL' | 'DUE' | 'PARTIAL' | 'PAID' | 'CREDIT' = 'ALL';
  readingsPage = 1;
  paymentsPage = 1;
  readonly pageSize = 10;
  private openDeliveryFromMenu = false;
  loading = true;
  modal: 'reading' | 'paymentEntry' | 'delivery' | 'setup' | 'paymentMethod' | null = null;
  selectedPaymentReading: any = null;
  attendantPaymentsOpen = false;
  attendantPaymentFilter = 0;
  attendantPaymentFrom = '';
  attendantPaymentTo = '';
  paymentDate = new Date().toISOString().slice(0, 10);
  paymentMethodReturn: 'reading' | 'paymentEntry' = 'reading';
  saving = false;
  error = '';
  setupType = 'FUEL';
  savingLabel = 'Traitement en cours...';
  readingForm;
  deliveryForm;
  setupForm;
  paymentMethodForm;
  paymentLabels: Record<string, string> = {
    CASH: 'Espèces',
    CHEQUE: 'Chèque',
    TPE: 'Carte TPE',
    FANILO: 'Carte FANILO',
    VISA: 'Carte Visa',
    FMS: 'FMS',
    CLIENT_VOUCHER: 'Bons clients',
    STATION_OPERATION: 'Fonctionnement station',
  };
  constructor(
    private fuel: FuelService,
    private supplierService: SupplierService,
    private articles: ArticleService,
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private route: ActivatedRoute,
    public readonly auth: AuthService,
  ) {
    this.readingForm = fb.group({
      date: [new Date().toISOString().slice(0, 10), Validators.required],
      attendantId: [0, Validators.min(1)],
      readings: fb.array([]),
      payments: fb.array([]),
    });
    this.deliveryForm = fb.group({
      date: [new Date().toISOString().slice(0, 10), Validators.required],
      tankId: [0, Validators.min(1)],
      supplierId: [0, Validators.min(1)],
      invoiceNumber: [''],
      dueDate: [''],
      quantity: [0, Validators.min(0.001)],
      unitCost: [0, Validators.min(0)],
    });
    this.setupForm = fb.group({
      code: ['', Validators.required],
      name: ['', Validators.required],
      fuelTypeId: [0],
      capacity: [0],
      currentStock: [0],
      minimumStock: [0],
      pumpId: [0],
      tankId: [0],
      currentIndex: [0],
      unitPrice: [0],
      contact: [''],
    });
    this.paymentMethodForm = fb.group({ code: [''], name: ['', Validators.required], supplierDeduction: [false] });
  }
  ngOnInit() {
    this.route.queryParams.subscribe((params) => {
      this.openDeliveryFromMenu = params['action'] === 'delivery';
      if (params['view'] === 'journal') this.salesView = 'journal';
      if (params['action'] === 'reading') this.modal = 'reading';
    });
    this.articles.options().subscribe((x) => {
      this.stations = x.stations.map((s) => ({ value: s.id, label: s.name }));
      this.stationId = x.stations[0]?.id ?? 0;
      this.load();
    });
  }
  load() {
    if (!this.stationId) return;
    this.loading = true;
    this.supplierService.list(this.stationId).subscribe((x) => {
      this.suppliers = (x.suppliers ?? [])
        .filter((supplier: any) => supplier.active)
        .map((supplier: any) => ({
          value: supplier.id,
          label: supplier.name,
          hint: supplier.code,
        }));
      this.cdr.detectChanges();
    });
    this.loadPaymentData();
    this.fuel.workspace(this.stationId).subscribe((x) => {
      this.data = x;
      this.loading = false;
      if (this.openDeliveryFromMenu) {
        this.openDeliveryFromMenu = false;
        this.openDelivery();
      }
      this.cdr.detectChanges();
    });
  }
  loadPaymentData() {
    this.fuel.paymentMethods(this.stationId).subscribe((x) => {
      this.paymentMethods = (x.methods ?? [])
        .filter((method: any) => method.active)
        .map((method: any) => ({ value: method.id, label: method.name, hint: method.code }));
      this.cdr.detectChanges();
    });
    this.fuel.paymentHistory(this.stationId).subscribe((x) => {
      this.paymentHistory = x.payments ?? [];
      this.cdr.detectChanges();
    });
  }
  options(items: any[], label = 'name'): DropdownOption[] {
    return (items ?? []).map((x) => ({
      value: x.id,
      label: x[label],
      hint: x.fuel ?? x.code ?? '',
    }));
  }
  openReading() {
    this.error = '';
    this.payments.clear();
    this.readingForm.reset({
      date: new Date().toISOString().slice(0, 10),
      attendantId: 0,
      readings: [],
      payments: [],
    });
    this.readings.clear();
    this.modal = 'reading';
  }
  get readings(): FormArray { return this.readingForm.get('readings') as FormArray; }
  get assignedReadingNozzles(): any[] { return (this.data?.nozzles ?? []).filter(n => Number(n.attendantId) === Number(this.readingForm.value.attendantId)); }
  selectAttendant() {
    this.readings.clear();
    for (const nozzle of this.assignedReadingNozzles) {
      const row = this.fb.group({ nozzleId: [nozzle.id], startIndex: [nozzle.currentIndex, [Validators.required, Validators.min(0)]], endIndex: [nozzle.currentIndex, [Validators.required, Validators.min(0)]], returnToTank: [0, Validators.min(0)], unitPrice: [nozzle.unitPrice, Validators.min(0)] });
      if (!this.auth.hasAnyRole(['ROLE_GERANT'])) row.controls.unitPrice.disable({ emitEvent: false });
      this.readings.push(row);
    }
  }
  get paid() {
    return this.payments.controls.reduce((sum, payment) => sum + Number(payment.value.amount || 0), 0);
  }
  get payments(): FormArray { return this.readingForm.get('payments') as FormArray; }
  addPayment() {
    this.payments.push(this.fb.group({
      paymentMethodId: [this.paymentMethods[0]?.value != null ? Number(this.paymentMethods[0].value) : 0, Validators.min(1)],
      amount: [0, Validators.min(0.01)], reference: [''],
    }));
  }
  removePayment(index: number) { if (this.payments.length > 1) this.payments.removeAt(index); }
  amountPaid(reading: any): number { return (reading.payments ?? []).reduce((sum: number, payment: any) => sum + Number(payment.amount ?? 0), 0); }
  amountRemaining(reading: any): number { return Math.max(0, Number(reading.totalAmount ?? 0) - this.amountPaid(reading)); }
  canCollect(reading: any): boolean { return reading.paymentStatus !== 'CUSTOMER_CREDIT' && this.amountRemaining(reading) > 0.01; }
  paymentState(reading: any): string { if (reading.paymentStatus === 'CUSTOMER_CREDIT') return 'Compte client'; const remaining = this.amountRemaining(reading); return remaining <= 0.01 ? 'Versé' : (this.amountPaid(reading) > 0 ? 'Partiel' : 'À verser'); }
  openPaymentEntry(reading: any) {
    this.selectedPaymentReading = reading;
    this.paymentDate = new Date().toISOString().slice(0, 10);
    this.payments.clear();
    this.addPayment();
    this.payments.at(0)?.patchValue({ amount: this.amountRemaining(reading) });
    this.error = '';
    this.modal = 'paymentEntry';
  }
  get paymentEntryAmount(): number { return this.paid; }
  get paymentEntryGap(): number { return this.amountRemaining(this.selectedPaymentReading) - this.paymentEntryAmount; }
  savePaymentEntry() {
    if (!this.selectedPaymentReading || this.saving || !this.payments.length || this.payments.invalid) return;
    if (this.paymentEntryAmount <= 0 || this.paymentEntryAmount > this.amountRemaining(this.selectedPaymentReading) + 0.01) {
      this.error = 'Le versement doit être positif et ne peut pas dépasser le reste dû.';
      return;
    }
    this.saving = true;
    this.error = '';
    this.fuel.addReadingPayments(this.selectedPaymentReading.id, { date: this.paymentDate, payments: this.payments.getRawValue() }).subscribe({
      next: () => { this.saving = false; this.modal = null; this.selectedPaymentReading = null; this.load(); },
      error: (e) => { this.error = e.error?.message ?? 'Le versement n’a pas pu être enregistré.'; this.saving = false; this.cdr.detectChanges(); },
    });
  }
  get totalTankStock() {
    return (this.data?.tanks ?? []).reduce((total, tank) => total + Number(tank.stock || 0), 0);
  }
  get criticalTanks() {
    return (this.data?.tanks ?? []).filter((tank) => Number(tank.stock) <= Number(tank.minimum))
      .length;
  }
  get totalSales() {
    return (this.data?.readings ?? []).reduce(
      (total, reading) => total + Number(reading.totalAmount || 0),
      0,
    );
  }
  get totalVolumeSold() {
    return (this.data?.readings ?? []).reduce(
      (total, reading) => total + Number(reading.quantitySold || 0),
      0,
    );
  }
  get attendantOptions(): DropdownOption[] {
    return [
      { value: 0, label: 'Tous les pompistes' },
      ...this.options(this.data?.attendants ?? []),
    ];
  }
  get attendantPaymentOptions(): DropdownOption[] {
    const options = new Map<number, DropdownOption>();
    for (const attendant of this.data?.attendants ?? []) options.set(Number(attendant.id), { value: Number(attendant.id), label: attendant.name });
    for (const reading of this.data?.readings ?? []) {
      const id = Number(reading.attendantId ?? 0);
      if (id && !options.has(id)) options.set(id, { value: id, label: reading.responsible || `Pompiste ${id}` });
    }
    return [{ value: 0, label: 'Tous les pompistes' }, ...[...options.values()].sort((a, b) => a.label.localeCompare(b.label))];
  }
  get nozzleOptions(): DropdownOption[] {
    return [
      { value: 0, label: 'Toutes les pompes' },
      ...this.options(this.data?.nozzles ?? [], 'code'),
    ];
  }
  get paymentFilterOptions(): DropdownOption[] {
    return [
      { value: 'ALL', label: 'Tous les versements' },
      { value: 'DUE', label: 'Reste à verser' },
      { value: 'PARTIAL', label: 'Versement partiel' },
      { value: 'PAID', label: 'Soldés' },
      { value: 'CREDIT', label: 'Compte client' },
    ];
  }
  get filteredReadings() {
    return (this.data?.readings ?? []).filter((reading: any) => {
      const nozzle = (this.data?.nozzles ?? []).find((item: any) => item.code === reading.nozzle);
      const due = this.canCollect(reading);
      const matchesPayment = this.paymentFilter === 'ALL' ||
        (this.paymentFilter === 'DUE' && due) ||
        (this.paymentFilter === 'PARTIAL' && due && this.amountPaid(reading) > 0) ||
        (this.paymentFilter === 'PAID' && !due && reading.paymentStatus !== 'CUSTOMER_CREDIT') ||
        (this.paymentFilter === 'CREDIT' && reading.paymentStatus === 'CUSTOMER_CREDIT');
      return (
        (!this.fromDate || reading.date >= this.fromDate) &&
        (!this.toDate || reading.date <= this.toDate) &&
        (!this.attendantFilter || Number(reading.attendantId ?? 0) === Number(this.attendantFilter)) &&
        (!this.nozzleFilter || nozzle?.id === Number(this.nozzleFilter)) &&
        matchesPayment
      );
    });
  }
  get attendantSales() {
    const rows = new Map<number, any>();
    for (const reading of this.filteredReadings) {
      const attendantId = Number(reading.attendantId ?? 0);
      const row = rows.get(attendantId) ?? {
        id: attendantId,
        name: reading.responsible || 'Non renseigné',
        nozzle: reading.nozzle,
        transactions: 0,
        sp: 0,
        go: 0,
        pl: 0,
        volume: 0,
        total: 0,
      };
      const fuel = String(reading.fuel || '').toUpperCase();
      if (fuel === 'SP') row.sp += Number(reading.quantitySold || 0);
      else if (fuel === 'GO') row.go += Number(reading.quantitySold || 0);
      else if (fuel === 'PL') row.pl += Number(reading.quantitySold || 0);
      row.transactions += 1;
      row.volume += Number(reading.quantitySold || 0);
      row.total += Number(reading.totalAmount || 0);
      rows.set(attendantId, row);
    }
    return [...rows.values()].sort((a, b) => b.total - a.total);
  }
  openAttendantPayments() { this.attendantPaymentFilter = 0; this.attendantPaymentFrom = ''; this.attendantPaymentTo = ''; this.attendantPaymentsOpen = true; }
  get attendantPaymentRows(): any[] {
    const rows: any[] = [];
    for (const reading of this.data?.readings ?? []) {
      if (this.attendantPaymentFilter && Number(reading.attendantId ?? 0) !== Number(this.attendantPaymentFilter)) continue;
      const lines: { label?: string; type?: string; amount: number; date?: string }[] = reading.payments?.length ? reading.payments : [{ label: 'Aucun versement', amount: 0 }];
      for (const payment of lines) {
        const date = payment.date || reading.date || '';
        if ((this.attendantPaymentFrom && date < this.attendantPaymentFrom) || (this.attendantPaymentTo && date > this.attendantPaymentTo)) continue;
        rows.push({ id: `${reading.id}-${rows.length}`, readingId: reading.id, date, invoiceNumber: reading.invoiceNumber, nozzle: reading.nozzle, responsible: reading.responsible, method: payment.label || payment.type || 'Aucun versement', amount: Number(payment.amount ?? 0), gap: this.amountRemaining(reading) });
      }
    }
    return rows.sort((a, b) => String(b.date).localeCompare(String(a.date)));
  }
  get attendantPaymentGapTotal(): number {
    const readings = new Map<string, number>();
    for (const row of this.attendantPaymentRows) readings.set(String(row.readingId), row.gap);
    return [...readings.values()].reduce((sum, gap) => sum + gap, 0);
  }
  get attendantPaymentAmountTotal(): number { return this.attendantPaymentRows.reduce((sum, row) => sum + Number(row.amount || 0), 0); }
  get filteredPaymentHistory() {
    return this.paymentHistory.filter(
      (payment) =>
        (!this.paymentFromDate || payment.date >= this.paymentFromDate) &&
        (!this.paymentToDate || payment.date <= this.paymentToDate),
    );
  }
  get salesTotalPages() {
    const total = this.salesView === 'attendants' ? this.attendantSales.length : this.filteredReadings.length;
    return Math.max(1, Math.ceil(total / this.pageSize));
  }
  get paymentTotalPages() {
    return Math.max(1, Math.ceil(this.filteredPaymentHistory.length / this.pageSize));
  }
  get salesPages() { return Array.from({ length: this.salesTotalPages }, (_, index) => index + 1); }
  get paymentPages() { return Array.from({ length: this.paymentTotalPages }, (_, index) => index + 1); }
  get pagedReadings() {
    const page = Math.min(this.readingsPage, this.salesTotalPages);
    return this.filteredReadings.slice((page - 1) * this.pageSize, page * this.pageSize);
  }
  get pagedAttendantSales() {
    const page = Math.min(this.readingsPage, this.salesTotalPages);
    return this.attendantSales.slice((page - 1) * this.pageSize, page * this.pageSize);
  }
  get pagedPayments() {
    const page = Math.min(this.paymentsPage, this.paymentTotalPages);
    return this.filteredPaymentHistory.slice((page - 1) * this.pageSize, page * this.pageSize);
  }
  resetSalesPage() { this.readingsPage = 1; }
  resetPaymentsPage() { this.paymentsPage = 1; }
  saveReading() {
    if (this.saving || this.readingForm.invalid || !this.readings.length || this.readings.invalid) return;
    for (const control of this.readings.controls) { const v = control.value; if (Number(v.endIndex) < Number(v.startIndex) || Number(v.returnToTank) > Number(v.endIndex) - Number(v.startIndex)) { this.error = 'Vérifiez les index et le retour cuve de chaque pistolet.'; return; } }
    this.saving = true;
    this.savingLabel = 'Enregistrement du relevé...';
    this.error = '';
    const v = this.readingForm.getRawValue();
    this.fuel.createSimpleReading({ stationId: this.stationId, date: v.date, attendantId: v.attendantId, readings: this.readings.getRawValue() }).subscribe({
      next: (r) => {
        if (this.data) this.data.readings = [r, ...this.data.readings];
        this.modal = null;
        this.saving = false;
        this.load();
      },
      error: (e) => {
        this.error = e.error?.message ?? 'Enregistrement impossible';
        this.saving = false;
        this.cdr.detectChanges();
      },
    });
  }
  openPaymentMethod() {
    this.paymentMethodReturn = this.modal === 'paymentEntry' ? 'paymentEntry' : 'reading';
    this.paymentMethodForm.reset();
    this.modal = 'paymentMethod';
  }
  savePaymentMethod() {
    if (this.saving || this.paymentMethodForm.invalid) return;
    this.saving = true;
    this.savingLabel = 'Création du mode de paiement...';
    this.fuel
      .createPaymentMethod({ ...this.paymentMethodForm.getRawValue(), stationId: this.stationId })
      .subscribe({
        next: (method) => {
          this.saving = false;
          this.loadPaymentData();
          this.payments.at(0)?.patchValue({ paymentMethodId: method.id });
          this.modal = this.paymentMethodReturn;
        },
        error: (e) => {
          this.error = e.error?.message ?? 'Création impossible';
          this.saving = false;
          this.cdr.detectChanges();
        },
      });
  }
  openDelivery() {
    this.error = '';
    this.deliveryForm.reset({
      date: new Date().toISOString().slice(0, 10),
      tankId: 0,
      supplierId: 0,
      invoiceNumber: '',
      dueDate: '',
      quantity: 0,
      unitCost: 0,
    });
    this.modal = 'delivery';
  }
  saveDelivery() {
    if (this.saving || this.deliveryForm.invalid) return;
    this.saving = true;
    this.savingLabel = 'Enregistrement de la livraison...';
    this.supplierService
      .delivery({ ...this.deliveryForm.getRawValue(), stationId: this.stationId })
      .subscribe({
        next: () => {
          this.modal = null;
          this.saving = false;
          this.load();
        },
        error: (e) => {
          this.error = e.error?.message ?? 'Livraison impossible';
          this.saving = false;
          this.cdr.detectChanges();
        },
      });
  }
  openSetup() {
    this.setupType = 'FUEL';
    this.error = '';
    this.setupForm.reset();
    this.modal = 'setup';
  }
  changeSetupType(type: string) {
    if (this.saving) return;
    this.setupType = type;
    this.error = '';
    this.setupForm.reset({
      code: '',
      name: '',
      fuelTypeId: 0,
      capacity: 0,
      currentStock: 0,
      minimumStock: 0,
      pumpId: 0,
      tankId: 0,
      currentIndex: 0,
      unitPrice: 0,
      contact: '',
    });
  }
  closeModal() {
    if (!this.saving) this.modal = null;
  }
  saveSetup() {
    if (this.saving || this.setupForm.invalid) return;
    const value = this.setupForm.getRawValue();
    if (this.setupType === 'TANK' && !Number(value.fuelTypeId)) {
      this.error = 'Choisissez le carburant de la cuve.';
      return;
    }
    if (this.setupType === 'NOZZLE' && (!Number(value.pumpId) || !Number(value.tankId))) {
      this.error = 'Choisissez la pompe et la cuve du pistolet.';
      return;
    }
    this.saving = true;
    this.savingLabel = 'Mise à jour de la configuration...';
    this.fuel.setup({ ...value, stationId: this.stationId, type: this.setupType }).subscribe({
      next: (created) => {
        if (this.setupType === 'FUEL' && created?.id) {
          this.fuel.update('fuel', created.id, value).subscribe({
            next: () => this.finishSetup(),
            error: (e) => {
              this.error = e.error?.message ?? 'Enregistrement du prix impossible';
              this.saving = false;
              this.cdr.detectChanges();
            },
          });
          return;
        }
        this.finishSetup();
      },
      error: (e) => {
        this.error = e.error?.message ?? 'Création impossible';
        this.saving = false;
        this.cdr.detectChanges();
      },
    });
  }
  finishSetup() {
    this.saving = false;
    this.setupForm.reset();
    this.load();
    this.cdr.detectChanges();
  }
  money(v: number) {
    return formatMoney(v);
  }
}
