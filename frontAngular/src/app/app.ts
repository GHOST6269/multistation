import { Component } from '@angular/core';

@Component({
  selector: 'app-root',
  templateUrl: './app.html',
  standalone: false,
  styleUrl: './app.scss',
})
export class App {
  menuOpen = false;
  stockMenuOpen = true;
  readonly navigation: any[] = [
    { label: 'Vue d’ensemble', route: '/', icon: '⌂' },
    { label: 'Stations', route: '/stations', icon: '◇' },
    {
      label: 'Stock',
      route: '/stock-carburant',
      icon: '▦',
      children: [
        { label: 'État du stock', route: '/stock-carburant' },
        { label: 'Entrées de stock', route: '/carburants', queryParams: { action: 'delivery' } },
        { label: 'Sorties de stock', route: '/stock-carburant', queryParams: { action: 'exit' } },
        { label: 'Mouvements de stock', route: '/stock-carburant', queryParams: { action: 'movements' } },
        { label: 'Inventaire', route: '/inventaire' },
      ],
    },
    { label: 'Articles', route: '/articles', icon: '▤', visible: false },
    { label: 'Carburants & Pompes', route: '/carburants', icon: '⛽' },
    { label: 'Stock carburant', route: '/stock-carburant', icon: '◒' },
    { label: 'Fournisseurs', route: '/fournisseurs', icon: '▱' },
    { label: 'Équipe', route: '/equipe', icon: '♙' },
  ];
}
