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
  readingsPage = 1;
  paymentsPage = 1;
  readonly pageSize = 10;
  private openDeliveryFromMenu = false;
  loading = true;
  modal: 'reading' | 'delivery' | 'setup' | 'paymentMethod' | null = null;
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
      nozzleId: [0, Validators.min(1)],
      attendantId: [0, Validators.min(1)],
      startIndex: [0, Validators.min(0)],
      endIndex: [0, Validators.min(0)],
      returnToTank: [0, Validators.min(0)],
      unitPrice: [0, Validators.min(0)],
      payments: fb.array([]),
    });
    if (!this.auth.hasAnyRole(['ROLE_GERANT'])) this.readingForm.controls.unitPrice.disable({ emitEvent: false });
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
    this.paymentMethodForm = fb.group({ code: [''], name: ['', Validators.required] });
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
      nozzleId: 0,
      attendantId: 0,
      startIndex: 0,
      endIndex: 0,
      returnToTank: 0,
      unitPrice: 0,
      payments: [],
    });
    this.addPayment();
    this.modal = 'reading';
  }
  selectNozzle() {
    const n = this.data?.nozzles.find((x) => x.id === this.readingForm.value.nozzleId);
    if (n)
      this.readingForm.patchValue({
        startIndex: n.currentIndex,
        endIndex: n.currentIndex,
        unitPrice: n.unitPrice,
      });
  }
  get output() {
    return Math.max(
      0,
      Number(this.readingForm.value.endIndex) - Number(this.readingForm.value.startIndex),
    );
  }
  get sold() {
    return Math.max(0, this.output - Number(this.readingForm.value.returnToTank));
  }
  get total() {
    return this.sold * Number(this.readingForm.getRawValue().unitPrice);
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
  get nozzleOptions(): DropdownOption[] {
    return [
      { value: 0, label: 'Toutes les pompes' },
      ...this.options(this.data?.nozzles ?? [], 'code'),
    ];
  }
  get filteredReadings() {
    return (this.data?.readings ?? []).filter((reading: any) => {
      const attendant = (this.data?.attendants ?? []).find(
        (item: any) => item.name === reading.responsible,
      );
      const nozzle = (this.data?.nozzles ?? []).find((item: any) => item.code === reading.nozzle);
      return (
        (!this.fromDate || reading.date >= this.fromDate) &&
        (!this.toDate || reading.date <= this.toDate) &&
        (!this.attendantFilter || attendant?.id === Number(this.attendantFilter)) &&
        (!this.nozzleFilter || nozzle?.id === Number(this.nozzleFilter))
      );
    });
  }
  get attendantSales() {
    const rows = new Map<string, any>();
    for (const reading of this.filteredReadings) {
      const row = rows.get(reading.responsible) ?? {
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
      rows.set(reading.responsible, row);
    }
    return [...rows.values()].sort((a, b) => b.total - a.total);
  }
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
    if (this.saving || this.readingForm.invalid) return;
    if (Math.abs(this.paid - this.total) > 0.01) { this.error = `La somme des paiements doit être de ${this.money(this.total)} Ar.`; return; }
    this.saving = true;
    this.savingLabel = 'Enregistrement du relevé...';
    this.error = '';
    const v = this.readingForm.getRawValue();
    this.fuel.createSimpleReading({ ...v, stationId: this.stationId }).subscribe({
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
          this.modal = 'reading';
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
