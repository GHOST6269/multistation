/** Formats Ariary amounts with a visible non-breaking space every three digits. */
export function formatMoney(value: number | string | null | undefined, maximumFractionDigits = 0): string {
  const amount = Number(value);
  if (!Number.isFinite(amount)) return '0';

  const formatted = new Intl.NumberFormat('fr-FR', {
    maximumFractionDigits,
    useGrouping: false,
  }).format(amount);
  const [integer, decimal] = formatted.split(',');
  const sign = integer.startsWith('-') ? '-' : '';
  const digits = sign ? integer.slice(1) : integer;
  const grouped = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '\u00a0');

  return `${sign}${grouped}${decimal === undefined ? '' : `,${decimal}`}`;
}
