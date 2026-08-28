import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ArticleService } from '../../services/article.service';
import { SupplierService } from '../../services/supplier.service';
import { AuthService } from '../../services/auth.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { FuelService } from '../../services/fuel.service';
import { FormBuilder, Validators } from '@angular/forms';

@Component({ selector: 'app-fuel-deliveries', standalone: false, templateUrl: './fuel-deliveries.html', styleUrl: './fuel-deliveries.scss' })
export class FuelDeliveries implements OnInit {
  stations: DropdownOption[] = []; stationId = 0; invoices: any[] = []; fromDate = ''; toDate = ''; page = 1; readonly pageSize = 10; loading = true; modalOpen = false; quickSupplierOpen = false; saving = false; savingSupplier = false; tanks: any[] = []; supplierOptions: DropdownOption[] = []; form; supplierForm;
  constructor(private articles: ArticleService, private suppliers: SupplierService, private fuel: FuelService, private fb: FormBuilder, private cdr: ChangeDetectorRef, public auth: AuthService) { this.form = fb.group({ date: [new Date().toISOString().slice(0, 10), Validators.required], tankId: [0, Validators.min(1)], supplierId: [0, Validators.min(1)], invoiceNumber: [''], dueDate: [''], quantity: [0, Validators.min(.001)], unitCost: [0, Validators.min(0)] }); this.supplierForm = fb.group({ code: [''], name: ['', Validators.required], contactPerson: [''], phone: [''] }); }
  ngOnInit() { this.articles.options().subscribe(data => { this.stations = data.stations.map(s => ({ value: s.id, label: s.name })); this.stationId = data.stations[0]?.id ?? 0; this.load(); }); }
  load() { if (!this.stationId) return; this.loading = true; this.page = 1; this.suppliers.list(this.stationId).subscribe({ next: data => { this.invoices = data.invoices ?? []; this.supplierOptions = (data.suppliers ?? []).filter((supplier: any) => supplier.active).map((supplier: any) => ({ value: supplier.id, label: supplier.name, hint: supplier.code })); this.loading = false; this.cdr.detectChanges(); }, error: () => { this.invoices = []; this.loading = false; } }); this.fuel.workspace(this.stationId).subscribe(data => { this.tanks = data.tanks ?? []; this.cdr.detectChanges(); }); }
  get filtered() { return this.invoices.filter(row => (!this.fromDate || row.date >= this.fromDate) && (!this.toDate || row.date <= this.toDate)); }
  get totalAmount() { return this.filtered.reduce((sum, row) => sum + Number(row.total || 0), 0); }
  get remainingAmount() { return this.filtered.reduce((sum, row) => sum + Number(row.remaining || 0), 0); }
  get totalPages() { return Math.max(1, Math.ceil(this.filtered.length / this.pageSize)); } get pages() { return Array.from({ length: this.totalPages }, (_, index) => index + 1); } get rows() { const page = Math.min(this.page, this.totalPages); return this.filtered.slice((page - 1) * this.pageSize, page * this.pageSize); } resetPage() { this.page = 1; }
  get tankOptions(): DropdownOption[] { return this.tanks.map(tank => ({ value: tank.id, label: `${tank.name} · ${tank.fuel}`, hint: `${tank.stock} / ${tank.capacity} L` })); }
  get deliveryTotal() { return Number(this.form.value.quantity || 0) * Number(this.form.value.unitCost || 0); }
  openDelivery() { this.form.reset({ date: new Date().toISOString().slice(0, 10), tankId: 0, supplierId: 0, invoiceNumber: '', dueDate: '', quantity: 0, unitCost: 0 }); this.modalOpen = true; }
  openQuickSupplier() { if (this.saving) return; this.supplierForm.reset({ code: '', name: '', contactPerson: '', phone: '' }); this.quickSupplierOpen = true; }
  saveQuickSupplier() { if (this.supplierForm.invalid || this.savingSupplier) { this.supplierForm.markAllAsTouched(); return; } this.savingSupplier = true; this.suppliers.create({ ...this.supplierForm.getRawValue(), stationId: this.stationId }).subscribe({ next: (supplier) => { const option = { value: supplier.id, label: supplier.name, hint: supplier.code ?? '' }; this.supplierOptions = [...this.supplierOptions, option]; this.form.patchValue({ supplierId: supplier.id }); this.savingSupplier = false; this.quickSupplierOpen = false; this.cdr.detectChanges(); }, error: () => { this.savingSupplier = false; this.cdr.detectChanges(); } }); }
  save() { if (this.form.invalid || this.saving) { this.form.markAllAsTouched(); return; } this.saving = true; this.suppliers.delivery({ ...this.form.getRawValue(), stationId: this.stationId }).subscribe({ next: () => { this.saving = false; this.modalOpen = false; this.load(); }, error: () => { this.saving = false; this.cdr.detectChanges(); } }); }
  money(value: number) { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value); }
}
