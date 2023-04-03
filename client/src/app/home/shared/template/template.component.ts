import { Component } from '@angular/core';
import { LoginService } from 'src/app/services/login.service';


import Swal from 'sweetalert2'

@Component({
  selector: 'app-template',
  templateUrl: './template.component.html',
  styleUrls: ['./template.component.css']
})
export class TemplateComponent {

  authenticated: boolean = false;
  user_info: any;

  constructor(
    private login_service: LoginService,
  ) {

    if (localStorage.getItem('Certy_token')) {
      this.authenticated = true;

      this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);
    }
  }

  loginListener(status: boolean) {

    if (status) {
      if (localStorage.getItem('Certy_token')) {
        this.authenticated = true;

        this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);
      }
    } else {

      this.Logout();
    }
  }

  Logout() {

    this.login_service.closeSession()
      .then(() => {

        window.location.reload()

      }).catch(({ title, message, code }) => {

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
      });
  }
}
