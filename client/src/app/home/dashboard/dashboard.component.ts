import { Component } from '@angular/core';
import { ClientService } from 'src/app/services/client.service';
import { LoginService } from 'src/app/services/login.service';
import { QuotationService } from 'src/app/services/quotation.service';
import { TemplateComponent } from '../shared/template/template.component';

import Swal from 'sweetalert2';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent {

  user_info: any

  loading: boolean = true;

  policies: any = []

  total_policies: number = 0
  pending_amount: number = 0
  expired_amount: number = 0

  constructor(
    private _quotationService: QuotationService,
    private _clientService: ClientService,
    private _loginService: LoginService,
    private _templateComponent: TemplateComponent
  ) {

    if (localStorage.getItem('Certy_token')) {
      this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);

    }

    this.getQuotations();
  }

  getQuotations() {

    const searchData = {
      client_id: this.user_info.user_id
    }

    this._quotationService.getQuotations(searchData).
      then(({ policies, total_policies, pending_amount, expired_amount }) => {

        this.policies = policies
        this.total_policies = total_policies
        this.pending_amount = pending_amount
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
