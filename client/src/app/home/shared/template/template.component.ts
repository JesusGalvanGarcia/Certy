import { Component } from '@angular/core';
import { LoginService } from 'src/app/services/login.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';


import Swal from 'sweetalert2'

@Component({
  selector: 'app-template',
  templateUrl: './template.component.html',
  styleUrls: ['./template.component.css']
})
export class TemplateComponent {

  authenticated: boolean = false;
  user_info: any;
  showCotizaButton: any;

  constructor(
    private login_service: LoginService,
    private router: Router
  ) {

    if (localStorage.getItem('Certy_token')) {
      this.authenticated = true;

      this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);
    }

    this.router.events.subscribe((event) => {
      if (event instanceof NavigationEnd) {
        // Verifica la ruta actual y oculta el botón según sea necesario
        this.showCotizaButton = !this.isCotizacionRoute();
      }
    });
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

  private isCotizacionRoute(): boolean {
    // Obtiene la ruta actual desde el servicio Router
    const currentRoute = this.router.url;

    // Verifica si la ruta actual contiene "/cotizacion"
    return currentRoute.includes('/cotizacion');
  }
}
