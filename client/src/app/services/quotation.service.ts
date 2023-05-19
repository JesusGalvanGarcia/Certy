import { Injectable } from '@angular/core';
import axios from 'axios';
import { environment as env } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class QuotationService {

  public api_conect: any

  public copsis_api_conect: any

  constructor() {

    this.copsis_api_conect = axios.create({
      baseURL: 'https://sandbox.moffin.mx/api/v1',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer b41df88be73704d091a3ab28c317f04e115d5c6929401caba4943951e9b59468'
      },

    })

    this.api_conect = axios.create({
      baseURL: env.baseURL,
      headers: {
        'Content-Type': 'application/json',
        // 'Authorization': 'Bearer ' + this.token
      },
    })
  }

  async getQuotations(searchData: any) {

    return this.api_conect.get('/quotations', { params: searchData })
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async storeQuotation(quotationData: any) {

    return this.api_conect.post('/quotations', quotationData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async updateQuotation(quotationData: any) {

    return this.api_conect.put('/quotations/' + quotationData.quotation_id, quotationData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async lastUpdateQuotation(quotationData: any) {

    return this.api_conect.post('/quotations/lastUpdate', quotationData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async getQuotation(searchData: any) {

    return this.api_conect.get('/quotations/' + searchData.quotation_id, { params: searchData })
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        throw data;
      });
  }

  async validateCP(cp: any) {

    return this.copsis_api_conect.get('/postal-codes/' + cp)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async getBrands(searchData: any) {

    return this.api_conect.post('/copsis/brand', searchData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async getTypes(searchData: any) {

    return this.api_conect.post('/copsis/type', searchData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async getVersions(searchData: any) {

    return this.api_conect.post('/copsis/version', searchData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  //////////////// Quotations //////////////////

  async homologation(clientData: any) {

    return this.api_conect.post('/copsis/homologation', clientData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async anaQuotation(searchData: any) {

    return this.api_conect.post('/copsis/anaQuotation', searchData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async chuubQuotation(searchData: any) {

    return this.api_conect.post('/copsis/chuubQuotation', searchData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async primeroQuotation(searchData: any) {

    return this.api_conect.post('/copsis/primeroQuotation', searchData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async primeroEmission(searchData: any) {

    return this.api_conect.post('/copsis/primeroEmission', searchData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async confirmPayment(searchData: any) {

    return this.api_conect.post('/copsis/confirmPayment', searchData)
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
