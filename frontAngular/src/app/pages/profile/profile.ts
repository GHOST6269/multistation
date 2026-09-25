import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { AppUser, RoleOption, UserRole } from '../../models/user';
import { AuthService } from '../../services/auth.service';
import { ArticleService } from '../../services/article.service';
import { UserService } from '../../services/user.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';

@Component({
  selector: 'app-profile',
  standalone: false,
  templateUrl: './profile.html',
  styleUrl: './profile.scss',
})
export class Profile implements OnInit {
  user: AppUser | null = null;
  knownUsers: AppUser[] = [];
  saving = false;
  error = '';
  saved = false;
  roles: RoleOption[] = [];
  stations: DropdownOption[] = [];
  readonly form;

  constructor(
    fb: FormBuilder,
    private readonly auth: AuthService,
    private readonly users: UserService,
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
      passwordConfirmation: [''],
    });
  }

  ngOnInit(): void {
    this.auth.ensureUser().subscribe((user) => {
      this.user = user;
      this.form.patchValue({
        email: user?.email ?? '',
        firstName: user?.firstName ?? '',
        lastName: user?.lastName ?? '',
        contact: user?.contact ?? '',
        role: user?.role ?? 'ROLE_ASSISTANT',
        stationId: user?.stationIds?.[0] ?? 0,
      });
      this.cdr.detectChanges();
    });
    if (this.auth.isSuperAdmin()) {
      this.users.list().subscribe((users) => { this.knownUsers = users; this.cdr.detectChanges(); });
      this.users.roles().subscribe((data) => { this.roles = data.roles; this.cdr.detectChanges(); });
      this.articles.options().subscribe((data) => {
        this.stations = data.stations.map((station) => ({ value: station.id, label: station.name }));
        this.cdr.detectChanges();
      });
    }
  }

  save(): void {
    if (this.emailAlreadyUsed) {
      this.form.controls.email.markAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.error = '';
    this.saved = false;
    if (this.form.invalid || (this.isSuperAdmin() && value.role !== 'ROLE_SUPER_ADMIN' && !Number(value.stationId))) {
      this.form.markAllAsTouched();
      this.error = 'Le prénom est obligatoire.';
      return;
    }
    if (value.password !== value.passwordConfirmation) {
      this.error = 'Les deux mots de passe ne correspondent pas.';
      return;
    }
    if (value.password && value.password.length < 6) {
      this.error = 'Le mot de passe doit contenir au moins 6 caractères.';
      return;
    }

    this.saving = true;
    this.auth.updateProfile({
      email: this.isSuperAdmin() ? value.email ?? '' : undefined,
      firstName: value.firstName ?? '',
      lastName: value.lastName ?? '',
      contact: value.contact ?? '',
      role: this.isSuperAdmin() ? value.role as UserRole : undefined,
      stationIds: this.isSuperAdmin() ? (value.role === 'ROLE_SUPER_ADMIN' ? [] : [Number(value.stationId)]) : undefined,
      password: value.password || undefined,
    }).subscribe({
      next: (user) => {
        this.user = user;
        this.form.patchValue({ password: '', passwordConfirmation: '' });
        this.saving = false;
        this.saved = true;
        this.cdr.detectChanges();
      },
      error: (error) => {
        this.error = error.error?.message ?? 'La modification du profil est impossible.';
        this.saving = false;
        this.cdr.detectChanges();
      },
    });
  }

  stationLabel(): string {
    if (!this.user || this.user.role === 'ROLE_SUPER_ADMIN') return 'Toutes les stations';
    return this.user.stationNames?.join(', ') || 'Station assignée';
  }

  isSuperAdmin(): boolean { return this.auth.isSuperAdmin(); }

  get emailAlreadyUsed(): boolean {
    const email = String(this.form.controls.email.value ?? '').trim().toLowerCase();
    return !!email && this.knownUsers.some((user) => user.email.trim().toLowerCase() === email && user.id !== this.user?.id);
  }
}
