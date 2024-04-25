import { Component } from '@angular/core';

@Component({
  selector: 'app-faqs',
  templateUrl: './faqs.component.html',
  styleUrls: ['./faqs.component.css']
})
export class FaqsComponent {

  panelOpenState = false;


  openFile(file_id: number) {

    var rutaImagen = "";

    switch (file_id) {
      case 1:

        rutaImagen = "./assets/documents/COBERTURAS QUALITAS.pdf";
        break;
      case 2:

        rutaImagen = "./assets/documents/Seguro sobre automoviles y camionetas hasta de 2 y media para uso personal Qualitas.pdf";
        break;
      default:

        rutaImagen = "";
    }

    if (rutaImagen != '')
      var nuevaVentana = window.open(rutaImagen);
  }
}
