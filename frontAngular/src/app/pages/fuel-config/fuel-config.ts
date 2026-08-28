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
      currentIndex: [0],
      unitPrice: [0],
      contact: [''],
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
      this.data = x;
      this.cdr.detectChanges();
    });
  }
  get items() {
    return this.data?.[`${this.tab}s`] ?? [];
  }
  get totalPages() { return Math.max(1, Math.ceil(this.items.length / this.pageSize)); }
  get pages() { return Array.from({ length: this.totalPages }, (_, index) => index + 1); }
  get pagedItems() { const page = Math.min(this.page, this.totalPages); return this.items.slice((page - 1) * this.pageSize, page * this.pageSize); }
  options(items: any[]): DropdownOption[] {
    return (items ?? [])
      .filter((x) => x.active)
      .map((x) => ({ value: x.id, label: x.name, hint: x.code ?? '' }));
  }
  edit(x: any) {
    this.editing = x;
    this.formOpen = true;
    this.form.patchValue({
      code: x.code,
      name: x.name,
      fuelTypeId: x.fuelTypeId ?? 0,
      capacity: x.capacity ?? 0,
      minimumStock: x.minimum ?? 0,
      pumpId: x.pumpId ?? 0,
      tankId: x.tankId ?? 0,
      currentIndex: x.currentIndex ?? 0,
      unitPrice: x.unitPrice ?? 0,
      contact: x.contact ?? '',
    });
  }
  changeTab(tab: string) {
    this.tab = tab;
    this.page = 1;
    this.formOpen = false;
    this.editing = null;
  }
  openCreate() {
    if (this.saving) return;
    this.editing = null;
    this.form.reset({ code: '', name: '', fuelTypeId: 0, capacity: 0, minimumStock: 0, pumpId: 0, tankId: 0, currentIndex: 0, unitPrice: 0, contact: '' });
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
    const request = this.editing
      ? this.service.update(this.tab, this.editing.id, this.form.getRawValue())
      : this.service.setup({ ...this.form.getRawValue(), stationId: this.stationId, type: this.tab.toUpperCase() });
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
      this.service.deactivate(this.tab, x.id).subscribe(() => this.load());
  }
}
