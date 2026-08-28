import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ArticleService } from '../../services/article.service';
import { FuelService } from '../../services/fuel.service';
import { AuthService } from '../../services/auth.service';
import { DropdownOption } from '../../shared/dropdown/dropdown';

@Component({
  selector: 'app-fuel-stock',
  standalone: false,
  templateUrl: './fuel-stock.html',
  styleUrl: './fuel-stock.scss',
})
export class FuelStock implements OnInit {
  stations: DropdownOption[] = [];
  stationId = 0;
  tanks: any[] = [];
  loading = true;
  page = 1;
  readonly pageSize = 10;
  constructor(
    private articles: ArticleService,
    private fuel: FuelService,
    private cdr: ChangeDetectorRef,
    public readonly auth: AuthService,
  ) {}
  ngOnInit() {
    this.articles.options().subscribe((data) => {
      this.stations = data.stations.map((station) => ({ value: station.id, label: station.name }));
      this.stationId = data.stations[0]?.id ?? 0;
      this.load();
    });
  }
  load() {
    if (!this.stationId) return;
    this.loading = true;
    this.fuel.workspace(this.stationId).subscribe({
      next: (data) => {
        this.tanks = data.tanks ?? [];
        this.page = 1;
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.tanks = [];
        this.loading = false;
      },
    });
  }
  get totalStock() {
    return this.tanks.reduce((total, tank) => total + Number(tank.stock || 0), 0);
  }
  get totalCapacity() {
    return this.tanks.reduce((total, tank) => total + Number(tank.capacity || 0), 0);
  }
  get availableCapacity() {
    return Math.max(0, this.totalCapacity - this.totalStock);
  }
  get fillRate() {
    return this.totalCapacity ? Math.min(100, (this.totalStock / this.totalCapacity) * 100) : 0;
  }
  get alerts() {
    return this.tanks.filter((tank) => Number(tank.stock) <= Number(tank.minimum)).length;
  }
  get totalPages() {
    return Math.max(1, Math.ceil(this.tanks.length / this.pageSize));
  }
  get pages() {
    return Array.from({ length: this.totalPages }, (_, index) => index + 1);
  }
  get pagedTanks() {
    const page = Math.min(this.page, this.totalPages);
    return this.tanks.slice((page - 1) * this.pageSize, page * this.pageSize);
  }
  percentage(tank: any) {
    return tank.capacity ? Math.min(100, (Number(tank.stock) / Number(tank.capacity)) * 100) : 0;
  }
}
