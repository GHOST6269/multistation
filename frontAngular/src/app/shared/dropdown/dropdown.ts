import {
  Component,
  ElementRef,
  EventEmitter,
  forwardRef,
  HostListener,
  Input,
  Output,
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';

export interface DropdownOption {
  value: string | number;
  label: string;
  hint?: string;
}

@Component({
  selector: 'app-dropdown',
  standalone: false,
  templateUrl: './dropdown.html',
  styleUrl: './dropdown.scss',
  providers: [{ provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => Dropdown), multi: true }],
})
export class Dropdown implements ControlValueAccessor {
  @Input() options: DropdownOption[] = [];
  @Input() placeholder = 'Sélectionner';
  @Input() searchable = false;
  @Output() valueChange = new EventEmitter<string | number>();
  open = false;
  disabled = false;
  value: string | number | null = null;
  search = '';
  menuStyle: Record<string, string> = {};
  private onChange: (value: string | number | null) => void = () => {};
  private onTouched: () => void = () => {};
  constructor(private readonly element: ElementRef<HTMLElement>) {}
  get selected(): DropdownOption | undefined {
    return this.options.find((option) => option.value === this.value);
  }
  get filtered(): DropdownOption[] {
    const query = this.search.trim().toLowerCase();
    return query
      ? this.options.filter((option) =>
          `${option.label} ${option.hint ?? ''}`.toLowerCase().includes(query),
        )
      : this.options;
  }
  toggle(): void {
    if (!this.disabled) {
      this.open = !this.open;
      if (this.open) this.positionMenu();
      else this.onTouched();
    }
  }
  private positionMenu(): void {
    const box = this.element.nativeElement.getBoundingClientRect();
    const maxHeight = 250;
    const spaceBelow = window.innerHeight - box.bottom;
    const openAbove = spaceBelow < maxHeight && box.top > spaceBelow;
    this.menuStyle = {
      left: `${box.left}px`,
      width: `${box.width}px`,
      top: openAbove ? `${Math.max(8, box.top - maxHeight - 7)}px` : `${box.bottom + 7}px`,
    };
  }
  @HostListener('window:resize') @HostListener('window:scroll') reposition(): void {
    if (this.open) this.positionMenu();
  }
  select(option: DropdownOption): void {
    this.value = option.value;
    this.onChange(option.value);
    this.valueChange.emit(option.value);
    this.onTouched();
    this.open = false;
    this.search = '';
  }
  writeValue(value: string | number | null): void {
    this.value = value;
  }
  registerOnChange(fn: (value: string | number | null) => void): void {
    this.onChange = fn;
  }
  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }
  setDisabledState(disabled: boolean): void {
    this.disabled = disabled;
  }
  @HostListener('document:click', ['$event']) closeOutside(event: MouseEvent): void {
    if (!this.element.nativeElement.contains(event.target as Node)) {
      this.open = false;
      this.search = '';
    }
  }
}
