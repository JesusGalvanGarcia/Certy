import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-policy-card',
  templateUrl: './policy-card.component.html',
  styleUrls: ['./policy-card.component.css']
})
export class PolicyCardComponent {

  user_info: any

  @Input() policy: any = {}

  constructor(
  ) {

    if (localStorage.getItem('Certy_token')) {
      this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);

    }
  }
}
