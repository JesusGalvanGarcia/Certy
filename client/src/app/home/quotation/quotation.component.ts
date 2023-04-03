import { AfterViewInit, Component, EventEmitter, Output, ViewChild } from '@angular/core';
import { FormBuilder, Validators, FormControl } from '@angular/forms';
import { BreakpointObserver } from '@angular/cdk/layout';
import { StepperOrientation } from '@angular/material/stepper';
import { Observable } from 'rxjs';
import { map, startWith } from 'rxjs/operators';

import Swal from 'sweetalert2';
import { QuotationService } from 'src/app/services/quotation.service';
import { ClientService } from 'src/app/services/client.service';
import { Router } from '@angular/router';

import { MatSort, Sort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { LoginService } from '../../services/login.service';
import { TemplateComponent } from '../shared/template/template.component';

@Component({
  selector: 'app-quotation',
  templateUrl: './quotation.component.html',
  styleUrls: ['./quotation.component.css']
})
export class QuotationComponent {

  user_info: any
  client: any

  // Initial Page  
  initial_page: boolean = true
  brands: any
  types: any
  versions: any

  userFormGroup: any = this._formBuilder.group({
    complete_name: ['', [Validators.required, Validators.minLength(5), Validators.pattern('^(?!.* $)[A-ZÁÉÍÓÚa-zñáéíóú\u00f1\u00d1]+(?: [A-ZÁÉÍÓÚa-zñáéíóú\u00f1\u00d1]+)(?: [A-ZÁÉÍÓÚa-zñáéíóú\u00f1\u00d1]+)?(?:[A-ZÁÉÍÓÚa-zñáéíóú\u00f1\u00d1]+)?(?:[A-ZÁÉÍÓÚa-zñáéíóú\u00f1\u00d1]+)?$')]],
    email: ['', [Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,3}$')]],
    cellphone: ['', [Validators.required, Validators.pattern('[0-9]{10}')]],
    age: ['', [Validators.required, Validators.min(18), Validators.pattern('[0-9]{1,2}')]],
    cp: ['', [Validators.required, Validators.pattern('\\+?[0-9]{5}')]],
    genre: ['', [Validators.required]]
  });

  vehicleFormGroup: any = this._formBuilder.group({
    model: ['', [Validators.required, Validators.pattern('\\+?[0-9]{4}')]],
    brand_id: ['', [Validators.required, Validators.pattern('\\+?[0-9]{1,3}')]],
    type: ['', [Validators.required]],
    version: ['', [Validators.required]],
    unit_type: ['AUTO', [Validators.required]]
  });

  lastVehicleFormGroup: any = this._formBuilder.group({
    serial_no: ['', [Validators.required, Validators.minLength(15), Validators.maxLength(17), Validators.pattern('^[a-zA-Z0-9]*$')]],
    plate_no: ['', [Validators.required, Validators.pattern('[A-Z0-9]{7}')]],
    motor_no: ['', [Validators.required, Validators.pattern('[a-zA-Z0-9]{5,17}$')]],
  });

  lastUserFormGroup: any = this._formBuilder.group({
    rfc: ['', [Validators.required, Validators.maxLength(13), Validators.minLength(12), Validators.pattern('^([A-ZÑ\x26]{3,4}([0-9]{2})(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1]))([A-Z0-9]{3})?$')]],
    township: ['', [Validators.required, Validators.minLength(5)]],
    state: ['', [Validators.required, Validators.minLength(5)]],
    street: ['', [Validators.required, Validators.minLength(5)]],
    street_number: ['', [Validators.pattern('^[a-zA-z0-9-]+$')]],
    int_street_number: ['', [Validators.pattern('^[a-zA-z0-9-]+$')]]
  });

  stepperOrientation: Observable<StepperOrientation>;

  //////////////// Quotations ////////////////////////

  quotations: boolean = false

  quotation_selected: any;

  payment_frequency: string = 'MENSUAL'

  quoter_name: string = ''
  quoter_pack: string = "1"

  quoters_packs: any = ['base', 'Amplia', 'AMPLIA PLUS', 'AMPLIA PLUS 3%', 'LIMITADA', 'RC']

  anaInfo: any
  chuubInfo: any
  primeroInfo: any

  anaLoading: boolean = true
  chuubLoading: boolean = true
  primeroLoading: boolean = true

  anaActive: boolean = false
  chuubActive: boolean = false
  primeroActive: boolean = false

  ana_quotation: any
  chuub_quotation: any
  primero_quotation: any

  @ViewChild(MatSort) AnaSort: MatSort | any;
  @ViewChild(MatSort) ChuubSort: MatSort | any;
  @ViewChild(MatSort) PrimeroSort: MatSort | any;

  // Table
  AnaDisplayedColumns: string[] = ['cover', 'sa', 'deductible'];
  AnaDataSource: MatTableDataSource<AnaData> | any = [];

  ChuubDisplayedColumns: string[] = ['cover', 'sa', 'deductible'];
  ChuubDataSource: MatTableDataSource<ChuubData> | any = [];

  PrimeroDisplayedColumns: string[] = ['cover', 'sa', 'deductible'];
  PrimeroDataSource: MatTableDataSource<PrimeroData> | any = [];

  // Login | Register

  @Output() loginController: EventEmitter<number> = new EventEmitter<number>();
  @Output() newItemEvent = new EventEmitter<any>();

  visibility: boolean = false;
  next_step: boolean = false;

  display_register_modal: string = 'none'
  display_login_modal: string = 'none'
  display_recovery_modal: string = 'none'

  loginFormGroup: any = this._formBuilder.group({
    email: ['', [Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,3}$')]],
    password: ['', [Validators.required, Validators.minLength(5), Validators.pattern('^[a-zA-Z0-9]{4,10}$')]]
  });

  registerFormGroup: any = this._formBuilder.group({
    complete_name: ['', [Validators.required, Validators.minLength(5), Validators.pattern('^(?!.* $)[A-ZÁÉÍÓÚa-zñáéíóú]+(?: [A-ZÁÉÍÓÚa-zñáéíóú]+)(?: [A-ZÁÉÍÓÚa-zñáéíóú]+)?(?:[A-ZÁÉÍÓÚa-zñáéíóú]+)?(?:[A-ZÁÉÍÓÚa-zñáéíóú]+)?$')]],
    email: ['', [Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,3}$')]],
    password: ['', [Validators.required, Validators.minLength(5), Validators.pattern('^[a-zA-Z0-9]{4,10}$')]],
    terms: [null, Validators.requiredTrue]
  });

  recoveryFormGroup: any = this._formBuilder.group({
    email: ['', [Validators.required, Validators.pattern('^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,3}$')]]
  });

  actualizeFormGroup: any = this._formBuilder.group({
    secure_code: ['', [Validators.required, Validators.pattern('\\+?[0-9]{6}')]],
    password: ['', [Validators.required, Validators.pattern('^[a-zA-Z0-9]{4,10}$')]],
  });


  ////////////////////////// Last Data Page ////////////////////////////

  last_data_page: boolean = false;

  ////////////////////////// Pay Page ////////////////////////////

  pay_page: boolean = false;

  // Suburb control
  suburbControl: FormControl = new FormControl('', [Validators.required, Validators.minLength(5)]);
  filteredSuburbs: Observable<string[]> | any;

  suburbs: any = []

  constructor(
    private _quotationService: QuotationService,
    private _clientService: ClientService,
    private _loginService: LoginService,
    private _templateComponent: TemplateComponent,
    private _formBuilder: FormBuilder, breakpointObserver: BreakpointObserver,
    private router: Router
  ) {

    if (localStorage.getItem('Certy_token')) {
      this.user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);

      this.getClient()
    }

    this.stepperOrientation = breakpointObserver
      .observe('(min-width: 800px)')
      .pipe(map(({ matches }) => (matches ? 'horizontal' : 'vertical')));
  }


  ngAfterViewInit() {

    this.AnaDataSource.sort = this.AnaSort;
    this.ChuubDataSource.sort = this.ChuubSort;
    this.PrimeroDataSource.sort = this.PrimeroSort;
  }

  getClient() {
    Swal.fire({
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      text: 'Procesando'
    })
    Swal.showLoading()

    this._clientService.getClient(this.user_info.user_id).
      then(({ client }) => {

        this.client = client

        this.userFormGroup.patchValue({
          complete_name: client.complete_name,
          email: client.email,
          cellphone: client.cellphone,
          age: client.age,
          cp: client.cp,
          genre: client.genre
        })

        this.lastUserFormGroup.patchValue({
          rfc: client.rfc,
          state: client.state,
          township: client.township,
          street_number: client.street_number,
          int_street_number: client.int_street_number
        })

        this.suburbControl.patchValue(client.suburb)

        if (client.cp) {

          this.validateCp();
        }

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

  getBrands() {
    Swal.fire({
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      text: 'Procesando'
    })

    Swal.showLoading()

    const searchData = {
      model: this.vehicleFormGroup.get('model').value,
      unit_type: 'AUTO'
    }

    this.vehicleFormGroup.patchValue({
      brand_id: '',
      type: '',
      version: ''
    })

    this._quotationService.getBrands(searchData).
      then(({ brands }) => {

        this.brands = brands
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

  getTypes() {
    Swal.fire({
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      text: 'Procesando'
    })
    Swal.showLoading()

    const searchData = {
      model: this.vehicleFormGroup.get('model').value,
      brand_id: this.vehicleFormGroup.get('brand_id').value,
      unit_type: 'AUTO'
    }

    this.vehicleFormGroup.patchValue({
      type: '',
      version: ''
    })

    this._quotationService.getTypes(searchData).
      then(({ types }) => {

        this.types = types;
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

  getVersions() {
    Swal.fire({
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      text: 'Procesando'
    })
    Swal.showLoading()

    const searchData = {
      model: this.vehicleFormGroup.get('model').value,
      brand_id: this.vehicleFormGroup.get('brand_id').value,
      type: this.vehicleFormGroup.get('type').value,
      unit_type: 'AUTO'
    }

    this.vehicleFormGroup.patchValue({
      version: ''
    })

    this._quotationService.getVersions(searchData).
      then(({ versions }) => {

        this.versions = versions;
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

  otherCar() {

    this.quotations = false;
    this.initial_page = true;

    this.chuubLoading = true;
    this.primeroLoading = true;
    this.anaLoading = true;

    this.vehicleFormGroup.patchValue({
      model: '',
      brand_id: '',
      type: '',
      version: ''
    })
  }

  validateCp() {

    if (this.userFormGroup.get('cp').invalid)
      return

    this._quotationService.validateCP(this.userFormGroup.get('cp').value).
      then((place_data) => {

        for (let place of place_data) {

          this.lastUserFormGroup.patchValue({
            state: place.state,
            township: place.municipality
          })

          this.suburbs.push(place.neighborhood)
        }

        this.filteredSuburbs = this.suburbControl.valueChanges.pipe(
          startWith(null),
          map((suburb: string | null) => ((suburb) ? this._filter(suburb) : this.suburbs.slice())),
        );

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

  private _filter(value: string): string[] {
    const filterValue = value.toLowerCase();

    return this.suburbs.filter((suburb: any) => suburb.toLowerCase().includes(filterValue));
  }

  validateForms() {

    if (this.userFormGroup.invalid || this.vehicleFormGroup.invalid) { return; }

    this.toQuotations();
  }

  toQuotations() {

    this.quotations = true
    this.initial_page = false

    Swal.fire({
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      text: 'Procesando'
    })
    Swal.showLoading()

    const clientData: any = {
      complete_name: this.userFormGroup.get('complete_name').value,
      email: this.userFormGroup.get('email').value,
      cellphone: this.userFormGroup.get('cellphone').value,
      age: this.userFormGroup.get('age').value,
      cp: this.userFormGroup.get('cp').value,
      genre: this.userFormGroup.get('genre').value,
      amis: this.vehicleFormGroup.get('version').value,
      model: this.vehicleFormGroup.get('model').value,
      unit_type: this.vehicleFormGroup.get('unit_type').value
    };

    this._quotationService.homologation(clientData).
      then(({ ana, chuub, primero }) => {

        this.anaInfo = ana;
        this.chuubInfo = chuub;
        this.primeroInfo = primero;

        this.chuubQuotation();
        this.primeroQuotation();
        this.anaQuotation();

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

  ////////////////////////////////////// QUOTATIONS //////////////////////////////////


  chuubQuotation() {

    this.chuubLoading = true;

    const searchData = {
      brand_id: this.chuubInfo.negocioID,
      pack: this.chuubInfo.paquetes.find((paquete: any) => paquete.orden == this.quoter_pack).paqueteID,
      payment_frequency: this.payment_frequency,
      vehicle: this.chuubInfo.vehiculo,
      age: this.userFormGroup.get('age').value,
      genre: this.userFormGroup.get('genre').value,
      cp: this.userFormGroup.get('cp').value
    }

    this._quotationService.chuubQuotation(searchData).
      then(({ chuub_quotation }) => {

        this.chuub_quotation = chuub_quotation;

        this.ChuubDataSource = new MatTableDataSource(chuub_quotation.coberturas);
        this.ChuubDataSource.sort = this.ChuubSort;

        this.chuubLoading = false;
        this.chuubActive = true;
      })
      .catch(({ title, message, code }) => {

        console.log(message)

        this.chuubLoading = false;
      })
  }

  primeroQuotation() {

    this.primeroLoading = true;

    const searchData = {
      brand_id: this.primeroInfo.negocioID,
      pack: this.primeroInfo.paquetes.find((paquete: any) => paquete.orden == this.quoter_pack).paqueteID,
      payment_frequency: this.payment_frequency,
      vehicle: this.primeroInfo.vehiculo,
      age: this.userFormGroup.get('age').value,
      genre: this.userFormGroup.get('genre').value,
      cp: this.userFormGroup.get('cp').value
    }

    this._quotationService.primeroQuotation(searchData).
      then(({ primero_quotation }) => {

        this.primero_quotation = primero_quotation;

        this.PrimeroDataSource = new MatTableDataSource(primero_quotation.coberturas);
        this.PrimeroDataSource.sort = this.PrimeroSort;

        this.primeroLoading = false;

        this.primeroActive = true;
      })
      .catch(({ title, message, code }) => {
        console.log(message)

        this.primeroLoading = false;
      })
  }

  anaQuotation() {

    this.anaLoading = true;

    const searchData = {
      brand_id: this.anaInfo.negocioID,
      pack: this.anaInfo.paquetes.find((paquete: any) => paquete.orden == this.quoter_pack).paqueteID,
      payment_frequency: this.payment_frequency,
      vehicle: this.anaInfo.vehiculo,
      age: this.userFormGroup.get('age').value,
      genre: this.userFormGroup.get('genre').value,
      cp: this.userFormGroup.get('cp').value
    }

    this._quotationService.anaQuotation(searchData).
      then(({ ana_quotation }) => {

        this.ana_quotation = ana_quotation;

        this.AnaDataSource = new MatTableDataSource(ana_quotation.coberturas);
        this.AnaDataSource.sort = this.AnaSort;

        this.anaLoading = false;
        this.anaActive = true;
      })
      .catch(({ title, message, code }) => {
        console.log(message)

        this.anaLoading = false;
      })
  }

  selectQuotation(quoter: number) {

    switch (quoter) {

      case 1:
        this.quoter_name = 'CHUUB';
        this.quotation_selected = this.chuub_quotation;
        break;

      case 2:

        this.quoter_name = 'PRIMERO';
        this.quotation_selected = this.primero_quotation;
        break;

      case 3:

        this.quoter_name = 'ANA';
        this.quotation_selected = this.ana_quotation;
        break;
    }

  }
  ////////////////////////////////////// Login | Register //////////////////////////////////

  checkSession() {

    if (this._loginService.isAuthenticated()) {

      this.quotations = false;
      this.last_data_page = true;
    } else {

      this.loginFormGroup.patchValue({
        email: this.userFormGroup.get('email').value
      });

      this.registerFormGroup.patchValue({
        complete_name: this.userFormGroup.get('complete_name').value,
        email: this.userFormGroup.get('email').value
      });

      this.recoveryFormGroup.patchValue({
        email: this.userFormGroup.get('email').value
      });

      this.display_login_modal = 'block';

    }
  }

  /*********** Login ************/
  openLoginModal() {
    this.display_login_modal = 'block';
    this.display_register_modal = 'none';
    this.display_recovery_modal = 'none';
  }

  closeLoginModal() {
    this.display_login_modal = 'none';
  }

  validateLoginForm() {

    if (this.loginFormGroup.invalid) { return; }

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
      email: this.loginFormGroup.get('email').value,
      password: this.loginFormGroup.get('password').value
    }

    this._loginService.login(searchData).
      then(({ login }) => {

        this._templateComponent.loginListener(true);

        this.quotations = false;
        this.last_data_page = true;

        Swal.close();
        this.closeLoginModal();
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

  get invalidLoginEmail() {
    return this.loginFormGroup.get('email').invalid
  }

  get invalidLoginPassword() {
    return this.loginFormGroup.get('password').invalid
  }

  /*********** Register ************/
  openRegisterModal() {
    this.display_register_modal = 'block';
    this.display_login_modal = 'none';
    this.display_recovery_modal = 'none';
  }

  closeRegisterModal() {
    this.display_register_modal = 'none';
  }

  validateRegisterForm() {

    if (this.registerFormGroup.invalid) { return; }

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
      complete_name: this.registerFormGroup.get('complete_name').value,
      email: this.registerFormGroup.get('email').value,
      password: this.registerFormGroup.get('password').value
    }

    this._loginService.register(searchData).
      then(({ register }) => {

        this._templateComponent.loginListener(true);

        this.quotations = false;
        this.last_data_page = true;

        Swal.close();
        this.closeRegisterModal();
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

  get invalidRegisterCompleteName() {
    return this.registerFormGroup.get('complete_name').invalid
  }

  get invalidRegisterEmail() {
    return this.registerFormGroup.get('email').invalid
  }

  get invalidRegisterPassword() {
    return this.registerFormGroup.get('password').invalid
  }

  get invalidTerms() {

    if (this.registerFormGroup.touched)
      return this.registerFormGroup.get('terms').invalid
  }

  /*********** Recovery ************/
  openRecoveryModal() {
    this.display_recovery_modal = 'block';
    this.display_register_modal = 'none';
    this.display_login_modal = 'none';
  }

  closeRecoveryModal() {
    this.display_recovery_modal = 'none';
  }

  validateRecoveryForm() {

    if (this.recoveryFormGroup.invalid) { return; }

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
      email: this.recoveryFormGroup.get('email').value,
    }

    this._loginService.sendSecureCode(searchData).
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

  validateActualizeForm() {

    if (this.actualizeFormGroup.invalid) { return; }

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
      email: this.recoveryFormGroup.get('email').value,
      secure_code: this.actualizeFormGroup.get('secure_code').value,
      password: this.actualizeFormGroup.get('password').value,
    }

    this._loginService.actualizePassword(searchData).
      then(({ title, message }) => {

        Swal.fire({
          title: title,
          text: message,
          icon: 'success',
          confirmButtonColor: '#60CDEE',
          confirmButtonText: 'Entendido'
        }).then((result) => {
          if (result.isConfirmed) {

            this._templateComponent.loginListener(true);

            this.quotations = false;
            this.last_data_page = true;

            Swal.close();
            this.closeRecoveryModal();
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

  get invalidRecoveryEmail() {
    return this.recoveryFormGroup.get('email').invalid
  }

  get invalidSecureCode() {
    return this.actualizeFormGroup.get('secure_code').invalid
  }

  get invalidActualizePassword() {
    return this.actualizeFormGroup.get('password').invalid
  }

  ////////////////////////////////////// Pay Page //////////////////////////////////

  validateLastForms() {

    if (this.lastVehicleFormGroup.invalid || this.lastUserFormGroup.invalid || this.suburbControl.invalid) { return; }

    this.toPayPage();
  }

  toPayPage() {

    Swal.fire({
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      text: 'Procesando'
    })
    Swal.showLoading()

    let user_info = JSON.parse(localStorage.getItem('Certy_user_info')!);

    let clientInfo = {
      rfc: this.lastUserFormGroup.get('rfc').value,
      township: this.lastUserFormGroup.get('township').value,
      state: this.lastUserFormGroup.get('state').value,
      street_number: this.lastUserFormGroup.get('street_number').value,
      int_street_number: this.lastUserFormGroup.get('int_street_number').value
    }

    this._clientService.updateClient(user_info.user_id, clientInfo)
      .then((client) => {

        this.last_data_page = false;
        this.pay_page = true;
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
  // Getters 

  get invalidCompleteName() {
    return this.userFormGroup.get('complete_name').invalid
  }

  get invalidEmail() {
    return this.userFormGroup.get('email').invalid
  }

  get invalidCellphone() {
    return this.userFormGroup.get('cellphone').invalid
  }

  get invalidAge() {
    return this.userFormGroup.get('age').invalid
  }

  get invalidCp() {
    return this.userFormGroup.get('cp').invalid
  }

  get invalidSerialNumber() {
    return this.lastVehicleFormGroup.get('serial_no').invalid
  }

  get invalidPlateNumber() {
    return this.lastVehicleFormGroup.get('plate_no').invalid
  }

  get invalidMotorNumber() {
    return this.lastVehicleFormGroup.get('motor_no').invalid
  }

  get invalidRFC() {
    return this.lastUserFormGroup.get('rfc').invalid
  }

  get invalidStreet() {
    return this.lastUserFormGroup.get('street').invalid
  }

  get invalidSuburb() {
    return this.suburbControl.invalid
  }

  get invalidTownship() {
    return this.lastUserFormGroup.get('township').invalid
  }

  get invalidState() {
    return this.lastUserFormGroup.get('state').invalid
  }

  get invalidStreetNumber() {
    return this.lastUserFormGroup.get('street_number').invalid
  }

  get invalidIntStreetNumber() {
    return this.lastUserFormGroup.get('int_street_number').invalid
  }
}

export interface AnaData {
  name: string;
  position: number;
  weight: number;
  symbol: string;
}

export interface ChuubData {
  name: string;
  position: number;
  weight: number;
  symbol: string;
}

export interface PrimeroData {
  name: string;
  position: number;
  weight: number;
  symbol: string;
}