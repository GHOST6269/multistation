import { ChangeDetectorRef, Component } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: false,
  templateUrl: './login.html',
  styleUrl: './login.scss',
})
export class Login {
  loading = false;
  error = '';
  readonly form;

  constructor(
    fb: FormBuilder,
    private readonly auth: AuthService,
    private readonly router: Router,
    private readonly cdr: ChangeDetectorRef,
  ) {
    this.form = fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required],
    });
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.loading = true;
    this.error = '';
    const { email, password } = this.form.getRawValue();
    this.auth.login(email ?? '', password ?? '').subscribe({
      next: () => this.router.navigate(['/']),
      error: (e) => {
        this.error = e.error?.message ?? 'Connexion impossible.';
        this.loading = false;
        this.cdr.detectChanges();
      },
    });
  }
}
