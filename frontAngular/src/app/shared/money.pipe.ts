import { Pipe, PipeTransform } from '@angular/core';
import { formatMoney } from './money-format';

/** Formats Ariary amounts with a narrow space every three digits. */
@Pipe({ name: 'money', standalone: false })
export class MoneyPipe implements PipeTransform {
  transform(value: number | string | null | undefined, maximumFractionDigits = 0): string {
    return formatMoney(value, maximumFractionDigits);
  }
}
