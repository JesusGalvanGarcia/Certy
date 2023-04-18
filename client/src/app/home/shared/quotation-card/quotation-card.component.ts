import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-quotation-card',
  templateUrl: './quotation-card.component.html',
  styleUrls: ['./quotation-card.component.css']
})
export class QuotationCardComponent {

  user_info: any

  @Input() policy: any = {}

  constructor(
  ) {

    if (localStorage.getItem('Certy_token')) {
      this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);

    }
  }
}
