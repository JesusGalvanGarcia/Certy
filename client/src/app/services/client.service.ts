import { Injectable } from '@angular/core';
import axios from 'axios';
import { environment as env } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ClientService {

  public api_conect: any

  constructor() {

    this.api_conect = axios.create({
      baseURL: env.baseURL,
      headers: {
        'Content-Type': 'application/json',
        // 'Authorization': 'Bearer ' + this.token
      },
    })
  }

  async getClient(client_id: number) {

    return this.api_conect.get('/clients/' + client_id)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async updateClient(client_id: number, clientInfo: any) {

    return this.api_conect.put('/clients/' + client_id, clientInfo)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }
}
