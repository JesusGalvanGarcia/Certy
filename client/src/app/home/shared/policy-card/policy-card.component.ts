import { Component, Input } from '@angular/core';
import { QuotationService } from 'src/app/services/quotation.service';

import Swal from 'sweetalert2';
@Component({
  selector: 'app-policy-card',
  templateUrl: './policy-card.component.html',
  styleUrls: ['./policy-card.component.css']
})
export class PolicyCardComponent {

  user_info: any

  @Input() policy: any = {}

  constructor(
    private _quotationService: QuotationService
  ) {

    if (localStorage.getItem('Certy_token')) {
      this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);

    }
  }

  downloadPolicy() {

    Swal.fire({
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      text: 'Procesando'
    })
    Swal.showLoading()

    let user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);

    let policyData = {
      client_id: user_info.user_id,
      policy_id: this.policy.id
    }

    this._quotationService.downloadPolicy(policyData).
      then(({ pdfs }) => {

        for (let pdf of pdfs) {

          // this.downLoadFile(pdf.url, "pdf")
          window.open(pdf.url, '_blank')
        }

        Swal.close();
      })
      .catch(({ title, message, code }) => {
        console.log(message)

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

  downLoadFile(data: any, type: string) {
    let blob = new Blob([data], { type: type });
    let url = window.URL.createObjectURL(blob);
    let pwa = window.open(url);
    if (!pwa || pwa.closed || typeof pwa.closed == 'undefined') {
      alert('Por favor desactiva el bloqueo de ventanas emergentes e intenta nuevamente.');
    }
  }
}
