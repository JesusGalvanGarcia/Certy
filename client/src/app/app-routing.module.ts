import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { QuotationComponent } from './home/quotation/quotation.component';
import { LoginComponent } from './login/login.component';
import { RecoveryPasswordComponent } from './recovery-password/recovery-password.component';
import { RegisterComponent } from './register/register.component';
import { DashboardComponent } from './home/dashboard/dashboard.component';
import { AuthGuard } from './guards/auth.guard';
import { QuotationsComponent } from './home/quotations/quotations.component';

const routes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: 'registro', component: RegisterComponent },
  { path: 'recuperacion_contraseña', component: RecoveryPasswordComponent },
  { path: 'cotizacion', component: QuotationComponent },
  // { path: 'cotizaciones', component: QuotationsComponent },
  { path: 'dashboard', component: DashboardComponent, canActivate: [AuthGuard] },
  { path: '', redirectTo: 'cotizacion', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
