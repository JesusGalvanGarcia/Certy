import { AfterViewInit, Component, EventEmitter, Output, ViewChild } from '@angular/core';

import { Router, ActivatedRoute, Params } from '@angular/router';
import { QuotationService } from 'src/app/services/quotation.service';

import Swal from 'sweetalert2';

@Component({
  selector: 'app-control',
  templateUrl: './control.component.html',
  styleUrls: ['./control.component.css']
})
export class ControlComponent {

  status: any
  policy_id: any

  constructor(
    private rutaActiva: ActivatedRoute,
    private router: Router,
    private _quotationService: QuotationService
  ) {

    Swal.fire({
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      text: 'Procesando'
    })
    Swal.showLoading()

    this.rutaActiva.params.subscribe(
      ({ policy_id }: Params) => {
        this.policy_id = policy_id ? policy_id : 0;
      }
    );

    this.rutaActiva.queryParams
      .subscribe((params: any) => {

        this.status = params.status ? params.status : null;
      });

    this.validateResponse();
  }

  validateResponse() {

    const searchData = {
      policy_id: this.policy_id,
      status_id: this.status
    }

    this._quotationService.confirmPayment(searchData).
      then(({ title, message }) => {

        if (this.status == 0) { // Error

          Swal.fire({
            title: 'Póliza Emitida',
            text: 'No se pudo concluir tu pago, un agente se pondrá en contacto contigo para continuar con el proceso.',
            icon: 'success',
            confirmButtonColor: '#60CDEE',
            confirmButtonText: 'Entendido'
          }).then((result) => {
            if (result.isConfirmed) {

              this.router.navigate(['dashboard']);
            }
          })
        } else if (this.status == 1) { // Aprobado

          Swal.fire({
            title: 'Póliza Emitida',
            text: 'Pago realizado correctamente.',
            icon: 'success',
            confirmButtonColor: '#60CDEE',
            confirmButtonText: 'Entendido'
          }).then((result) => {
            if (result.isConfirmed) {

              this.router.navigate(['dashboard']);
            }
          })
        } else if (this.status == 3) { // Rechazado

          Swal.fire({
            title: 'Póliza Emitida',
            text: 'Tu forma de pago fue rechazada, un agente se pondrá en contacto contigo para continuar con el proceso.',
            icon: 'success',
            confirmButtonColor: '#60CDEE',
            confirmButtonText: 'Entendido'
          }).then((result) => {
            if (result.isConfirmed) {

              this.router.navigate(['dashboard']);
            }
          })
        }
      })
      .catch(({ title, message, code }) => {

        console.log(message)

        Swal.fire({
          icon: 'success',
          title: 'Póliza Emitida',
          text: 'Un agente se pondrá en contacto contigo.',
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