import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ArticleService } from '../../services/article.service';
import { ExpenseService } from '../../services/expense.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { AuthService } from '../../services/auth.service';
import { formatMoney } from '../../shared/money-format';

@Component({ selector: 'app-expenses', standalone: false, templateUrl: './expenses.html', styleUrl: './expenses.scss' })
export class Expenses implements OnInit {
  stations: DropdownOption[] = [];
  paymentMethods: any[] = [];
  suppliers: any[] = [];
  stationId = 0;
  expenses: any[] = [];
  modal = false;
  saving = false;
  loading = false;
  error = '';
  supplierPurchase = false;
  form;

  constructor(private articles: ArticleService, private api: ExpenseService, private cdr: ChangeDetectorRef, fb: FormBuilder, public auth: AuthService) {
    this.form = fb.group({
      date: [new Date().toISOString().slice(0, 10), Validators.required],
      category: ['Autre', [Validators.required, Validators.pattern(/\S/)]],
      description: ['', [Validators.required, Validators.pattern(/\S/)]],
      amount: [0, [Validators.required, Validators.min(.01)]],
      paymentMethod: ['', Validators.required],
      supplierId: [null],
      reference: [''],
    });
  }

  ngOnInit(): void {
    this.articles.options().subscribe({
      next: data => {
        this.stations = data.stations.map(station => ({ value: station.id, label: station.name }));
        this.stationId = data.stations[0]?.id ?? 0;
        this.load();
        this.cdr.detectChanges();
      },
      error: error => { this.error = error.error?.message ?? 'Chargement impossible.'; this.loading = false; this.cdr.detectChanges(); },
    });
  }

  load(): void {
    if (!this.stationId) { this.loading = false; this.expenses = []; this.paymentMethods = []; this.suppliers = []; this.cdr.detectChanges(); return; }
    this.loading = true;
    this.api.list(this.stationId).subscribe({
      next: data => { this.expenses = data.expenses ?? []; this.loading = false; this.cdr.detectChanges(); },
      error: error => { this.expenses = []; this.error = error.error?.message ?? 'Chargement impossible.'; this.loading = false; this.cdr.detectChanges(); },
    });
    this.api.options(this.stationId).subscribe({
      next: data => {
        this.paymentMethods = data.methods ?? [];
        this.suppliers = data.suppliers ?? [];
        const currentMethod = this.form.get('paymentMethod')?.value;
        if (!this.paymentMethods.some(method => method.code === currentMethod)) this.form.patchValue({ paymentMethod: this.paymentMethods[0]?.code ?? '' });
        this.cdr.detectChanges();
      },
      error: error => { this.error = error.error?.message ?? 'Modes de paiement indisponibles.'; this.cdr.detectChanges(); },
    });
  }

  get total(): number { return this.expenses.reduce((sum, expense) => sum + Number(expense.amount || 0), 0); }
  get supplierOptions(): DropdownOption[] { return this.suppliers.map(supplier => ({ value: supplier.id, label: supplier.name })); }

  open(): void {
    this.error = '';
    this.supplierPurchase = false;
    this.form.reset({ date: new Date().toISOString().slice(0, 10), category: 'Autre', description: '', amount: 0, paymentMethod: this.paymentMethods[0]?.code ?? '', supplierId: null, reference: '' });
    this.modal = true;
  }

  setSupplierPurchase(checked: boolean): void {
    this.supplierPurchase = checked;
    this.form.patchValue({ supplierId: checked ? this.suppliers[0]?.id ?? null : null });
  }

  fieldError(name: string): string {
    const control = this.form.get(name);
    if (!control?.touched || control.valid) return '';
    if (control.hasError('required')) return 'Ce champ est obligatoire.';
    if (control.hasError('min')) return 'Le montant doit être supérieur à 0.';
    return 'Ce champ est obligatoire.';
  }

  save(): void {
    if (this.saving) return;
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    if (this.supplierPurchase && !this.form.get('supplierId')?.value) { this.error = 'Sélectionnez le fournisseur de cet achat.'; return; }
    if (this.supplierPurchase && !String(this.form.get('reference')?.value ?? '').trim()) { this.error = 'Saisissez le numéro de facture fournisseur.'; return; }
    this.error = '';
    this.saving = true;
    const value = this.form.getRawValue();
    this.api.create({ ...value, category: String(value.category).trim(), description: String(value.description).trim(), reference: String(value.reference ?? '').trim(), supplierId: this.supplierPurchase ? value.supplierId : null, stationId: this.stationId }).subscribe({
      next: () => { this.modal = false; this.saving = false; this.error = ''; this.form.reset(); this.load(); this.cdr.detectChanges(); },
      error: error => { this.error = error.error?.message ?? 'Enregistrement impossible.'; this.saving = false; this.cdr.detectChanges(); },
    });
  }

  money(value: number): string { return formatMoney(value); }
}
