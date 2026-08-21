import { HttpEvent, HttpHandler, HttpInterceptor, HttpRequest } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const token = localStorage.getItem('stationflow_token');
    const request = token ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : req;
    return next.handle(request);
  }
}
