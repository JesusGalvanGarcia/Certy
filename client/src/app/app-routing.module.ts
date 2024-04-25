import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { QuotationComponent } from './home/quotation/quotation.component';
import { LoginComponent } from './login/login.component';
import { RecoveryPasswordComponent } from './recovery-password/recovery-password.component';
import { RegisterComponent } from './register/register.component';
import { DashboardComponent } from './home/dashboard/dashboard.component';
import { AuthGuard } from './guards/auth.guard';
import { AccountStatusComponent } from './home/account-status/account-status.component';
import { FaqsComponent } from './home/faqs/faqs.component';
import { HelpsComponent } from './home/helps/helps.component';
import { ControlComponent } from './home/control/control.component';
import { UsComponent } from './home/us/us.component';
import { CoverageComponent } from './home/coverage/coverage.component';

const routes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: 'registro', component: RegisterComponent },
  { path: 'recuperacion_contraseña', component: RecoveryPasswordComponent },
  { path: 'proceso/:policy_id', component: ControlComponent },
  { path: 'cotizacion', component: QuotationComponent },
  { path: 'faqs', component: FaqsComponent },
  { path: 'coberturas', component: CoverageComponent },
  { path: 'nosotros', component: UsComponent },
  { path: 'ayuda', component: HelpsComponent },
  // { path: 'estado-de-cuenta', component: AccountStatusComponent, canActivate: [AuthGuard] },

  { path: 'cotizacion/:quotation_id', component: QuotationComponent },
  { path: 'dashboard', component: DashboardComponent, canActivate: [AuthGuard] },
  { path: '', redirectTo: 'cotizacion', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
