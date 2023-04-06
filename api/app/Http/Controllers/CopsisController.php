<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\ErrorsLog;
use App\Models\Type;
use App\Models\Version;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Services\CopsisService;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Validator;

class CopsisController extends Controller
{

    private $prefixCode = 'Copsis';

    public function consultBrand(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'model' => 'Required|Integer|NotIn:0|Digits:4',
                'unit_type' => 'Required|String'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X001'
                ], 400);

            // Conexión con Copsis, el valor aseguradora es fijo
            try {

                $brand = Http::withHeaders([
                    'x-api-key' => env('api_key_uat'),
                    'Accept' => 'application/json'
                ])
                    ->timeout(5)
                    ->post('https://apiuat.quattrocrm.mx/autos/publics/busqueda/marca', [
                        'aseguradora' => "CHUBB",
                        'modelo' => $request->model,
                        'grupoUnidades' => [$request->unit_type]
                    ]);

                // Se trata la respuesta para poder leerla como un objeto
                $response = json_decode($brand, true);

                // Si la respuesta falla se inserta en un log los motivos de las fallas
                if ($brand->failed()) {

                    foreach ($response['errors'] as $error) {

                        ErrorsLog::create([
                            'description' => $error,
                            'http_code' => $brand->status(),
                            'module' => 'CopsisController',
                            'prefix_code' => $this->prefixCode . 'X002'
                        ]);
                    }

                    // Se consulta si se tienen marcas almacenadas con el modelo solicitado.
                    $brands = CopsisService::checkBrand($request->model);

                    //Si no se encuentran marcas se responde avisa al usuario del error
                    if ($brands->count() <= 0)
                        return response()->json([
                            'title' => 'Error en Copsis',
                            'message' => $response['errors'],
                            'code' => $this->prefixCode . 'X002'
                        ], 400);
                } else {

                    // Si la respuesta es correcta se asigna la respuesta de Copsis.
                    $brands = $response['result'];

                    // Se evalua si las marcas ya existen en la base de datos actual
                    $old_brands = Brand::whereIn('brand_id', collect($brands)->pluck('marca'))->where('model', $request->model)->get();

                    $new_brands = collect($brands)->pluck('marca')->diff($old_brands->pluck('brand_id'));

                    $new_brands = collect($brands)->whereIn('marca', $new_brands);

                    // Se insertan las nuevas marcas consultadas
                    CopsisService::storeNewBrands($new_brands, $request->model);
                }
            } catch (Exception $e) {

                ErrorsLog::create([
                    'description' => $e->getMessage() . '-L:' . $e->getLine(),
                    'http_code' => 500,
                    'module' => 'CopsisController',
                    'prefix_code' => $this->prefixCode . 'X003'
                ]);

                // Se consulta si se tienen marcas almacenadas con el modelo solicitado.
                $brands = CopsisService::checkBrand($request->model);

                //Si no se encuentran marcas se responde avisa al usuario del error
                if ($brands->count() <= 0) {

                    ErrorsLog::create([
                        'description' => 'Error en Copsis. Las marcas no se pudieron consultar y no se tiene respaldo de este modelo.',
                        'http_code' => 404,
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X003'
                    ]);

                    return response()->json([
                        'title' => 'Ooops',
                        'message' => 'No se encontró información coincidente al año de su vehículo.',
                        'code' => $this->prefixCode . 'X004'
                    ], 400);
                }
            }

            return response()->json([
                'title' => 'Proceso correcto',
                'message' => 'Marcas consultadas correctamente',
                'brands' => $brands
            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X099'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X099'
            ], 500);
        }
    }

    public function consultType(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'model' => 'Required|Integer|NotIn:0|Digits:4',
                'brand_id' => 'Required|Integer|NotIn:0|MaxDigits:4',
                'unit_type' => 'Required|String'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X101'
                ], 400);

            // Conexión con Copsis, el valor de aseguradora es fijo
            try {

                $type = Http::withHeaders([
                    'x-api-key' => env('api_key_uat')
                ])
                    ->timeout(5)
                    ->post('https://apiuat.quattrocrm.mx/autos/publics/busqueda/tipo', [
                        'aseguradora' => "CHUBB",
                        'modelo' => $request->model,
                        'marca' =>  $request->brand_id,
                        'grupoUnidades' => [$request->unit_type]
                    ]);

                // Se trata la respuesta para poder leerla como un objeto
                $response = json_decode($type, true);

                // Si la respuesta falla se inserta en un log los motivos de las fallas
                if ($type->failed()) {

                    foreach ($response['errors'] as $error) {

                        ErrorsLog::create([
                            'description' => $error,
                            'http_code' => $type->status(),
                            'module' => 'CopsisController',
                            'prefix_code' => $this->prefixCode . 'X102'
                        ]);
                    }

                    // Se consulta si se tienen tipos almacenadas con el modelo y marca solicitados.
                    $types = CopsisService::checkTypes($request->model, $request->brand_id);

                    //Si no se encuentran marcas se responde avisa al usuario del error
                    if ($types->count() <= 0)
                        return response()->json([
                            'title' => 'Error en Copsis',
                            'message' => $response['errors'],
                            'code' => $this->prefixCode . 'X103'
                        ], 400);
                } else {

                    // Si la respuesta es correcta se asigna la respuesta de Copsis.
                    $types = $response['result'];

                    // Se evalua si las marcas ya existen en la base de datos actual
                    $old_types = Type::whereIn('description', collect($types)->pluck('tipo'))->where([
                        ['model', $request->model],
                        ['brand_id', $request->brand_id]
                    ])->get();

                    $new_types = collect($types)->pluck('tipo')->diff($old_types->pluck('description'));

                    $new_types = collect($types)->whereIn('tipo', $new_types);

                    // Se insertan las nuevas marcas consultadas
                    CopsisService::storeNewTypes($new_types, $request->model, $request->brand_id);
                }
            } catch (Exception $e) {

                ErrorsLog::create([
                    'description' => $e->getMessage() . '-L:' . $e->getLine(),
                    'http_code' => 500,
                    'module' => 'CopsisController',
                    'prefix_code' => $this->prefixCode . 'X104'
                ]);

                // Se consulta si se tienen tipos almacenadas con el modelo y marca solicitados.
                $types = CopsisService::checkTypes($request->model, $request->brand_id);

                //Si no se encuentran marcas se responde avisa al usuario del error
                if ($types->count() <= 0) {

                    ErrorsLog::create([
                        'description' => 'Error en Copsis. Los tipos no se pudieron consultar y no se tiene respaldo de este modelo.',
                        'http_code' => 404,
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X105'
                    ]);

                    return response()->json([
                        'title' => 'Ooops',
                        'message' => 'No se encontró información coincidente la marca de su vehículo.',
                        'code' => $this->prefixCode . 'X106'
                    ], 400);
                }
            }

            return response()->json([
                'title' => 'Proceso correcto',
                'message' => 'Tipos consultados correctamente',
                'types' => $types
            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X199'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X199'
            ], 500);
        }
    }

    public function consultVersion(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'model' => 'Required|Integer|NotIn:0|Digits:4',
                'brand_id' => 'Required|Integer|NotIn:0|MaxDigits:4',
                'type' => 'Required|String',
                'unit_type' => 'Required|String'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X201'
                ], 400);

            // Conexión con Copsis, el valor de aseguradora es fijo
            try {

                $version = Http::withHeaders([
                    'x-api-key' => env('api_key_uat')
                ])
                    ->timeout(5)
                    ->post('https://apiuat.quattrocrm.mx/autos/publics/busqueda/version', [
                        'aseguradora' => "CHUBB",
                        'modelo' => $request->model,
                        'marca' =>  $request->brand_id,
                        'tipo' =>  $request->type,
                        'grupoUnidades' => [$request->unit_type]
                    ]);

                // Se trata la respuesta para poder leerla como un objeto
                $response = json_decode($version, true);

                // Si la respuesta falla se inserta en un log los motivos de las fallas
                if ($version->failed()) {

                    foreach ($response['errors'] as $error) {

                        ErrorsLog::create([
                            'description' => $error,
                            'http_code' => $version->status(),
                            'module' => 'CopsisController',
                            'prefix_code' => $this->prefixCode . 'X202'
                        ]);
                    }

                    // Se consulta si se tienen tipos almacenadas con el modelo y marca solicitados.
                    $versions = CopsisService::checkVersions($request->model, $request->brand_id, $request->type);

                    //Si no se encuentran marcas se responde avisa al usuario del error
                    if ($versions->count() <= 0)
                        return response()->json([
                            'title' => 'Error en Copsis',
                            'message' => $response['errors'],
                            'code' => $this->prefixCode . 'X203'
                        ], 400);
                } else {

                    // Si la respuesta es correcta se asigna la respuesta de Copsis.
                    $versions = $response['result'];

                    if (count($versions) <= 0) {

                        return response()->json([
                            'title' => 'Error en Copsis',
                            'message' => 'Elige otra',
                            'code' => $this->prefixCode . 'X204'
                        ], 400);
                    }

                    // Se evalua si las marcas ya existen en la base de datos actual
                    $old_versions = Version::whereIn('amis', collect($versions)->pluck('amis'))->where([
                        ['model', $request->model],
                        ['brand_id', $request->brand_id],
                        ['type', $request->type]
                    ])->get();

                    $new_versions = collect($versions)->pluck('amis')->diff($old_versions->pluck('amis'));

                    $new_versions = collect($versions)->whereIn('amis', $new_versions);

                    // Se insertan las nuevas marcas consultadas
                    CopsisService::storeNewVersions($new_versions, $request->model, $request->brand_id, $request->type);
                }
            } catch (Exception $e) {

                ErrorsLog::create([
                    'description' => $e->getMessage() . '-L:' . $e->getLine(),
                    'http_code' => 500,
                    'module' => 'CopsisController',
                    'prefix_code' => $this->prefixCode . 'X205'
                ]);

                // Se consulta si se tienen tipos almacenadas con el modelo y marca solicitados.
                $versions = CopsisService::checkVersions($request->model, $request->brand_id, $request->type);

                //Si no se encuentran marcas se responde avisa al usuario del error
                if ($versions->count() <= 0) {

                    ErrorsLog::create([
                        'description' => 'Error en Copsis. Las versiones no se pudieron consultar y no se tiene respaldo de este modelo.',
                        'http_code' => 500,
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X206'
                    ]);

                    return response()->json([
                        'title' => 'Ooops',
                        'message' => 'No se encontró información coincidente el tipo de su vehículo.',
                        'code' => $this->prefixCode . 'X207'
                    ], 400);
                }
            }

            return response()->json([
                'title' => 'Proceso correcto',
                'message' => 'Versiones consultados correctamente',
                'versions' => $versions
            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X299'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X299'
            ], 500);
        }
    }

    public function homologation(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'complete_name' => 'Required|String',
                'email' => 'Required|Email',
                'cellphone' => 'Required|Numeric|MinDigits:10|MaxDigits:12',
                'cp' => 'Required|Integer|NotIn:0|Digits:5',
                'age' => 'Required|Integer|NotIn:0|Min:18|MaxDigits:3',
                'genre' => 'Required|String',
                'amis' => 'Required|String',
                'model' => 'Required|Integer|NotIn:0|Digits:4|Min:2003',
                'unit_type' => 'Required|String'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X301'
                ], 400);

            $client = Client::where([['email', $request->email]])->first();

            // Se valida que el cliente no exista en la base de datos con el mismo correo y teléfono.
            if (!$client)

                // Almacenamiento de la información de contacto del cliente
                Client::create([
                    'complete_name' => $request->complete_name,
                    'email' => $request->email,
                    'cellphone' => $request->cellphone,
                    'cp' => $request->cp,
                    'age' => $request->age,
                    'genre' => $request->genre,
                    'status_id' => 1
                ]);

            else {

                if (!$client->cellphone)
                    // Se actualiza la información del cliente
                    $client->update([
                        'cellphone' => $request->cellphone,
                        'cp' => $request->cp,
                        'age' => $request->age,
                        'genre' => $request->genre
                    ]);
            }

            // Conexión con Copsis, el valor grupoNegocioID y tipoHomologacion son fijos
            $homologation = Http::withHeaders([
                'x-api-key' => env('api_key_uat')
            ])
                ->post('https://apiuat.quattrocrm.mx/autos/publics/homologacion', [
                    'grupoNegocioID' => 195,
                    'aseguradora' => "CHUBB",
                    'clave' => $request->amis,
                    'modelo' => $request->model,
                    'tipoHomologacion' => "NOQUALITAS",
                    'tipoUnidad' => $request->unit_type,
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($homologation, true);

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($homologation->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $homologation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X002'
                    ]);
                }

                return response()->json([
                    'title' => 'Problema en la conexión',
                    'message' => 'No se pudo conectar con el proveedor, intente mas tarde.',
                    'code' => $this->prefixCode . 'X302'
                ], $homologation->status());
            }

            return response()->json([
                'title' => 'Proceso correcto',
                'message' => 'Homologación consultada.',
                'ana' => $response['result'][0],
                'chuub' => $response['result'][2],
                'primero' => $response['result'][1]

            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X399'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X399'
            ], 500);
        }
    }

    public function chuubQuotation(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'brand_id' => 'Required|Integer|NotIn:0',
                'pack' => 'Required|Integer|NotIn:0',
                'payment_frequency' => 'Required|String',
                'cp' => 'Required|Integer|NotIn:0|Digits:5',
                'age' => 'Required|Integer|NotIn:0|Min:18|MaxDigits:3',
                'genre' => 'Required|String',
                'vehicle' => 'Required|Array'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X301'
                ], 400);

            // Se validan los datos del vehículo
            $vehicle_validator = Validator::make($request->vehicle, [
                'descripcion' => 'Required|String',
                'ocupantes' => 'Required|Integer|NotIn:0|Min:1',
                'tipoUnidad' => 'Required|String',
                'tarifa' => 'Required|Numeric|Min:0',
                'clave' => 'Required|String',
                'modelo' => 'Required|Integer|NotIn:0|Digits:4|Min:2003'
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($vehicle_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $vehicle_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X402'
                ], 400);

            // Conexión con Copsis, el valor grupoNegocioID y tipoHomologacion son fijos
            $chuub_quotation = Http::withHeaders([
                'x-api-key' => env('api_key_uat')
            ])
                ->timeout(120)
                ->post('https://apiuat.quattrocrm.mx/autos/publics/cotizacion', [
                    'negocioID' => $request->brand_id,
                    'paquete' => $request->pack,
                    'vigenciaDe' => Carbon::now()->format('Y-m-d'),
                    'vigenciaA' => Carbon::now()->addYear()->format('Y-m-d'),
                    'frecuenciaPago' => $request->payment_frequency,
                    'tipoSuma' => "COMERCIAL",
                    'plataforma' =>   "CERTY",
                    'vehiculo' => $request->vehicle,
                    'asegurado' => [
                        'edad' => $request->age,
                        'sexo' => $request->genre,
                        'tipoPersona' => "FISICA",
                        'codigoPostal' => $request->cp
                    ]
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($chuub_quotation, true);

            if (!$response['ok']) {

                ErrorsLog::create([
                    'description' => $response['message'],
                    'http_code' => $chuub_quotation->status(),
                    'module' => 'CopsisController',
                    'prefix_code' => $this->prefixCode . 'X404'
                ]);

                return response()->json([
                    'title' => 'Error Copsis',
                    'message' => $response['message'],
                    'code' => $this->prefixCode . 'X405'
                ], 400);
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($chuub_quotation->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $chuub_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X403'
                    ]);
                }

                return response()->json([
                    'title' => 'Error Copsis',
                    'message' => $response['errors'],
                    'code' => $this->prefixCode . 'X403'
                ], 400);
            }

            return response()->json([
                'title' => 'Proceso completo',
                'message' => 'Cotización consultada correctamente',
                'chuub_quotation' => $response['result']
            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X499'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X499'
            ], 500);
        }
    }

    public function primeroQuotation(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'brand_id' => 'Required|Integer|NotIn:0',
                'pack' => 'Required|Integer|NotIn:0',
                'payment_frequency' => 'Required|String',
                'cp' => 'Required|Integer|NotIn:0|Digits:5',
                'age' => 'Required|Integer|NotIn:0|Min:18|MaxDigits:3',
                'genre' => 'Required|String',
                'vehicle' => 'Required|Array'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X501'
                ], 400);

            // Se validan los datos del vehículo
            $vehicle_validator = Validator::make($request->vehicle, [
                'descripcion' => 'Required|String',
                'tipoUnidad' => 'Required|String',
                'clave' => 'Required|String',
                'modelo' => 'Required|Integer|NotIn:0|Digits:4|Min:2003',
                'valorUnidad' => 'Required|Numeric|Min:0'
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($vehicle_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $vehicle_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X502'
                ], 400);

            // Conexión con Copsis, el valor grupoNegocioID y tipoHomologacion son fijos
            $primero_quotation = Http::withHeaders([
                'x-api-key' => env('api_key_uat')
            ])
                ->timeout(120)
                ->post('https://apiuat.quattrocrm.mx/autos/publics/cotizacion', [
                    'negocioID' => $request->brand_id,
                    'paquete' => $request->pack,
                    'vigenciaDe' => Carbon::now()->format('Y-m-d'),
                    'vigenciaA' => Carbon::now()->addYear()->format('Y-m-d'),
                    'frecuenciaPago' => $request->payment_frequency,
                    'tipoSuma' => "COMERCIAL",
                    'plataforma' =>   "CERTY",
                    'vehiculo' => $request->vehicle,
                    'asegurado' => [
                        'edad' => $request->age,
                        'sexo' => $request->genre,
                        'tipoPersona' => "FISICA",
                        'codigoPostal' => $request->cp
                    ]
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($primero_quotation, true);

            if (!$response['ok']) {

                ErrorsLog::create([
                    'description' => $response['message'],
                    'http_code' => $primero_quotation->status(),
                    'module' => 'CopsisController',
                    'prefix_code' => $this->prefixCode . 'X504'
                ]);

                return response()->json([
                    'title' => 'Error Copsis',
                    'message' => $response['message'],
                    'code' => $this->prefixCode . 'X506'
                ], 400);
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($primero_quotation->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $primero_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X503'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['errors'],
                        'code' => $this->prefixCode . 'X506'
                    ], 400);
                }
            }

            return response()->json([
                'title' => 'Proceso completo',
                'message' => 'Cotización consultada correctamente',
                'primero_quotation' => $response['result']

            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X599'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X599'
            ], 500);
        }
    }

    public function anaQuotation(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'brand_id' => 'Required|Integer|NotIn:0',
                'pack' => 'Required|Integer|NotIn:0',
                'payment_frequency' => 'Required|String',
                'cp' => 'Required|Integer|NotIn:0|Digits:5',
                'age' => 'Required|Integer|NotIn:0|Min:18|MaxDigits:3',
                'genre' => 'Required|String',
                'vehicle' => 'Required|Array'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X601'
                ], 400);

            // Se validan los datos del vehículo
            $vehicle_validator = Validator::make($request->vehicle, [
                'descripcion' => 'Required|String',
                'clave' => 'Required|String',
                'modelo' => 'Required|Integer|NotIn:0|Digits:4|Min:2003',
                'valorUnidad' => 'Required|Numeric|Min:0'
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($vehicle_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $vehicle_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X602'
                ], 400);

            // Conexión con Copsis, el valor grupoNegocioID y tipoHomologacion son fijos
            $ana_quotation = Http::withHeaders([
                'x-api-key' => env('api_key_uat')
            ])
                ->timeout(120)
                ->post('https://apiuat.quattrocrm.mx/autos/publics/cotizacion', [
                    'negocioID' => $request->brand_id,
                    'paquete' => $request->pack,
                    'vigenciaDe' => Carbon::now()->format('Y-m-d'),
                    'vigenciaA' => Carbon::now()->addYear()->format('Y-m-d'),
                    'frecuenciaPago' => $request->payment_frequency,
                    'tipoSuma' => "COMERCIAL",
                    'plataforma' => "CERTY",
                    'vehiculo' => $request->vehicle,
                    'asegurado' => [
                        'edad' => $request->age,
                        'sexo' => $request->genre,
                        'tipoPersona' => "FISICA",
                        'codigoPostal' => $request->cp
                    ]
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($ana_quotation, true);

            if (!$response['ok']) {

                ErrorsLog::create([
                    'description' => $response['message'],
                    'http_code' => $ana_quotation->status(),
                    'module' => 'CopsisController',
                    'prefix_code' => $this->prefixCode . 'X604'
                ]);

                return response()->json([
                    'title' => 'Error Copsis',
                    'message' => $response['message'],
                    'code' => $this->prefixCode . 'X605'
                ], 400);
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($ana_quotation->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $ana_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X603'
                    ]);
                }

                return response()->json([
                    'title' => 'Error Copsis',
                    'message' => $response['errors'],
                    'code' => $this->prefixCode . 'X605'
                ], 400);
            }

            return response()->json([
                'title' => 'Proceso Completado',
                'message' => 'Cotización consultada correctamente',
                'ana_quotation' => $response['result']

            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X699'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X499'
            ], 500);
        }
    }

    public function anaPayment(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'cotizacionID' => 'Required|String|NotIn:0',
                'contratante' => 'Required|Array',
                'vehiculo' => 'Required|Array',
                'quattroPoliza' => 'Required|Array'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X701'
                ], 400);

            // Se validan los datos del cliente
            $client_validator = Validator::make($request->contratante, [
                'nombre' => 'Required|String',
                'apellidoPaterno' => 'Required|String',
                'apellidoMaterno' => 'Required|String',
                'rfc' => 'Required|String',
                'estadoCivil' => 'Required|String',
                'sexo' => 'Required|String',
                'tipoPersona' => 'Required|String',
                'correo' => 'Required|String',
                'telefono' => 'Required|String',
                'direccion' => 'Required|Array',
                'direccion.calle' => 'Required|String',
                'direccion.pais' => 'Required|String',
                'direccion.codigoPostal' => 'Required|String',
                'direccion.colonia' => 'Required|String',
                'direccion.numeroExterior' => 'String|Nullable',
                'direccion.numeroInterior' => 'String|Nullable',
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($client_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $client_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X702'
                ], 400);

            // Se validan los datos del vehículo
            $vehicle_validator = Validator::make($request->vehiculo, [
                'serie' => 'Required|String',
                'placas' => 'Required|String',
                'motor' => 'Required|String'
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($vehicle_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $vehicle_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X702'
                ], 400);

            // Se validan los datos del quattroPoliza
            $quattro_validator = Validator::make($request->quattroPoliza, [
                'grupoEstructuraID' => 'Required|Integer',
                'vendedorID' => 'Required|String'
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($quattro_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $quattro_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X702'
                ], 400);

            // Conexión con Copsis para obtener el token de autenticación
            $token = Http::withHeaders([
                'Authorization' => "Basic Q0VSVFlfVFJJTklUQVM6UXpOU1ZGbGZNakF5TXpBeU1qUT0="
            ])
                ->timeout(120)
                ->post('https://apiuat.copsis.com/api/oauth/token', []);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($token, true);

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($token->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $token->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X703'
                    ]);
                }
            }

            if (!$response['ok']) {

                ErrorsLog::create([
                    'description' => $response['result']['error'],
                    'http_code' => $token->status(),
                    'module' => 'CopsisController',
                    'prefix_code' => $this->prefixCode . 'X704'
                ]);

                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $response['result']['error'],
                    'code' => $this->prefixCode . 'X706'
                ], 400);
            }

            $auth_token = $response['result']['token'];

            // Conexión con Copsis para obtener el token de autenticación
            $token = Http::withHeaders([
                'Authorization' => "Bearer " . $auth_token
            ])
                ->timeout(120)
                ->put('https://apiuat.quattrocrm.mx/autos/emision', [
                    "cotizacionID" => 0,
                    "contratante" => [
                        "nombre" => "Jose",
                        "apellidoPaterno" => "Martinez",
                        "apellidoMaterno" => "Garcia",
                        "rfc" => "XXXX010101000",
                        "estadoCivil" => "SOLTERO",
                        "sexo" => "MASCULINO",
                        "tipoPersona" => "FISICA",
                        "correo" => "test@copsis.com",
                        "telefono" => "8181818181",
                        "direccion" => [
                            "calle" => "Centro",
                            "pais" => "MEXICO",
                            "codigoPostal" => "64000",
                            "colonia" => "Monterrey Centro",
                            "numeroExterior" => "1000",
                            "numeroInterior" => ""
                        ]
                    ],
                    "vehiculo" => [
                        "serie" => "00000000000000001",
                        "placas" => "ABC123",
                        "motor" => "HECHO EN MEX"
                    ],
                    "quattroPoliza" => [
                        "grupoEstructuraID" => 2,
                        "vendedorID" => "l66xHtlkmi56Uqz3MEYuqw=="
                    ]
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($token, true);

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($token->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $token->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X703'
                    ]);
                }
            }

            if (!$response['ok']) {

                ErrorsLog::create([
                    'description' => $response['result']['error'],
                    'http_code' => $token->status(),
                    'module' => 'CopsisController',
                    'prefix_code' => $this->prefixCode . 'X704'
                ]);

                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $response['result']['error'],
                    'code' => $this->prefixCode . 'X706'
                ], 400);
            }

            return response()->json([
                'title' => 'Proceso Completado',
                'message' => 'Cotización consultada correctamente',
                'ana_quotation' => $response['result']

            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X799'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X799'
            ], 500);
        }
    }
}
