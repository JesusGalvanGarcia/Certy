import { Component } from '@angular/core';
import { QuotationService } from 'src/app/services/quotation.service';

import Swal from 'sweetalert2';

@Component({
  selector: 'app-account-status',
  templateUrl: './account-status.component.html',
  styleUrls: ['./account-status.component.css']
})
export class AccountStatusComponent {

  user_info: any

  loading: boolean = true;

  policies: any = []

  pending_amount: number = 0
  expired_amount: number = 0
  total_amount: number = 0
  paid_amount: number = 0

  constructor(
    private _quotationService: QuotationService
  ) {

    if (localStorage.getItem('Certy_token')) {
      this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);

    }

    this.getQuotations();
  }
  getQuotations() {

    const searchData = {
      client_id: this.user_info.user_id,
      filter: 1
    }

    this._quotationService.getQuotations(searchData).
      then(({ policies, pending_amount, expired_amount, total_amount, paid_amount }) => {

        this.policies = policies
        this.total_amount = total_amount
        this.pending_amount = pending_amount
        this.paid_amount = paid_amount
        this.expired_amount = expired_amount
        this.loading = false

      })
      .catch(({ title, message, code }) => {
        console.log(message)

        this.loading = false
        Swal.fire({
          icon: 'warning',
          title: title,
          text: message,
          footer: code,
          confirmButtonColor: '#06B808',
          confirmButtonText: 'Entendido',
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false
        })

      })
  }
}
