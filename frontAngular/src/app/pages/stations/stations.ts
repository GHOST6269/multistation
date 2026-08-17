import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Station as StationModel } from '../../models/station.model';
import { Station } from '../../services/station';

@Component({ selector: 'app-stations', standalone: false, templateUrl: './stations.html', styleUrl: './stations.scss' })
export class Stations implements OnInit {
  items: StationModel[] = []; loading = true; showForm = false; saving = false; query = ''; saveError = '';
  editingStation: StationModel | null = null;
  stationToDeactivate: StationModel | null = null;
  deactivating = false;
  readonly form: FormGroup;
  constructor(private readonly service: Station, fb: FormBuilder, private readonly cdr: ChangeDetectorRef) {
    this.form = fb.group({
      name: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100)]],
      code: ['', [Validators.required, Validators.pattern(/^[A-Z0-9-]{2,20}$/)]],
      city: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
      address: ['', Validators.maxLength(255)], manager: ['', Validators.maxLength(100)],
      contact: ['', Validators.pattern(/^\+?[0-9][0-9 ()-]{7,19}$/)],
      email: ['', [Validators.email, Validators.maxLength(180)]], status: ['ACTIVE'],
    });
  }
  ngOnInit(): void { this.load(); }
  load(): void { this.loading = true; this.service.getAll(this.query).subscribe({ next: items => { this.items = items; this.loading = false; this.cdr.detectChanges(); }, error: () => { this.loading = false; this.cdr.detectChanges(); } }); }
  openForm(station?: StationModel): void {
    this.saveError = ''; this.editingStation = station ?? null;
    this.form.reset(station ? { name: station.name, code: station.code, city: station.city, address: station.address, manager: station.manager, contact: station.contact, email: station.email, status: station.status } : { status: 'ACTIVE' });
    this.showForm = true;
  }
  closeForm(): void { if (!this.saving) { this.showForm = false; this.editingStation = null; this.saveError = ''; } }
  invalid(field: string, error?: string): boolean { const control = this.form.get(field); return !!control && control.touched && (error ? control.hasError(error) : control.invalid); }
  submit(): void {
    this.saveError = '';
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.saving = true;
    const request = this.editingStation ? this.service.update(this.editingStation.id, this.form.getRawValue()) : this.service.create(this.form.getRawValue());
    request.subscribe({
      next: item => { this.items = this.editingStation ? this.items.map(current => current.id === item.id ? item : current) : [item, ...this.items]; this.showForm = false; this.editingStation = null; this.form.reset({ status: 'ACTIVE' }); this.saving = false; this.cdr.detectChanges(); },
      error: (error: HttpErrorResponse) => { this.saveError = error.error?.message ?? 'La station n’a pas pu être enregistrée. Réessayez.'; this.saving = false; this.cdr.detectChanges(); },
    });
  }
  confirmDeactivate(station: StationModel): void { this.stationToDeactivate = station; }
  cancelDeactivate(): void { if (!this.deactivating) this.stationToDeactivate = null; }
  deactivate(): void {
    if (!this.stationToDeactivate) return;
    this.deactivating = true;
    this.service.deactivate(this.stationToDeactivate.id).subscribe({
      next: station => { this.items = this.items.map(item => item.id === station.id ? station : item); this.stationToDeactivate = null; this.deactivating = false; this.cdr.detectChanges(); },
      error: () => { this.deactivating = false; this.cdr.detectChanges(); },
    });
  }
  initials(name: string): string { return name.split(' ').map(x => x[0]).join('').slice(0, 2).toUpperCase(); }
}
