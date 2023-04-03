import { Component } from '@angular/core';
import { FormBuilder, Validators, FormGroup } from '@angular/forms';

import Swal from 'sweetalert2';
import { Router } from '@angular/router';
import { LoginService } from '../services/login.service';

@Component({
  selector: 'app-recovery-password',
  templateUrl: './recovery-password.component.html',
  styleUrls: ['./recovery-password.component.css']
})
export class RecoveryPasswordComponent {

  // Form 
  form: FormGroup | any;
  recovery_form: FormGroup | any;

  next_step: boolean = false;
  visibility: boolean = false;

  constructor(
    private login_service: LoginService,
    private router: Router,
    private fb: FormBuilder
  ) {

    if (localStorage.getItem('Certy_token')) {
      router.navigate(['dashboard']);
    }
  }

  ngOnInit(): void {

    this.makeForm();
  }

  makeForm() {

    this.form = this.fb.group({
      email: ['', [Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,3}$')]],
    })

    this.recovery_form = this.fb.group({
      secure_code: ['', [Validators.required, Validators.pattern('\\+?[0-9]{6}')]],
      password: ['', [Validators.required, Validators.pattern('^[a-zA-Z0-9]{4,10}$')]],
    })
  }

  validateForm() {

    if (this.form.invalid) { return; }

    this.sendSecureCode();
  }

  sendSecureCode() {

    Swal.fire({
      text: 'Procesando',
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false
    })
    Swal.showLoading()

    const searchData = {
      email: this.form.get('email').value,
    }

    this.login_service.sendSecureCode(searchData).
      then(({ login }) => {

        this.next_step = true

        Swal.close()
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

  validateRecoveryForm() {

    if (this.recovery_form.invalid) { return; }

    this.actualizePassword();
  }

  actualizePassword() {

    Swal.fire({
      text: 'Procesando',
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false
    })
    Swal.showLoading()

    const searchData = {
      email: this.form.get('email').value,
      secure_code: this.recovery_form.get('secure_code').value,
      password: this.recovery_form.get('password').value,
    }

    this.login_service.actualizePassword(searchData).
      then(({ title, message }) => {

        Swal.fire({
          title: title,
          text: message,
          icon: 'success',
          confirmButtonColor: '#60CDEE',
          confirmButtonText: 'Entendido'
        }).then((result) => {
          if (result.isConfirmed) {

            window.location.reload()
          }
        })
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

  // Getters 

  get invalidEmail() {
    return this.form.get('email').invalid
  }

  get invalidSecureCode() {
    return this.recovery_form.get('secure_code').invalid
  }

  get invalidPassword() {
    return this.recovery_form.get('password').invalid
  }
}
