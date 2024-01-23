<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\ErrorsLog;
use App\Models\Type;
use App\Models\Version;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class CRMService extends ServiceProvider
{

    static function getToken()
    {
        $response = Http::withHeaders([])
            ->timeout(30)
            ->post('https://trinitas.sugarondemand.com/rest/v11/oauth2/token', [
                "grant_type" => "password",
                "client_id" => "sugar",
                "username" => "Jesus.Galvan",
                "password" => "jgalvan2023",
                "platform" => "trinitas-integrate"
            ]);

        $content = json_decode($response, true);
        // Si la respuesta falla se inserta en un log los motivos de las fallas
        if ($response->failed()) {

            foreach ($response['errors'] as $error) {

                ErrorsLog::create([
                    'description' => $error,
                    'http_code' => $response->status(),
                    'module' => 'CRMController',
                    'prefix_code' => 'CRMService'
                ]);
            }

            return false;
        } else {

            return $content['access_token'];
        }
    }

    static function saveLead($token, $lead_data)
    {

        $response = Http::withHeaders([
            'oauth-token' => $token
        ])
            ->timeout(30)
            ->post('https://trinitas.sugarondemand.com/rest/v11/certy_Certy', [
                "id_cliente" => $lead_data['client_id'],
                "nombre" => $lead_data['name'],
                "correo" => $lead_data['email'],
                "fecha_movimiento" => Carbon::now(),
                "estado_proceso" => $lead_data['process_description'],
                "modelo" => $lead_data['model'],
                "marca" => $lead_data['brand'],
                "tipo_vehiculo" => $lead_data['vehicle_type'],
                "vehiculo" => $lead_data['vehicle']
            ]);

        $content = json_decode($response, true);

        // Si la respuesta falla se inserta en un log los motivos de las fallas
        if ($response->failed()) {

            foreach ($response['errors'] as $error) {

                ErrorsLog::create([
                    'description' => $error,
                    'http_code' => $response->status(),
                    'module' => 'CopsisController',
                    'prefix_code' => 'CRMService'
                ]);
            }

            return false;
        } else {

            return $content;
        }
    }

    static function updateLead($token, $lead_data)
    {

        $response = Http::withHeaders([
            'oauth-token' => $token
        ])
            ->timeout(30)
            ->put('https://trinitas.sugarondemand.com/rest/v11/certy_Certy/' . $lead_data['lead_id'], [
                "id_cliente" => $lead_data['client_id'],
                "nombre" => $lead_data['name'],
                "correo" => $lead_data['email'],
                "celular" => $lead_data['phone'],
                "fecha_movimiento" => Carbon::now(),
                "edad" => $lead_data['age'],
                "sexo" => $lead_data['genre'],
                "estado_proceso" => $lead_data['process_description'],
                "modelo" => $lead_data['model'],
                "marca" => $lead_data['brand'],
                "tipo_vehiculo" => $lead_data['vehicle_type'],
                "vehiculo" => $lead_data['vehicle'],
                "aseguradora" => $lead_data['insurer']
            ]);

        $content = json_decode($response, true);

        // Si la respuesta falla se inserta en un log los motivos de las fallas
        if ($response->failed()) {

            foreach ($response['errors'] as $error) {

                ErrorsLog::create([
                    'description' => $error,
                    'http_code' => $response->status(),
                    'module' => 'CopsisController',
                    'prefix_code' => 'CRMService'
                ]);
            }

            return false;
        } else {

            return $content;
        }
    }
}
