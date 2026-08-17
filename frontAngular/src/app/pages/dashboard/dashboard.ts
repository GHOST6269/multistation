import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DashboardData } from '../../models/inventory.model';
import { Station } from '../../services/station';

@Component({ selector: 'app-dashboard', standalone: false, templateUrl: './dashboard.html', styleUrl: './dashboard.scss' })
export class Dashboard implements OnInit {
  data?: DashboardData; loading = true; error = false;
  constructor(private readonly stations: Station, private readonly cdr: ChangeDetectorRef) {}
  ngOnInit(): void { this.load(); }
  load(): void { this.loading = true; this.error = false; this.stations.dashboard().subscribe({ next: data => { this.data = data; this.loading = false; this.cdr.detectChanges(); }, error: () => { this.error = true; this.loading = false; this.cdr.detectChanges(); } }); }
  initials(name: string): string { return name.split(' ').map(word => word[0]).join('').slice(0, 2).toUpperCase(); }
}
