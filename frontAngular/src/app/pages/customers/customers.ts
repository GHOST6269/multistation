import { Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ArticleService } from '../../services/article.service';
import { CustomerService } from '../../services/customer.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';

@Component({ selector: 'app-customers', standalone: false, templateUrl: './customers.html', styleUrl: './customers.scss' })
export class Customers implements OnInit {
  stations: DropdownOption[] = []; stationId = 0; customers: any[] = []; loading = false; saving = false; error = '';
  editing: any = null; modalOpen = false;
  form;
  constructor(private readonly articles: ArticleService, private readonly customersApi: CustomerService, fb: FormBuilder) {
    this.form = fb.group({ code: [''], name: ['', Validators.required], contactPerson: [''], phone: [''], email: [''], address: [''] });
  }
  ngOnInit() { this.articles.options().subscribe(data => { this.stations = data.stations.map(s => ({ value: s.id, label: s.name })); this.stationId = Number(this.stations[0]?.value ?? 0); this.load(); }); }
  get activeCount() { return this.customers.filter(customer => customer.active).length; }
  get inactiveCount() { return this.customers.length - this.activeCount; }
  load() { if (!this.stationId) return; this.loading = true; this.customersApi.list(this.stationId).subscribe({ next: data => { this.customers = data.customers ?? []; this.loading = false; }, error: () => { this.error = 'Les clients n’ont pas pu être chargés.'; this.loading = false; } }); }
  open(customer?: any) { this.error = ''; this.editing = customer ?? null; this.form.reset(customer ?? { code: '', name: '', contactPerson: '', phone: '', email: '', address: '' }); this.modalOpen = true; }
  save() { if (this.form.invalid || this.saving) { this.form.markAllAsTouched(); return; } this.saving = true; const request = this.editing ? this.customersApi.update(this.editing.id, this.form.value) : this.customersApi.create({ ...this.form.value, stationId: this.stationId }); request.subscribe({ next: () => { this.saving = false; this.modalOpen = false; this.load(); }, error: e => { this.error = e.error?.message ?? 'Enregistrement impossible.'; this.saving = false; } }); }
  deactivate(customer: any) { if (!confirm(`Désactiver ${customer.name} ?`)) return; this.customersApi.deactivate(customer.id).subscribe({ next: () => this.load(), error: e => this.error = e.error?.message ?? 'Action impossible.' }); }
}
