import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ArticleService } from '../../services/article.service';
import { SupplierService } from '../../services/supplier.service';
import { AuthService } from '../../services/auth.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';
import { ActivatedRoute } from '@angular/router';
import { formatMoney } from '../../shared/money-format';
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
  modal: 'supplier' | 'payment' | 'deduction' | null = null;
  deductions: any[] = [];
  selectedDeduction: any = null;
  deductionError = '';
  printTarget: any = null;
  invoice: any;
  invoiceQuery = '';
  invoiceFromDate = '';
  invoiceToDate = '';
  paymentFromDate = '';
  paymentToDate = '';
  invoicePage = 1;
  paymentPage = 1;
  readonly pageSize = 10;
  readonly today = new Date();
  view: 'invoices' | 'payments' | 'deductions' = 'invoices';
  form;
  paymentForm;
  deductionForm;
  constructor(
    private service: SupplierService,
    private articles: ArticleService,
    fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private route: ActivatedRoute,
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
    this.deductionForm = fb.group({ supplierId: [0, [Validators.required, Validators.min(1)]], amount: [0, [Validators.required, Validators.min(0.01)]], date: [new Date().toISOString().slice(0, 10), Validators.required], settlementInvoiceNumber: ['', Validators.required], reference: [''] });
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
    return (this.data?.invoices ?? []).filter((invoice: any) =>
      (!query || `${invoice.number} ${invoice.supplier} ${invoice.status}`.toLocaleLowerCase().includes(query)) &&
      (!this.invoiceFromDate || invoice.date >= this.invoiceFromDate) &&
      (!this.invoiceToDate || invoice.date <= this.invoiceToDate),
    );
  }
  get filteredPayments() {
    return this.payments.filter((payment) =>
      (!this.paymentFromDate || payment.date >= this.paymentFromDate) &&
      (!this.paymentToDate || payment.date <= this.paymentToDate),
    );
  }
  get invoiceTotalPages() { return Math.max(1, Math.ceil(this.filteredInvoices.length / this.pageSize)); }
  get paymentTotalPages() { return Math.max(1, Math.ceil(this.filteredPayments.length / this.pageSize)); }
  get invoicePages() { return Array.from({ length: this.invoiceTotalPages }, (_, index) => index + 1); }
  get paymentPages() { return Array.from({ length: this.paymentTotalPages }, (_, index) => index + 1); }
  get pagedInvoices() {
    const page = Math.min(this.invoicePage, this.invoiceTotalPages);
    return this.filteredInvoices.slice((page - 1) * this.pageSize, page * this.pageSize);
  }
  get pagedPayments() {
    const page = Math.min(this.paymentPage, this.paymentTotalPages);
    return this.filteredPayments.slice((page - 1) * this.pageSize, page * this.pageSize);
  }
  resetInvoicePage() { this.invoicePage = 1; }
  resetPaymentPage() { this.paymentPage = 1; }
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
    FOURNISSEUR: 'Paiement reçu fournisseur',
  };
  ngOnInit() {
    this.route.data.subscribe(data => this.view = data['view'] === 'payments' ? 'payments' : data['view'] === 'deductions' ? 'deductions' : 'invoices');
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
    this.service.deductions(this.stationId).subscribe((x) => {
      this.deductions = x.deductions ?? [];
      this.cdr.detectChanges();
    });
  }
  get supplierOptions(): DropdownOption[] {
    return (this.data?.suppliers ?? []).filter((supplier: any) => supplier.active)
      .map((supplier: any) => ({ value: supplier.id, label: supplier.name, hint: supplier.code ?? '' }));
  }
  openDeduction(deduction: any) {
    this.selectedDeduction = deduction;
    this.deductionError = '';
    this.deductionForm.reset({ supplierId: 0, amount: deduction.remaining, date: new Date().toISOString().slice(0, 10), settlementInvoiceNumber: '', reference: '' });
    this.modal = 'deduction';
  }
  saveDeduction() {
    if (this.deductionForm.invalid || !this.selectedDeduction) return;
    this.service.applyDeduction({
      readingId: this.selectedDeduction.readingId,
      paymentIndex: this.selectedDeduction.paymentIndex,
      ...this.deductionForm.getRawValue(),
      supplierId: Number(this.deductionForm.value.supplierId),
      amount: Number(this.deductionForm.value.amount),
    }).subscribe({
      next: () => { this.modal = null; this.selectedDeduction = null; this.load(); },
      error: (error) => { this.deductionError = error.error?.message ?? 'Le paiement fournisseur n’a pas pu être enregistré.'; this.cdr.detectChanges(); },
    });
  }
  printDeduction(deduction: any) {
    this.printTarget = deduction;
    window.onafterprint = () => { this.printTarget = null; this.cdr.detectChanges(); };
    setTimeout(() => window.print());
  }
  get stationName(): string { return this.stations.find(station => Number(station.value) === Number(this.stationId))?.label ?? 'Station'; }
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
    return formatMoney(v);
  }
  statusLabel(status: string): string {
    const labels: Record<string, string> = {
      DRAFT: 'Brouillon', PENDING: 'En attente', OPEN: 'Ouverte', UNPAID: 'Non payée',
      PARTIAL: 'Partiellement payée', PARTIALLY_PAID: 'Partiellement payée', PAID: 'Payée',
      OVERDUE: 'En retard', CANCELLED: 'Annulée', CANCELED: 'Annulée',
    };
    return labels[String(status || '').toUpperCase()] ?? status;
  }
}
