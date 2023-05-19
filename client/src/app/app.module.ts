import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { TemplateComponent } from './home/shared/template/template.component';

import { MatStepperModule } from '@angular/material/stepper';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { MatButtonModule } from '@angular/material/button';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatTableModule } from '@angular/material/table';
import { MatSortModule } from '@angular/material/sort';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatInputModule } from '@angular/material/input';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatGridListModule } from '@angular/material/grid-list';
import { MatDialogModule } from '@angular/material/dialog';
import { MatNativeDateModule, MatRippleModule } from '@angular/material/core';
import { MatCardModule } from '@angular/material/card';
import { MatBadgeModule } from '@angular/material/badge';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatChipsModule } from '@angular/material/chips';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MatTabsModule } from '@angular/material/tabs';
import { MatSelectModule } from '@angular/material/select';
import { MatDividerModule } from '@angular/material/divider';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatButtonToggleModule } from '@angular/material/button-toggle';
import { MatRadioModule } from '@angular/material/radio';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatListModule } from '@angular/material/list';

import { LocationStrategy, HashLocationStrategy } from '@angular/common';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { QuotationComponent } from './home/quotation/quotation.component';
import { LoginComponent } from './login/login.component';
import { RegisterComponent } from './register/register.component';
import { RecoveryPasswordComponent } from './recovery-password/recovery-password.component';
import { DashboardComponent } from './home/dashboard/dashboard.component';
import { QuotationCardComponent } from './home/shared/quotation-card/quotation-card.component';
import { PolicyCardComponent } from './home/shared/policy-card/policy-card.component';
import { AccountStatusComponent } from './home/account-status/account-status.component';
import { FaqsComponent } from './home/faqs/faqs.component';
import { HelpsComponent } from './home/helps/helps.component';
import { ControlComponent } from './home/control/control.component';

@NgModule({
  declarations: [
    AppComponent,
    TemplateComponent,
    QuotationComponent,
    LoginComponent,
    RegisterComponent,
    RecoveryPasswordComponent,
    DashboardComponent,
    QuotationCardComponent,
    PolicyCardComponent,
    AccountStatusComponent,
    FaqsComponent,
    HelpsComponent,
    ControlComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    BrowserAnimationsModule,
    MatStepperModule,
    MatIconModule,
    BrowserModule,
    AppRoutingModule,
    BrowserAnimationsModule,
    MatMenuModule,
    MatIconModule,
    MatButtonModule,
    MatExpansionModule,
    MatTableModule,
    MatSortModule,
    MatPaginatorModule,
    MatInputModule,
    MatDatepickerModule,
    MatFormFieldModule,
    MatGridListModule,
    MatDialogModule,
    MatRippleModule,
    MatProgressSpinnerModule,
    MatNativeDateModule,
    MatCardModule,
    MatBadgeModule,
    MatAutocompleteModule,
    MatChipsModule,
    FormsModule,
    ReactiveFormsModule,
    MatTabsModule,
    MatSelectModule,
    MatDividerModule,
    MatTooltipModule,
    MatSlideToggleModule,
    MatButtonToggleModule,
    MatRadioModule,
    MatCheckboxModule,
    MatListModule
  ],
  providers: [
    { provide: LocationStrategy, useClass: HashLocationStrategy }
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
