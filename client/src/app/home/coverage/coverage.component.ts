import { Component } from '@angular/core';

@Component({
  selector: 'app-coverage',
  templateUrl: './coverage.component.html',
  styleUrls: ['./coverage.component.css']
})
export class CoverageComponent {


  openCoverageImage(insurer: number) {

    var rutaImagen = "";

    switch (insurer) {
      case 1:

        rutaImagen = "./assets/images/COBERTURAS_QUALITAS_IMAGEN.jpg";
        break;
      case 2:

        rutaImagen = "./assets/images/COBERTURAS_PRIMERO_IMAGEN.jpg";
        break;
      case 3:
        rutaImagen = "./assets/images/COBERTURAS_CHUBB.jpg";
        break;
      default:

        rutaImagen = "";
    }

    if (rutaImagen != '')
      var nuevaVentana = window.open(rutaImagen);
  }
}
