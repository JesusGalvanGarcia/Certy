<?php

namespace App\Http\Controllers;

use App\Models\ErrorsLog;
use App\Services\CRMService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CRMController extends Controller
{

    private $prefixCode = 'CRM';
    private $CRM_url = 'https://trinitastest.sugarondemand.com/rest/v11/';

    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        try {

            // Se consulta el token para poder almacenar el Lead
            $token = CRMService::getToken();

            $lead_data = collect([
                "client_id" => $request->client_id,
                "name" => $request->complete_name,
                "email" => $request->email,
                "phone" => $request->phone,
                "age" => $request->age,
                "genre" => $request->genre,
                "process_description" => $request->process_description,
                "model" => $request->model,
                "brand" => $request->brand,
                "vehicle_type" => $request->vehicle_type,
                "vehicle" => $request->vehicle
            ]);

            $response = CRMService::saveLead($token, $lead_data);

            if (!$response)

                return response()->json([
                    'title' => 'Proceso concluido',
                    'message' => 'Ocurrio un error al almacenar el lead, revisar log de errores'
                ]);
            else
                return response()->json([
                    'title' => 'Proceso concluido',
                    'message' => 'Lead actualizado correctamente',
                    'crm_id' => $response['id']
                ]);
            // return $lead;
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X199'
            ]);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        try {

            // Se consulta el token para poder almacenar el Lead
            $token = CRMService::getToken();

            $lead_data = collect([
                "client_id" => $request->client_id,
                "lead_id" => $request->lead_id,
                "name" => $request->complete_name,
                "email" => $request->email,
                "phone" => $request->phone,
                "age" => $request->age,
                "genre" => $request->genre,
                "process_description" => $request->process_description,
                "model" => $request->model,
                "brand" => $request->brand,
                "vehicle_type" => $request->vehicle_type,
                "vehicle" => $request->vehicle,
                "insurer" => $request->insurer
            ]);

            $response =  CRMService::updateLead($token, $lead_data);

            if (!$response)
                return response()->json([
                    'title' => 'Proceso concluido',
                    'message' => 'Ocurrio un error al almacenar el lead, revisar log de errores'
                ]);
            else
                return response()->json([
                    'title' => 'Proceso concluido',
                    'message' => 'Lead actualizado correctamente',
                    'crm_id' => $response->id
                ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X399'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
