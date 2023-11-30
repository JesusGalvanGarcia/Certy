import { Component } from '@angular/core';
import { FormBuilder, Validators, FormGroup } from '@angular/forms';

import Swal from 'sweetalert2';
import { Router } from '@angular/router';
import { LoginService } from '../services/login.service';
import { debounceTime } from 'rxjs/operators';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css']
})
export class RegisterComponent {

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
    this.form.get('complete_name').valueChanges
      .pipe(debounceTime(1000))
      .subscribe((value: string) => {
        // Elimina el último espacio en blanco si existe
        this.form.patchValue({ complete_name: value.trim() });
      });
  }

  makeForm() {

    this.form = this.fb.group({
      complete_name: ['', [Validators.required, Validators.pattern('^[A-ZÁÉÍÓÚa-zñáéíóú\u00f1\u00d1\s]{1,}(?: [A-ZÁÉÍÓÚa-zñáéíóú\u00f1\u00d1\s]+){0,6}$')]],
      email: ['', [Validators.required, Validators.pattern('^[A-Za-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,3}$')]],
      password: ['', [Validators.required, Validators.maxLength(20), Validators.pattern('^[a-zA-Z0-9\d@$!%*?.&]{4,}$')]],
      terms: [null, Validators.requiredTrue]
    })
  }

  validateForm() {

    if (this.form.invalid) { return; }

    this.register();
  }

  register() {

    Swal.fire({
      text: 'Procesando',
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false
    })
    Swal.showLoading()

    const searchData = {
      complete_name: this.form.get('complete_name').value,
      email: this.form.get('email').value,
      password: this.form.get('password').value
    }

    this.login_service.register(searchData).
      then(({ login }) => {

        window.location.reload()

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

  // Getters 

  get invalidCompleteName() {
    return this.form.get('complete_name').invalid
  }

  get invalidEmail() {
    return this.form.get('email').invalid
  }

  get invalidPassword() {
    return this.form.get('password').invalid
  }

  get invalidTerms() {

    if (this.form.touched)
      return this.form.get('terms').invalid
  }
}
