import {
  Component,
  ElementRef,
  EventEmitter,
  forwardRef,
  HostListener,
  Input,
  OnDestroy,
  Output,
  ViewChild,
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';

export interface DropdownOption {
  value: string | number;
  label: string;
  hint?: string;
  isCredit?: boolean;
}

@Component({
  selector: 'app-dropdown',
  standalone: false,
  templateUrl: './dropdown.html',
  styleUrl: './dropdown.scss',
  providers: [{ provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => Dropdown), multi: true }],
})
export class Dropdown implements ControlValueAccessor, OnDestroy {
  @Input() options: DropdownOption[] = [];
  @Input() placeholder = 'Sélectionner';
  @Input() searchable = false;
  @Input() selectedTag = 'SÉLECTIONNÉE';
  @Input() emptyTag = 'SÉLECTION';
  @Output() valueChange = new EventEmitter<string | number>();
  open = false;
  disabled = false;
  value: string | number | null = null;
  search = '';
  menuStyle: Record<string, string> = {};
  private menuElement?: HTMLElement;
  private onChange: (value: string | number | null) => void = () => {};
  private onTouched: () => void = () => {};
  private readonly onDocumentScroll = (): void => this.reposition();
  constructor(private readonly element: ElementRef<HTMLElement>) {
    document.addEventListener('scroll', this.onDocumentScroll, true);
  }
  ngOnDestroy(): void {
    document.removeEventListener('scroll', this.onDocumentScroll, true);
    this.hideMenu();
  }
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
  @ViewChild('menu') set menuRef(ref: ElementRef<HTMLElement> | undefined) {
    this.menuElement = ref?.nativeElement;
    if (this.open && this.menuElement) this.presentMenu();
  }
  toggle(): void {
    if (!this.disabled) {
      this.open = !this.open;
      if (this.open) this.positionMenu();
      else {
        this.hideMenu();
        this.onTouched();
      }
    }
  }
  private presentMenu(): void {
    this.positionMenu();
    // The browser top layer lets the popup escape modal scroll containers,
    // transformed panels, and stacking contexts.
    if (this.menuElement && !this.menuElement.matches(':popover-open')) {
      try {
        this.menuElement.showPopover();
      } catch {
        // Keep the normal dropdown usable in browsers without the Popover API.
      }
    }
  }
  private hideMenu(): void {
    if (this.menuElement?.matches(':popover-open')) {
      try {
        this.menuElement.hidePopover();
      } catch {
        // The element may already have been removed during view teardown.
      }
    }
  }
  private positionMenu(): void {
    const box = this.element.nativeElement.getBoundingClientRect();
    const viewportGutter = 8;
    const desiredHeight = Math.min(300, window.innerHeight - viewportGutter * 2);
    const width = Math.min(box.width, window.innerWidth - viewportGutter * 2);
    const left = Math.min(Math.max(viewportGutter, box.left), window.innerWidth - width - viewportGutter);
    const spaceBelow = Math.max(0, window.innerHeight - box.bottom - viewportGutter);
    const spaceAbove = Math.max(0, box.top - viewportGutter);
    const openAbove = spaceBelow < Math.min(desiredHeight, 180) && spaceAbove > spaceBelow;
    const availableHeight = openAbove ? spaceAbove : spaceBelow;
    const menuHeight = Math.min(desiredHeight, availableHeight);
    this.menuStyle = {
      left: `${left}px`,
      width: `${width}px`,
      top: openAbove ? `${Math.max(viewportGutter, box.top - menuHeight - 7)}px` : `${box.bottom + 7}px`,
      maxHeight: `${menuHeight}px`,
    };
  }
  @HostListener('window:resize') @HostListener('window:scroll') reposition(): void {
    if (this.open) this.presentMenu();
  }
  select(option: DropdownOption): void {
    this.value = option.value;
    this.onChange(option.value);
    this.valueChange.emit(option.value);
    this.onTouched();
    this.open = false;
    this.hideMenu();
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
      this.hideMenu();
      this.search = '';
    }
  }
}
