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
  Aut: any
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
        this.Aut = params.Aut ? params.Aut : null;
      });

    this.validateResponse();
  }

  validateResponse() {

    const searchData = {
      policy_id: this.policy_id,
      status_id: this.status ? this.status : this.Aut ? this.Aut : 0,
    }

    this._quotationService.confirmPayment(searchData).
      then(({ title, message }) => {

        if (this.status) {
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
        } else {

          // Se evalúa la respuesta de Chubb
          if (this.Aut == 0 || this.Aut == 4 || this.Aut == 5 || this.Aut == 6) { // Error

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
          } else if (this.Aut == 1 || this.Aut == 2 || this.Aut == 3) { // Aprobado
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
          }
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