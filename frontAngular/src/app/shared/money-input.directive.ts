import { Directive, ElementRef, HostListener, OnInit } from '@angular/core';
import { NgControl } from '@angular/forms';
@Directive({ selector: 'input[appMoney]', standalone: false })
export class MoneyInputDirective implements OnInit {
  constructor(private element:ElementRef<HTMLInputElement>,private control:NgControl){}
  ngOnInit(){queueMicrotask(()=>this.render(this.control.value))}
  @HostListener('input',['$event']) onInput(event:Event){const value=(event.target as HTMLInputElement).value;const normalized=value.replace(/\s/g,'').replace(',','.').replace(/[^0-9.]/g,'');const parts=normalized.split('.');const clean=parts.length>1?`${parts.shift()}.${parts.join('').slice(0,2)}`:normalized;const numeric=clean===''?0:Number(clean);this.control.control?.setValue(Number.isFinite(numeric)?numeric:0,{emitEvent:false});this.render(clean)}
  @HostListener('blur') onBlur(){this.render(this.control.value);this.control.control?.markAsTouched()}
  private render(value:unknown){const raw=String(value??0).replace(/\s/g,'').replace(',','.');const [integer='0',decimal]=raw.split('.');const grouped=integer.replace(/^0+(?=\d)/,'').replace(/\B(?=(\d{3})+(?!\d))/g,' ');this.element.nativeElement.value=decimal!==undefined?`${grouped||'0'},${decimal.slice(0,2)}`:(grouped||'0')}
}
