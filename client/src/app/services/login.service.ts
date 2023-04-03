import { Injectable } from '@angular/core';
import axios from 'axios';
import { environment as env } from 'src/environments/environment';
import { Router } from '@angular/router';

@Injectable({
  providedIn: 'root'
})
export class LoginService {

  public api_conect: any

  userToken: string | null = ''

  constructor(

    private router: Router
  ) {

    this.readToken();

    this.api_conect = axios.create({
      baseURL: env.baseURL,
      headers: {
        'Content-Type': 'application/json',
        // 'Authorization': 'Bearer ' + this.token
      },

    })
  }

  async login(userData: any) {

    return this.api_conect.post('/login', userData)
      .then(({ data }: any) => {

        this.saveToken(data.token, data.data)

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async register(userData: any) {

    return this.api_conect.post('/register', userData)
      .then(({ data }: any) => {

        this.saveToken(data.token, data.data)

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async sendSecureCode(userData: any) {

    return this.api_conect.post('/sendSecureCode', userData)
      .then(({ data }: any) => {

        this.saveToken(data.token, data.data)

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async actualizePassword(userData: any) {

    return this.api_conect.post('/actualizePassword', userData)
      .then(({ data }: any) => {

        this.saveToken(data.token, data.data)

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  private saveToken(idToken: string, user_data: any) {

    this.userToken = idToken
    localStorage.setItem('Certy_token', idToken)
    localStorage.setItem('Certy_user_info', JSON.stringify(user_data))
  }

  readToken() {

    if (localStorage.getItem('Certy_token')) {

      this.userToken = localStorage.getItem('Certy_token')
    } else {

      this.userToken = ''
    }

    return this.userToken;
  }

  isAuthenticated(): boolean {

    if (localStorage.getItem('Certy_token')) {

      return true;
    } else {

      return false;
    }
  }

  async closeSession() {

    localStorage.removeItem('Certy_token')
    localStorage.removeItem('Certy_user_info')

    window.location.reload()

  }
}
