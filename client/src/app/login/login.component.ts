import { Component } from '@angular/core';
import { FormBuilder, Validators, FormGroup } from '@angular/forms';

import Swal from 'sweetalert2';
import { Router } from '@angular/router';
import { LoginService } from '../services/login.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent {

  // Form 
  form: FormGroup | any;

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
      password: ['', [Validators.required, Validators.minLength(5), Validators.pattern('^[a-zA-Z0-9]{4,10}$')]]
    })
  }

  validateForm() {

    if (this.form.invalid) { return; }

    this.login();
  }

  login() {

    Swal.fire({
      text: 'Procesando',
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false
    })
    Swal.showLoading()

    const searchData = {
      email: this.form.get('email').value,
      password: this.form.get('password').value
    }

    this.login_service.login(searchData).
      then(({ login }) => {

        window.location.reload();
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

  get invalidPassword() {
    return this.form.get('password').invalid
  }
}
