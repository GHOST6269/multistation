import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { ArticleService } from '../../../services/article.service';
import { UserService } from '../../../services/user.service';
import { AppUser, RoleOption, UserRole } from '../../../models/user';
import { DropdownOption } from '../../../shared/dropdown/dropdown';

@Component({
  selector: 'app-create',
  standalone: false,
  templateUrl: './create.html',
  styleUrl: './create.scss',
})
export class Create implements OnInit {
  users: AppUser[] = [];
  roles: RoleOption[] = [];
  stations: DropdownOption[] = [];
  loading = true;
  saving = false;
  error = '';
  modal = false;
  editing: AppUser | null = null;
  readonly form;

  constructor(
    fb: FormBuilder,
    private readonly service: UserService,
    private readonly articles: ArticleService,
    private readonly cdr: ChangeDetectorRef,
  ) {
    this.form = fb.group({
      email: ['', [Validators.required, Validators.email]],
      firstName: ['', Validators.required],
      lastName: [''],
      contact: [''],
      role: ['ROLE_ASSISTANT' as UserRole, Validators.required],
      stationId: [0],
      password: [''],
      isActive: [true],
    });
  }

  ngOnInit(): void {
    this.load();
    this.articles.options().subscribe((data) => {
      this.stations = data.stations.map((station) => ({ value: station.id, label: station.name }));
      this.cdr.detectChanges();
    });
    this.service.roles().subscribe((data) => {
      this.roles = data.roles;
      this.cdr.detectChanges();
    });
  }

  load(): void {
    this.loading = true;
    this.service.list().subscribe({
      next: (users) => {
        this.users = users;
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.loading = false;
        this.error = 'Chargement des utilisateurs impossible.';
        this.cdr.detectChanges();
      },
    });
  }

  open(user?: AppUser): void {
    this.error = '';
    this.editing = user ?? null;
    this.form.reset({
      email: user?.email ?? '',
      firstName: user?.firstName ?? '',
      lastName: user?.lastName ?? '',
      contact: user?.contact ?? '',
      role: user?.role ?? 'ROLE_ASSISTANT',
      stationId: user?.stationIds?.[0] ?? 0,
      password: '',
      isActive: user?.isActive ?? true,
    });
    this.modal = true;
  }

  close(): void {
    if (!this.saving) this.modal = false;
  }

  save(): void {
    const value = this.form.getRawValue();
    if (this.form.invalid || (value.role !== 'ROLE_SUPER_ADMIN' && !Number(value.stationId))) {
      this.form.markAllAsTouched();
      this.error = 'Complétez les champs obligatoires.';
      return;
    }
    this.saving = true;
    this.error = '';
    const payload = {
      email: value.email ?? '',
      firstName: value.firstName ?? '',
      lastName: value.lastName ?? '',
      contact: value.contact ?? '',
      role: value.role as UserRole,
      stationIds: value.role === 'ROLE_SUPER_ADMIN' ? [] : [Number(value.stationId)],
      password: value.password || undefined,
      isActive: !!value.isActive,
    };
    const request = this.editing ? this.service.update(this.editing.id, payload) : this.service.create(payload);
    request.subscribe({
      next: (user) => {
        this.users = this.editing ? this.users.map((item) => item.id === user.id ? user : item) : [user, ...this.users];
        this.modal = false;
        this.saving = false;
        this.cdr.detectChanges();
      },
      error: (e) => {
        this.error = e.error?.message ?? 'Enregistrement impossible.';
        this.saving = false;
        this.cdr.detectChanges();
      },
    });
  }

  toggle(user: AppUser): void {
    this.service.toggle(user.id).subscribe((updated) => {
      this.users = this.users.map((item) => item.id === updated.id ? updated : item);
      this.cdr.detectChanges();
    });
  }

  stationLabel(user: AppUser): string {
    if (user.role === 'ROLE_SUPER_ADMIN') return 'Toutes les stations';
    return this.stations.find((station) => Number(station.value) === user.stationIds?.[0])?.label ?? 'Station non liée';
  }

  initials(user: AppUser): string {
    return `${user.firstName?.[0] ?? ''}${user.lastName?.[0] ?? ''}`.toUpperCase() || user.email.slice(0, 2).toUpperCase();
  }
}
