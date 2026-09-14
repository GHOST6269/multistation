import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ArticleService } from '../../services/article.service';
import { FuelService } from '../../services/fuel.service';
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
  saving = false;
  error = '';
  nozzles: any[] = [];
  attendants: any[] = [];
  paymentMethods: DropdownOption[] = [];
  form;
  constructor(private articles: ArticleService, private fuel: FuelService, private fb: FormBuilder, private cdr: ChangeDetectorRef, public auth: AuthService) {
    this.form = fb.group({ date: [new Date().toISOString().slice(0, 10), Validators.required], nozzleId: [0, Validators.min(1)], attendantId: [0, Validators.min(1)], startIndex: [0, Validators.min(0)], endIndex: [0, Validators.min(0)], returnToTank: [0, Validators.min(0)], unitPrice: [0, Validators.min(0)], payments: fb.array([]) });
    if (!this.auth.hasAnyRole(['ROLE_GERANT'])) this.form.controls.unitPrice.disable({ emitEvent: false });
  }
  ngOnInit() { this.articles.options().subscribe(data => { this.stations = data.stations.map(s => ({ value: s.id, label: s.name })); this.stationId = data.stations[0]?.id ?? 0; this.load(); }); }
  load() { if (!this.stationId) return; this.loading = true; this.page = 1; this.fuel.workspace(this.stationId).subscribe({ next: data => { this.readings = data.readings ?? []; this.nozzles = data.nozzles ?? []; this.attendants = data.attendants ?? []; this.loading = false; this.cdr.detectChanges(); }, error: () => { this.readings = []; this.loading = false; } }); this.fuel.paymentMethods(this.stationId).subscribe(data => { this.paymentMethods = (data.methods ?? []).filter((method: any) => method.active).map((method: any) => ({ value: method.id, label: method.name, hint: method.code })); }); }
  get filtered() { return this.readings.filter(row => (!this.fromDate || row.date >= this.fromDate) && (!this.toDate || row.date <= this.toDate)); }
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
  addPayment() { this.payments.push(this.fb.group({ paymentMethodId: [Number(this.paymentMethods[0]?.value ?? 0), Validators.min(1)], amount: [0, Validators.min(0.01)], reference: [''] })); }
  removePayment(index: number) { if (this.payments.length > 1) this.payments.removeAt(index); }
  openReading() { this.error = ''; this.form.reset({ date: new Date().toISOString().slice(0, 10), nozzleId: 0, attendantId: 0, startIndex: 0, endIndex: 0, returnToTank: 0, unitPrice: 0, payments: [] }); this.payments.clear(); this.addPayment(); this.modalOpen = true; }
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
    if (this.form.invalid) return 'Veuillez renseigner tous les champs obligatoires avec des valeurs valides.';
    const start = Number(this.form.value.startIndex || 0);
    const end = Number(this.form.value.endIndex || 0);
    const returnToTank = Number(this.form.value.returnToTank || 0);
    if (end < start) return 'L’index final ne peut pas être inférieur à l’index de départ.';
    if (returnToTank > end - start) return 'Le retour cuve ne peut pas être supérieur à la sortie de pompe.';
    if (Math.abs(this.paid - this.total) > 0.01) return `La somme des paiements doit correspondre au montant théorique (${this.money(this.total)} Ar).`;
    return '';
  }
  money(value: number) { return formatMoney(value); }
}
