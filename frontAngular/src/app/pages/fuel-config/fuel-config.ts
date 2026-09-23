import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ArticleService } from '../../services/article.service';
import { FuelService } from '../../services/fuel.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { AuthService } from '../../services/auth.service';
@Component({
  selector: 'app-fuel-config',
  standalone: false,
  templateUrl: './fuel-config.html',
  styleUrl: './fuel-config.scss',
})
export class FuelConfig implements OnInit {
  stations: DropdownOption[] = [];
  stationId = 0;
  data: any;
  tab = 'fuel';
  editing: any = null;
  assignmentTarget: any = null;
  nozzleAssignmentTarget: any = null;
  assignmentError = '';
  formOpen = false;
  saving = false;
  page = 1;
  readonly pageSize = 10;
  form;
  constructor(
    private service: FuelService,
    private articles: ArticleService,
    fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    public auth: AuthService,
  ) {
    this.form = fb.group({
      code: ['', Validators.required],
      name: ['', Validators.required],
      fuelTypeId: [0],
      capacity: [0],
      minimumStock: [0],
      pumpId: [0],
      tankId: [0],
      attendantId: [0],
      nozzleIds: [[] as number[]],
      currentIndex: [0],
      unitPrice: [0],
      contact: [''],
      allowedRoles: fb.nonNullable.control<string[]>([]),
      supplierDeduction: [false],
    });
  }
  ngOnInit() {
    this.articles.options().subscribe((x) => {
      this.stations = x.stations.map((s) => ({ value: s.id, label: s.name }));
      this.stationId = x.stations[0]?.id ?? 0;
      this.load();
    });
  }
  load() {
    this.page = 1;
    this.service.config(this.stationId).subscribe((x) => {
      this.data = { ...x, paymentMethods: this.data?.paymentMethods ?? [] };
      this.cdr.detectChanges();
    });
    if (this.auth.hasAnyRole(['ROLE_GERANT'])) this.service.paymentMethods(this.stationId, true).subscribe((x) => {
      this.data = { ...(this.data ?? {}), paymentMethods: x.methods ?? [] };
      this.cdr.detectChanges();
    });
  }
  get items() {
    return this.tab === 'payment' ? this.data?.paymentMethods ?? [] : this.data?.[`${this.tab}s`] ?? [];
  }
  get totalPages() { return Math.max(1, Math.ceil(this.items.length / this.pageSize)); }
  get pages() { return Array.from({ length: this.totalPages }, (_, index) => index + 1); }
  get pagedItems() { const page = Math.min(this.page, this.totalPages); return this.items.slice((page - 1) * this.pageSize, page * this.pageSize); }
  options(items: any[]): DropdownOption[] {
    return (items ?? [])
      .filter((x) => x.active)
      .map((x) => ({ value: x.id, label: x.name ?? x.code, hint: x.code ?? '' }));
  }
  get nozzleChoices(): any[] { return (this.data?.nozzles ?? []).filter((nozzle: any) => nozzle.active); }
  isNozzleSelected(id: number): boolean { return (this.form.value.nozzleIds ?? []).some((value: number) => Number(value) === id); }
  toggleNozzle(id: number, checked: boolean) {
    const selected = new Set<number>((this.form.value.nozzleIds ?? []).map(Number));
    checked ? selected.add(id) : selected.delete(id);
    this.form.patchValue({ nozzleIds: [...selected] });
  }
  openNozzleAssignment(attendant: any) {
    this.assignmentTarget = attendant;
    this.assignmentError = '';
    this.form.patchValue({ nozzleIds: attendant.nozzleIds ?? [] });
  }
  closeNozzleAssignment() {
    if (!this.saving) this.assignmentTarget = null;
  }
  saveNozzleAssignment() {
    if (!this.assignmentTarget || this.saving) return;
    this.saving = true;
    this.assignmentError = '';
    this.service.update('attendant', this.assignmentTarget.id, {
      code: this.assignmentTarget.code ?? '',
      name: this.assignmentTarget.name,
      contact: this.assignmentTarget.contact ?? '',
      nozzleIds: this.form.getRawValue().nozzleIds ?? [],
    }).subscribe({
      next: () => {
        this.saving = false;
        this.assignmentTarget = null;
        this.load();
      },
      error: (error) => {
        this.assignmentError = error.error?.message ?? 'L’affectation des pistolets a échoué.';
        this.saving = false;
        this.cdr.detectChanges();
      },
    });
  }
  openAttendantAssignment(nozzle: any) {
    this.nozzleAssignmentTarget = nozzle;
    this.assignmentError = '';
    this.form.patchValue({ attendantId: nozzle.attendantId ?? 0 });
  }
  closeAttendantAssignment() {
    if (!this.saving) this.nozzleAssignmentTarget = null;
  }
  saveAttendantAssignment() {
    if (!this.nozzleAssignmentTarget || this.saving) return;
    this.saving = true;
    this.assignmentError = '';
    const nozzle = this.nozzleAssignmentTarget;
    this.service.update('nozzle', nozzle.id, {
      code: nozzle.code,
      name: nozzle.code,
      pumpId: nozzle.pumpId,
      tankId: nozzle.tankId,
      currentIndex: nozzle.currentIndex ?? 0,
      attendantId: Number(this.form.value.attendantId ?? 0),
    }).subscribe({
      next: () => {
        this.saving = false;
        this.nozzleAssignmentTarget = null;
        this.load();
      },
      error: (error) => {
        this.assignmentError = error.error?.message ?? 'L’affectation du pompiste a échoué.';
        this.saving = false;
        this.cdr.detectChanges();
      },
    });
  }
  edit(x: any) {
    this.editing = x;
    this.formOpen = true;
    this.form.patchValue({
      code: x.code,
      name: x.name ?? x.code,
      fuelTypeId: x.fuelTypeId ?? 0,
      capacity: x.capacity ?? 0,
      minimumStock: x.minimum ?? 0,
      pumpId: x.pumpId ?? 0,
      tankId: x.tankId ?? 0,
      attendantId: x.attendantId ?? 0,
      nozzleIds: x.nozzleIds ?? [],
      currentIndex: x.currentIndex ?? 0,
      unitPrice: x.unitPrice ?? 0,
      contact: x.contact ?? '',
      allowedRoles: x.allowedRoles ?? [],
      supplierDeduction: x.supplierDeduction ?? false,
    });
  }
  changeTab(tab: string) {
    if (tab === 'payment' && !this.auth.hasAnyRole(['ROLE_GERANT'])) return;
    this.tab = tab;
    this.page = 1;
    this.formOpen = false;
    this.editing = null;
  }
  openCreate() {
    if (this.saving) return;
    this.editing = null;
    this.form.reset({ code: '', name: '', fuelTypeId: 0, capacity: 0, minimumStock: 0, pumpId: 0, tankId: 0, attendantId: 0, nozzleIds: [], currentIndex: 0, unitPrice: 0, contact: '', allowedRoles: ['ROLE_GERANT'], supplierDeduction: false });
    this.formOpen = true;
  }
  closeForm() {
    if (this.saving) return;
    this.formOpen = false;
    this.editing = null;
  }
  save() {
    if (this.form.invalid || this.saving) return;
    this.saving = true;
    const value = this.form.getRawValue();
    const request = this.tab === 'payment'
      ? (this.editing ? this.service.updatePaymentMethod(this.editing.id, value) : this.service.createPaymentMethod({ ...value, stationId: this.stationId }))
      : (this.editing ? this.service.update(this.tab, this.editing.id, value) : this.service.setup({ ...value, stationId: this.stationId, type: this.tab.toUpperCase() }));
    request.subscribe({
      next: () => {
        this.saving = false;
        this.formOpen = false;
        this.editing = null;
        this.load();
      },
      error: () => {
        this.saving = false;
        this.cdr.detectChanges();
      },
    });
  }
  deactivate(x: any) {
    if (confirm(`Désactiver ${x.name || x.code} ?`))
      (this.tab === 'payment' ? this.service.deactivatePaymentMethod(x.id) : this.service.deactivate(this.tab, x.id)).subscribe(() => this.load());
  }

  readonly paymentRoles = [
    { value: 'ROLE_GERANT', label: 'Gérant', short: 'Gestion' },
    { value: 'ROLE_QUALITY_MARSHALL', label: 'Quality Marshal', short: 'Contrôle' },
    { value: 'ROLE_ASSISTANT', label: 'Assistant', short: 'Opération' },
  ];
  roleAllowed(role: string): boolean { return (this.form.value.allowedRoles ?? []).includes(role); }
  roleLabels(roles: string[]): string { return this.paymentRoles.filter(role => (roles ?? []).includes(role.value)).map(role => role.label).join(', '); }
  toggleRole(role: string, checked: boolean) {
    const roles = new Set<string>(this.form.value.allowedRoles ?? []);
    checked ? roles.add(role) : roles.delete(role);
    this.form.patchValue({ allowedRoles: [...roles] });
  }
}
