import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ArticleService } from '../../services/article.service';
import { SupplierService } from '../../services/supplier.service';
import { AuthService } from '../../services/auth.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
@Component({
  selector: 'app-suppliers',
  standalone: false,
  templateUrl: './suppliers.html',
  styleUrl: './suppliers.scss',
})
export class Suppliers implements OnInit {
  stations: DropdownOption[] = [];
  stationId = 0;
  data: any;
  payments: any[] = [];
  modal: 'supplier' | 'payment' | null = null;
  invoice: any;
  invoiceQuery = '';
  form;
  paymentForm;
  constructor(
    private service: SupplierService,
    private articles: ArticleService,
    fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    public readonly auth: AuthService,
  ) {
    this.form = fb.group({
      code: [''],
      name: ['', Validators.required],
      contactPerson: [''],
      phone: [''],
      email: [''],
      address: [''],
    });
    this.paymentForm = fb.group({
      amount: [0, Validators.min(0.01)],
      date: [new Date().toISOString().slice(0, 10)],
      method: ['BANK_TRANSFER'],
      reference: [''],
      note: [''],
    });
  }
  get totalBalance() {
    return (this.data?.suppliers ?? []).reduce(
      (sum: number, s: any) => sum + Number(s.balance || 0),
      0,
    );
  }
  get activeSuppliers() {
    return (this.data?.suppliers ?? []).filter((supplier: any) => supplier.active).length;
  }
  get totalBilled() {
    return (this.data?.suppliers ?? []).reduce(
      (sum: number, supplier: any) => sum + Number(supplier.billed || 0),
      0,
    );
  }
  get filteredInvoices() {
    const query = this.invoiceQuery.trim().toLocaleLowerCase();
    if (!query) return this.data?.invoices ?? [];
    return (this.data?.invoices ?? []).filter((invoice: any) =>
      `${invoice.number} ${invoice.supplier} ${invoice.status}`.toLocaleLowerCase().includes(query),
    );
  }
  fuelsFor(supplier: any): string[] {
    return [
      ...new Set(
        (this.data?.invoices ?? [])
          .filter((invoice: any) => invoice.supplierId === supplier.id)
          .map(
            (invoice: any) =>
              String(invoice.description ?? '').match(/Livraison\s+([A-Za-z0-9]+)/)?.[1],
          )
          .filter(Boolean),
      ),
    ] as string[];
  }
  readonly paymentLabels: Record<string, string> = {
    BANK_TRANSFER: 'Virement',
    DIRECT_DEBIT: 'Prélèvement',
    CHEQUE: 'Chèque',
  };
  ngOnInit() {
    this.articles.options().subscribe((x) => {
      this.stations = x.stations.map((s) => ({ value: s.id, label: s.name }));
      this.stationId = x.stations[0]?.id ?? 0;
      this.load();
    });
  }
  load() {
    this.service.list(this.stationId).subscribe((x) => {
      this.data = x;
      this.cdr.detectChanges();
    });
    this.service.paymentHistory(this.stationId).subscribe((x) => {
      this.payments = x.payments ?? [];
      this.cdr.detectChanges();
    });
  }
  saveSupplier() {
    if (this.form.invalid) return;
    this.service.create({ ...this.form.getRawValue(), stationId: this.stationId }).subscribe(() => {
      this.modal = null;
      this.form.reset();
      this.load();
    });
  }
  openPayment(i: any) {
    this.invoice = i;
    this.paymentForm.controls.amount.setValidators([
      Validators.required,
      Validators.min(0.01),
      Validators.max(i.remaining),
    ]);
    this.paymentForm.reset({
      amount: i.remaining,
      date: new Date().toISOString().slice(0, 10),
      method: 'BANK_TRANSFER',
    });
    this.modal = 'payment';
  }
  savePayment() {
    if (this.paymentForm.invalid) return;
    this.service
      .pay({ ...this.paymentForm.getRawValue(), invoiceId: this.invoice.id })
      .subscribe(() => {
        this.modal = null;
        this.load();
      });
  }
  money(v: number) {
    return new Intl.NumberFormat('fr-FR').format(v);
  }
}
