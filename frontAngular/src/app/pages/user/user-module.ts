import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { UserRoutingModule } from './user-routing-module';
import { Create } from './create/create';

@NgModule({
  declarations: [Create],
  imports: [CommonModule, UserRoutingModule],
})
export class UserModule {}
