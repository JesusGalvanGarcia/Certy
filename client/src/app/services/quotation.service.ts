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
      baseURL: 'https://app.moffin.mx/api/v1',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer 1a9df5fd728a290a7410cf693ac1a25cccd9bf63e53d0286d764161a48427b37'
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

  async qualitasQuotation(searchData: any) {

    return this.api_conect.post('/copsis/qualitasQuotation', searchData)
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

  async chubbEmission(searchData: any) {

    return this.api_conect.post('/copsis/chubbEmission', searchData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async qualitasEmission(searchData: any) {

    return this.api_conect.post('/copsis/qualitasEmission', searchData)
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

  async downloadPolicy(policyData: any) {

    return this.api_conect.post('/copsis/printPDF', policyData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async saveLead(leadData: any) {

    return this.api_conect.post('/crm', leadData)
      .then(({ data }: any) => {

        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

  async updateLead(leadData: any) {

    return this.api_conect.put('/crm/1', leadData)
      .then(({ data }: any) => {
        console.log(data)
        return data;
      })
      .catch(({ response }: any) => {

        const { data } = response
        console.log(data)
        throw data;
      });
  }

}
