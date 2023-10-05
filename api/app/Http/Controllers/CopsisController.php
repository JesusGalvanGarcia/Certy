<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\EmittedPolicy;
use App\Models\ErrorsLog;
use App\Models\Policy;
use App\Models\Type;
use App\Models\Version;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use App\Services\CopsisService;
use App\Services\CRMService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CopsisController extends Controller
{

    private $prefixCode = 'Copsis';
    private $x_api_key = '4A6uqgBup6Fer2WZOcJJ2cvERi7Aomoox3JjAJ53WSf6tWxY8-CK-DhZnnnCsIfzyKwpd1LSboSLahXf6UOjQuexaSMcB1doo1TwdpnxbrU8cPFbxMiKw4bdDWnvB1Md';

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
                    'x-api-key' => $this->x_api_key,
                    'Accept' => 'application/json'
                ])
                    ->timeout(30)
                    ->post('https://api.quattrocrm.mx/autos/publics/busqueda/marca', [
                        'aseguradora' => "QUALITAS",
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
                'brand_id' => 'Required|String',
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
                    'x-api-key' => $this->x_api_key
                ])
                    ->timeout(30)
                    ->post('https://api.quattrocrm.mx/autos/publics/busqueda/tipo', [
                        'aseguradora' => "QUALITAS",
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
                'brand_id' => 'Required|String',
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
                    'x-api-key' => $this->x_api_key
                ])
                    ->timeout(30)
                    ->post('https://api.quattrocrm.mx/autos/publics/busqueda/version', [
                        'aseguradora' => "QUALITAS",
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
                            'message' => 'Elige otra opción',
                            'code' => $this->prefixCode . 'X204'
                        ], 400);
                    }

                    $copsis_versions = collect($versions);

                    $copsis_versions = $copsis_versions->filter(function ($value,  $key) {
                        return strlen($value['amis']) >= 12;
                    })->values();

                    // Se evalua si las marcas ya existen en la base de datos actual
                    $old_versions = Version::whereIn('amis', $copsis_versions->pluck('amis'))->where([
                        ['model', $request->model],
                        ['brand_id', $request->brand_id],
                        ['type', $request->type]
                    ])->get();

                    $new_versions = $copsis_versions->pluck('amis')->diff($old_versions->pluck('amis'));

                    $new_versions = $copsis_versions->whereIn('amis', $new_versions);

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

                if (!$client->cellphone || ($client->complete_name != $request->complete_name))
                    // Se actualiza la información del cliente
                    $client->update([
                        'complete_name' => $request->complete_name,
                        'cellphone' => $request->cellphone,
                        'cp' => $request->cp,
                        'age' => $request->age,
                        'genre' => $request->genre
                    ]);
            }

            // Conexión con Copsis, el valor grupoNegocioID y tipoHomologacion son fijos
            $homologation = Http::withHeaders([
                'x-api-key' => $this->x_api_key
            ])
                ->post('https://api.quattrocrm.mx/autos/publics/homologacion', [
                    'grupoNegocioID' => 298,
                    'aseguradora' => "QUALITAS",
                    'clave' => $request->amis,
                    'modelo' => $request->model,
                    'tipoHomologacion' => "QUALITAS",
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
                'chuub' => $response['result'][1],
                'primero' => $response['result'][0],
                'qualitas' => $response['result'][2]

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
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(120)
                ->post('https://api.quattrocrm.mx/autos/publics/cotizacion', [
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

                if (isset($response['result'])) {

                    ErrorsLog::create([
                        'description' => $response['result']['error'],
                        'http_code' => $chuub_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X404'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['result']['error'],
                        'code' => $this->prefixCode . 'X405'
                    ], 400);
                } else {
                    ErrorsLog::create([
                        'description' => $response['message'],
                        'http_code' => $chuub_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X406'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['message'],
                        'code' => $this->prefixCode . 'X407'
                    ], 400);
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($chuub_quotation->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $chuub_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X408'
                    ]);
                }

                return response()->json([
                    'title' => 'Error Copsis',
                    'message' => $response['errors'],
                    'code' => $this->prefixCode . 'X409'
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
                'brand_id' => 'Required|Integer',
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
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(120)
                ->post('https://api.quattrocrm.mx/autos/publics/cotizacion', [
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

                if (isset($response['result'])) {

                    ErrorsLog::create([
                        'description' => $response['result']['error'],
                        'http_code' => $primero_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X503'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['result']['error'],
                        'code' => $this->prefixCode . 'X504'
                    ], 400);
                } else {
                    ErrorsLog::create([
                        'description' => $response['message'],
                        'http_code' => $primero_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X505'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['message'],
                        'code' => $this->prefixCode . 'X506'
                    ], 400);
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($primero_quotation->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $primero_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X507'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['errors'],
                        'code' => $this->prefixCode . 'X508'
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

    // public function anaQuotation(Request $request)
    // {
    //     try {

    //         // Se validan los datos de entrada
    //         $validator = Validator::make($request->all(), [
    //             'brand_id' => 'Required|Integer|NotIn:0',
    //             'pack' => 'Required|Integer|NotIn:0',
    //             'payment_frequency' => 'Required|String',
    //             'cp' => 'Required|Integer|NotIn:0|Digits:5',
    //             'age' => 'Required|Integer|NotIn:0|Min:18|MaxDigits:3',
    //             'genre' => 'Required|String',
    //             'vehicle' => 'Required|Array'
    //         ]);

    //         // Respuesta en caso de que la validación falle
    //         if ($validator->fails())
    //             return response()->json([
    //                 'title' => 'Datos Faltantes',
    //                 'message' => $validator->messages()->first(),
    //                 'code' => $this->prefixCode . 'X601'
    //             ], 400);

    //         // Se validan los datos del vehículo
    //         $vehicle_validator = Validator::make($request->vehicle, [
    //             'descripcion' => 'Required|String',
    //             'clave' => 'Required|String',
    //             'modelo' => 'Required|Integer|NotIn:0|Digits:4|Min:2003',
    //             'valorUnidad' => 'Required|Numeric|Min:0'
    //         ]);

    //         // Respuesta en caso de que la validación del vehículo falle
    //         if ($vehicle_validator->fails())
    //             return response()->json([
    //                 'title' => 'Datos Faltantes',
    //                 'message' => $vehicle_validator->messages()->first(),
    //                 'code' => $this->prefixCode . 'X602'
    //             ], 400);

    //         // Conexión con Copsis, el valor grupoNegocioID y tipoHomologacion son fijos
    //         $ana_quotation = Http::withHeaders([
    //             'x-api-key' => $this->x_api_key
    //         ])
    //             ->timeout(120)
    //             ->post('https://api.quattrocrm.mx/autos/publics/cotizacion', [
    //                 'negocioID' => $request->brand_id,
    //                 'paquete' => $request->pack,
    //                 'vigenciaDe' => Carbon::now()->format('Y-m-d'),
    //                 'vigenciaA' => Carbon::now()->addYear()->format('Y-m-d'),
    //                 'frecuenciaPago' => $request->payment_frequency,
    //                 'tipoSuma' => "COMERCIAL",
    //                 'plataforma' => "CERTY",
    //                 'vehiculo' => $request->vehicle,
    //                 'asegurado' => [
    //                     'edad' => $request->age,
    //                     'sexo' => $request->genre,
    //                     'tipoPersona' => "FISICA",
    //                     'codigoPostal' => $request->cp
    //                 ]
    //             ]);

    //         // Se trata la respuesta para poder leerla como un objeto
    //         $response = json_decode($ana_quotation, true);

    //         if (!$response['ok']) {

    //             if (isset($response['result'])) {

    //                 ErrorsLog::create([
    //                     'description' => $response['result']['error'],
    //                     'http_code' => $ana_quotation->status(),
    //                     'module' => 'CopsisController',
    //                     'prefix_code' => $this->prefixCode . 'X603'
    //                 ]);

    //                 return response()->json([
    //                     'title' => 'Error Copsis',
    //                     'message' => $response['result']['error'],
    //                     'code' => $this->prefixCode . 'X604'
    //                 ], 400);
    //             } else {
    //                 ErrorsLog::create([
    //                     'description' => $response['message'],
    //                     'http_code' => $ana_quotation->status(),
    //                     'module' => 'CopsisController',
    //                     'prefix_code' => $this->prefixCode . 'X605'
    //                 ]);

    //                 return response()->json([
    //                     'title' => 'Error Copsis',
    //                     'message' => $response['message'],
    //                     'code' => $this->prefixCode . 'X606'
    //                 ], 400);
    //             }
    //         }

    //         // Si la respuesta falla se inserta en un log los motivos de las fallas
    //         if ($ana_quotation->failed()) {

    //             foreach ($response['errors'] as $error) {

    //                 ErrorsLog::create([
    //                     'description' => $error,
    //                     'http_code' => $ana_quotation->status(),
    //                     'module' => 'CopsisController',
    //                     'prefix_code' => $this->prefixCode . 'X607'
    //                 ]);
    //             }

    //             return response()->json([
    //                 'title' => 'Error Copsis',
    //                 'message' => $response['errors'],
    //                 'code' => $this->prefixCode . 'X608'
    //             ], 400);
    //         }

    //         return response()->json([
    //             'title' => 'Proceso Completado',
    //             'message' => 'Cotización consultada correctamente',
    //             'ana_quotation' => $response['result']

    //         ]);
    //     } catch (Exception $e) {

    //         ErrorsLog::create([
    //             'description' => $e->getMessage() . '-L:' . $e->getLine(),
    //             'http_code' => 500,
    //             'module' => 'CopsisController',
    //             'prefix_code' => $this->prefixCode . 'X699'
    //         ]);

    //         return response()->json([
    //             'title' => 'Error en el servidor',
    //             'message' => $e->getMessage() . '-L:' . $e->getLine(),
    //             'code' => $this->prefixCode . 'X499'
    //         ], 500);
    //     }
    // }

    // Emite póliza y pago en pasarela aseguradora
    public function chubbEmission(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'client_id' => 'Required|Integer|NotIn:0',
                'quotation_id' => 'Required|Integer|NotIn:0',
                'cotizacionID' => 'Required|Integer|NotIn:0',
                'contratante' => 'Required|Array',
                'vehiculo' => 'Required|Array'
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
                'direccion.codigoPostal' => 'Required|Numeric',
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
                'placas' => 'String',
                'motor' => 'String'
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($vehicle_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $vehicle_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X703'
                ], 400);

            $quotation = Policy::find($request->quotation_id);

            if (!$quotation || $quotation->status_id != 1)
                return response()->json([
                    'title' => 'Datos Incorrectos',
                    'message' => 'Esta cotización ya no es valida',
                    'code' => $this->prefixCode . 'X704'
                ], 400);

            // Conexión con Copsis para obtener el token de autenticación
            $token = Http::withHeaders([
                'Authorization' => "Basic Q0VSVFlfVFJJTklUQVM6UXpOU1ZGbGZNakF5TXpBeU1qUT0="
            ])
                ->timeout(120)
                ->post('https://api.copsis.com/api/oauth/token', []);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($token, true);

            // Se evalúa la respuesta
            if (!$response['ok']) {

                if (isset($response['result'])) {

                    ErrorsLog::create([
                        'description' => $response['result']['error'],
                        'http_code' => $token->status(),
                        'module' => 'CopsisChubbToken',
                        'prefix_code' => $this->prefixCode . 'X705'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['result']['error'],
                        'code' => $this->prefixCode . 'X705'
                    ], 400);
                } else {

                    if (isset($response['message'])) {
                        ErrorsLog::create([
                            'description' => $response['message'],
                            'http_code' => $token->status(),
                            'module' => 'CopsisChubbToken',
                            'prefix_code' => $this->prefixCode . 'X706'
                        ]);

                        return response()->json([
                            'title' => 'Error Copsis',
                            'message' => $response['message'],
                            'code' => $this->prefixCode . 'X706'
                        ], 400);
                    } else {

                        ErrorsLog::create([
                            'description' => $response,
                            'http_code' => $token->status(),
                            'module' => 'CopsisChubbToken',
                            'prefix_code' => $this->prefixCode . 'X707'
                        ]);

                        return response()->json([
                            'title' => 'Problema Conexión',
                            'message' => 'Estamos teniendo inconvenientes para conectarte con la aseguradora, inténtelo nuevamente.',
                            'code' => $this->prefixCode . 'X707'
                        ], 400);
                    }
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($token->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $token->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X709'
                    ]);
                }
            }

            // Se extrae el token de la respuesta.
            $auth_token = $response['result']['token'];

            // Conexión con Copsis emitir la póliza
            $chuub_emission = Http::withHeaders([
                'Authorization' => "Bearer " . $auth_token,
                'content_type' => 'application/json',
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(120)
                ->post('https://api.copsis.com/v1/polizas/auto/quattro', [
                    "cotizacionID" => $request->cotizacionID,
                    "urlRetorno" => "https://certytest.trinitas.mx/#/proceso/" . $quotation->id,
                    // "urlRetorno" => "http://localhost:4200/#/proceso/" . $quotation->id,
                    "contratante" => $request->contratante,
                    "vehiculo" => $request->vehiculo,
                    "quattroPoliza" => [
                        "grupoEstructuraID" => 2,
                        "vendedorID" => "l66xHtlkmi56Uqz3MEYuqw=="
                    ]
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $emission_response = json_decode($chuub_emission, true);

            if (!$emission_response['ok']) {

                if (isset($emission_response['result'])) {

                    ErrorsLog::create([
                        'description' => $emission_response['result']['error'],
                        'http_code' => $token->status(),
                        'module' => 'CopsisChubbToken',
                        'prefix_code' => $this->prefixCode . 'X710'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $emission_response['result']['error'],
                        'code' => $this->prefixCode . 'X711'
                    ], 400);
                } else {

                    if (isset($response['message'])) {
                        ErrorsLog::create([
                            'description' => $emission_response['message'],
                            'http_code' => $token->status(),
                            'module' => 'CopsisChubbToken',
                            'prefix_code' => $this->prefixCode . 'X712'
                        ]);

                        return response()->json([
                            'title' => 'Error Copsis',
                            'message' => $emission_response['message'],
                            'code' => $this->prefixCode . 'X712'
                        ], 400);
                    } else {

                        ErrorsLog::create([
                            'description' => $emission_response,
                            'http_code' => $token->status(),
                            'module' => 'CopsisChubbToken',
                            'prefix_code' => $this->prefixCode . 'X713'
                        ]);

                        return response()->json([
                            'title' => 'Problema Conexión',
                            'message' => 'Estamos teniendo inconvenientes para conectarte con la aseguradora, inténtelo nuevamente.',
                            'code' => $this->prefixCode . 'X713'
                        ], 400);
                    }
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($chuub_emission->failed()) {

                foreach ($emission_response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $chuub_emission->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X714'
                    ]);
                }
            }

            DB::beginTransaction();

            // Se guarda la emisión en el registro de emisiones realizadas.
            EmittedPolicy::create([
                'policy_id' => $emission_response['result']['polizaID'],
                'receipt_id' => $emission_response['result']['reciboID'],
                'policy_number' => $emission_response['result']['noPoliza'],
                'emission_id' => $emission_response['result']['emisionID'],
                'insurer' => $emission_response['result']['aseguradora'],
                'date_init' => $emission_response['result']['vigenciaDe'],
                'date_expires' => $emission_response['result']['vigenciaA'],
                'emission_date' => $emission_response['result']['fechaEmision'],
                'payment_frequency' => $emission_response['result']['frecuenciaPago'],
                'status_id' => 1
            ]);

            // Se evalúa si la cotización tenia una emisión anterior y se cancela
            if ($quotation->policy_code) {

                $emitted_policy = EmittedPolicy::where('policy_id', $quotation->policy_code)->first();

                if ($emitted_policy) {

                    $emitted_policy->update([
                        'status_id' => 2
                    ]);
                }
            }

            // Se actualiza/inserta la emisión en la cotización.
            $quotation->update([
                'issuance_date' => $emission_response['result']['fechaEmision'],
                'issuance_code' => $emission_response['result']['emisionID'],
                'receipt_code' => $emission_response['result']['reciboID'],
                'policy_code' => $emission_response['result']['polizaID'],
                'policy_number' => $emission_response['result']['noPoliza'],
                'init_date' => $emission_response['result']['vigenciaDe'],
                'date_expire' => $emission_response['result']['vigenciaA'],
                'status_id' => 5
            ]);

            DB::commit();

            return response()->json([
                'title' => 'Proceso Completado',
                'message' => 'Cotización consultada correctamente',
                'url' => $emission_response['result']['pasarela']

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

    // Emite póliza y pago en pasarela COPSIS
    public function primeroEmission(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'client_id' => 'Required|Integer|NotIn:0',
                'quotation_id' => 'Required|Integer|NotIn:0',
                'cotizacionID' => 'Required|Integer|NotIn:0',
                'contratante' => 'Required|Array',
                'vehiculo' => 'Required|Array'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X801'
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
                'direccion.codigoPostal' => 'Required|Numeric',
                'direccion.colonia' => 'Required|String',
                'direccion.numeroExterior' => 'String|Nullable',
                'direccion.numeroInterior' => 'String|Nullable',
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($client_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $client_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X802'
                ], 400);

            // Se validan los datos del vehículo
            $vehicle_validator = Validator::make($request->vehiculo, [
                'serie' => 'Required|String',
                'placas' => 'String',
                'motor' => 'String'
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($vehicle_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $vehicle_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X803'
                ], 400);

            // Consulta de la cotización para asignar la póliza
            $quotation = Policy::where('status_id', 1)->find($request->quotation_id);

            if (!$quotation)
                return response()->json([
                    'title' => 'Datos Incorrectos',
                    'message' => 'Esta cotización ya no es valida',
                    'code' => $this->prefixCode . 'X804'
                ], 400);

            // Conexión con Copsis para obtener el token de autenticación
            $token = Http::withHeaders([
                'Authorization' => "Basic Q0VSVFlfVFJJTklUQVM6UXpOU1ZGbGZNakF5TXpBeU1qUT0="
            ])
                ->timeout(120)
                ->post('https://api.copsis.com/api/oauth/token', []);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($token, true);

            if (!$response['ok']) {

                if (isset($response['result'])) {

                    ErrorsLog::create([
                        'description' => $response['result']['error'],
                        'http_code' => $token->status(),
                        'module' => 'CopsisPrimeroToken',
                        'prefix_code' => $this->prefixCode . 'X805'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['result']['error'],
                        'code' => $this->prefixCode . 'X806'
                    ], 400);
                } else {

                    if (isset($response['message'])) {
                        ErrorsLog::create([
                            'description' => $response['message'],
                            'http_code' => $token->status(),
                            'module' => 'CopsisPrimeroToken',
                            'prefix_code' => $this->prefixCode . 'X807'
                        ]);

                        return response()->json([
                            'title' => 'Error Copsis',
                            'message' => $response['message'],
                            'code' => $this->prefixCode . 'X807'
                        ], 400);
                    } else {

                        ErrorsLog::create([
                            'description' => $response,
                            'http_code' => $token->status(),
                            'module' => 'CopsisPrimeroToken',
                            'prefix_code' => $this->prefixCode . 'X808'
                        ]);

                        return response()->json([
                            'title' => 'Problema Conexión',
                            'message' => 'Estamos teniendo inconvenientes para conectarte con la aseguradora, inténtelo nuevamente.',
                            'code' => $this->prefixCode . 'X808'
                        ], 400);
                    }
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($token->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $token->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X809'
                    ]);
                }
            }

            $auth_token = $response['result']['token'];

            // Conexión con Copsis para emitir la póliza en Primero Seguros
            $primero_emission = Http::withHeaders([
                'Authorization' => "Bearer " . $auth_token,
                'content_type' => 'application/json',
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(500)
                ->post('https://api.copsis.com/v1/polizas/auto/quattro', [
                    "cotizacionID" => $request->cotizacionID,
                    "contratante" => $request->contratante,
                    "vehiculo" => $request->vehiculo,
                    "quattroPoliza" => [
                        "grupoEstructuraID" => 2,
                        "vendedorID" => "l66xHtlkmi56Uqz3MEYuqw=="
                    ]
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $emission_response = json_decode($primero_emission, true);

            if (!$emission_response['ok']) {

                if (isset($emission_response['result'])) {

                    ErrorsLog::create([
                        'description' => $emission_response['result']['error'],
                        'http_code' => $token->status(),
                        'module' => 'CopsisPrimeroToken',
                        'prefix_code' => $this->prefixCode . 'X810'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $emission_response['result']['error'],
                        'code' => $this->prefixCode . 'X811'
                    ], 400);
                } else {

                    if (isset($emission_response['message'])) {

                        ErrorsLog::create([
                            'description' => $emission_response['message'],
                            'http_code' => $token->status(),
                            'module' => 'CopsisPrimeroToken',
                            'prefix_code' => $this->prefixCode . 'X812'
                        ]);

                        return response()->json([
                            'title' => 'Error Copsis',
                            'message' => $emission_response['message'],
                            'code' => $this->prefixCode . 'X812'
                        ], 400);
                    } else {

                        ErrorsLog::create([
                            'description' => $emission_response,
                            'http_code' => $token->status(),
                            'module' => 'CopsisPrimeroToken',
                            'prefix_code' => $this->prefixCode . 'X813'
                        ]);

                        return response()->json([
                            'title' => 'Problema Conexión',
                            'message' => 'Estamos teniendo inconvenientes para conectarte con la aseguradora, inténtelo nuevamente.',
                            'code' => $this->prefixCode . 'X813'
                        ], 400);
                    }
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($primero_emission->failed()) {

                foreach ($emission_response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $primero_emission->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X814'
                    ]);
                }
            }

            DB::beginTransaction();

            // Se guarda la emisión en el registro de emisiones realizadas.
            EmittedPolicy::create([
                'policy_id' => $emission_response['result']['polizaID'],
                'receipt_id' => $emission_response['result']['reciboID'],
                'policy_number' => $emission_response['result']['noPoliza'],
                'emission_id' => $emission_response['result']['emisionID'],
                'insurer' => $emission_response['result']['aseguradora'],
                'date_init' => $emission_response['result']['vigenciaDe'],
                'date_expires' => $emission_response['result']['vigenciaA'],
                'emission_date' => $emission_response['result']['fechaEmision'],
                'payment_frequency' => $emission_response['result']['frecuenciaPago'],
                'status_id' => 1
            ]);

            // Se evalúa si la cotización tenia una emisión anterior y se cancela
            if ($quotation->policy_code) {

                $emitted_policy = EmittedPolicy::where('policy_id', $quotation->policy_code)->first();

                if ($emitted_policy) {

                    $emitted_policy->update([
                        'status_id' => 2
                    ]);
                }
            }

            // Se actualiza/inserta la emisión en la cotización.
            $quotation->update([
                'issuance_date' => $emission_response['result']['fechaEmision'],
                'issuance_code' => $emission_response['result']['emisionID'],
                'receipt_code' => $emission_response['result']['reciboID'],
                'policy_code' => $emission_response['result']['polizaID'],
                'policy_number' => $emission_response['result']['noPoliza'],
                'init_date' => $emission_response['result']['vigenciaDe'],
                'date_expire' => $emission_response['result']['vigenciaA'],
                'status_id' => 5
            ]);

            DB::commit();

            // Se solicita la URL de pago para el cliente.
            switch ($quotation->payment_frequency) {

                case 'CONTADO' || 'ANUAL':

                    $payment_frequency = 1;
                    break;

                case 'SEMESTRAL':
                    $payment_frequency = 2;
                    break;

                case 'TRIMESTRAL':
                    $payment_frequency = 3;
                    break;

                case 'MENSUAL':
                    $payment_frequency = 4;
                    break;
            }

            // Se solicita la URL de pago para el cliente.
            $payment_url = Http::withHeaders([
                'Authorization' => "Bearer " . $auth_token,
                'content_type' => 'application/json',
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(120)
                ->post('https://quattro-secure-d4f4hpx6ga-uc.a.run.app/secure/transaccion', [
                    "negocioID" => 3,
                    "cuentaID" => 20,
                    "descripcion" => "Pago Póliza",
                    "parametro" => "https://certytest.trinitas.mx/#/proceso/" . $quotation->id,
                    // "parametro" => "http://localhost:4200/#/proceso/" . $quotation->id,
                    "monto" => $emission_response['result']['recibos'][0]['primaTotal'],
                    "fp_transaccion" => $payment_frequency,
                    "reference" => $emission_response['result']['noPoliza'],
                    "entidad" => $emission_response['result']['emisionID'],
                    "tp_operation" => $emission_response['result']['recibos'][0]['referencia'],
                ]);

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($payment_url->failed()) {

                foreach ($payment_url['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => 'No pudimos proceder con el pago, un agente se pondrá en contacto para continuar.',
                        'http_code' => $payment_url->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X815'
                    ]);
                }
            }

            // Tratamiento de la respuesta para obtener la URL de pago
            // $error_first_slice = Str::after($payment_url, '<status>');
            // $error_second_slice = Str::before($error_first_slice, '</status>');

            // if ($error_second_slice != 'ok') {

            //     // if (isset($emission_response['result'])) {

            //     ErrorsLog::create([
            //         'description' => $payment_url,
            //         'http_code' => $payment_url->status(),
            //         'module' => 'CopsisChubbToken',
            //         'prefix_code' => $this->prefixCode . 'X710'
            //     ]);

            //     return response()->json([
            //         'title' => 'Error Copsis',
            //         'message' => 'No pudimos concluir el pago, un agente te contactara dentro de poco.',
            //         'code' => $this->prefixCode . 'X711'
            //     ], 400);
            //     // } else {

            //     //     ErrorsLog::create([
            //     //         'description' => $emission_response['message'],
            //     //         'http_code' => $token->status(),
            //     //         'module' => 'CopsisChubbToken',
            //     //         'prefix_code' => $this->prefixCode . 'X712'
            //     //     ]);

            //     //     return response()->json([
            //     //         'title' => 'Error Copsis',
            //     //         'message' => $emission_response['message'],
            //     //         'code' => $this->prefixCode . 'X713'
            //     //     ], 400);
            //     // }
            // }

            // Tratamiento de la respuesta para obtener la URL de pago
            $first_slice = Str::after($payment_url, '<url_wp>');
            $url = Str::before($first_slice, '</url_wp>');

            return response()->json([
                'title' => 'Proceso Completado',
                'message' => 'Emisión realizada correctamente',
                'url' => $url

            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X899'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X899'
            ], 500);
        }
    }

    // Emite y paga en pasarela COPSIS
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
                ->post('https://api.copsis.com/api/oauth/token', []);

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
                ->put('https://api.quattrocrm.mx/autos/emision', [
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

    // Confirmación de estado de la póliza
    public function confirmPayment(Request $request)
    {
        try {
            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'policy_id' => 'Required|Integer|NotIn:0',
                'status_id' => 'Required|Integer',
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X801'
                ], 400);

            DB::beginTransaction();

            // Consulta de póliza que esté en proceso
            $policy = Policy::where('status_id', 5)->find($request->policy_id);

            // Se valida si se encuentra la póliza
            if (!$policy)
                return response()->json([
                    'title' => 'Datos no encontrados',
                    'message' => 'Póliza no encontrada.',
                    'code' => $this->prefixCode . 'X802'
                ], 400);

            $process_description = '';

            if ($policy->insurer == 'PRIMERO' || $policy->insurer == 'ANA' || $policy->insurer == 'QUALITAS') {

                if ($request->status_id != 1) {
                    $policy_status = 5;
                    $copsis_confirm_payment = false;
                    $process_description = 'Emitido sin pago';
                } else {
                    $policy_status = 2;
                    $copsis_confirm_payment = true;
                    $process_description = 'Emitido con pago';
                }

                $policy->update([
                    'status_id' => $policy_status
                ]);
            } else if ($policy->insurer == 'CHUBB') {

                if ($request->status_id > 0 &&  $request->status_id) {
                    $policy_status = 2;
                    $copsis_confirm_payment = true;
                    $process_description = 'Emitido con pago';
                } else {
                    $policy_status = 5;
                    $copsis_confirm_payment = false;

                    $process_description = 'Emitido sin pago';
                }

                $policy->update([
                    'status_id' => $policy_status
                ]);
            }

            $client_info = Client::find($policy->client_id);

            // Se consulta el token para poder actualizar el Lead
            $token = CRMService::getToken();

            $lead_data = collect([
                "client_id" => $policy->client_id,
                "lead_id" => $policy->lead_id,
                "name" => $client_info->complete_name,
                "email" => $client_info->email,
                "phone" => $client_info->cellphone,
                "age" => $client_info->age,
                "genre" => $client_info->genre,
                "process_description" => $process_description,
                "model" => $policy->model,
                "brand" => $policy->brand,
                "vehicle_type" => $policy->type,
                "vehicle" => $policy->vehicle_description,
                "insurer" => $policy->insurer
            ]);

            $response =  CRMService::updateLead($token, $lead_data);

            DB::commit();

            // if ($copsis_confirm_payment) {

            //     // Conexión con Copsis para obtener el token de autenticación
            //     $token = Http::withHeaders([
            //         'Authorization' => "Basic Q0VSVFlfVFJJTklUQVM6UXpOU1ZGbGZNakF5TXpBeU1qUT0="
            //     ])
            //         ->timeout(120)
            //         ->post('https://api.copsis.com/api/oauth/token', []);

            //     // Se trata la respuesta para poder leerla como un objeto
            //     $response = json_decode($token, true);

            //     if (!$response['ok']) {

            //         if (isset($response['result'])) {

            //             ErrorsLog::create([
            //                 'description' => $response['result']['error'],
            //                 'http_code' => $token->status(),
            //                 'module' => 'CopsisChubbToken',
            //                 'prefix_code' => $this->prefixCode . 'X805'
            //             ]);

            //             return response()->json([
            //                 'title' => 'Error Copsis',
            //                 'message' => $response['result']['error'],
            //                 'code' => $this->prefixCode . 'X806'
            //             ], 400);
            //         } else {

            //             ErrorsLog::create([
            //                 'description' => $response['message'],
            //                 'http_code' => $token->status(),
            //                 'module' => 'CopsisChubbToken',
            //                 'prefix_code' => $this->prefixCode . 'X807'
            //             ]);

            //             return response()->json([
            //                 'title' => 'Error Copsis',
            //                 'message' => $response['message'],
            //                 'code' => $this->prefixCode . 'X808'
            //             ], 400);
            //         }
            //     }

            //     // Si la respuesta falla se inserta en un log los motivos de las fallas
            //     if ($token->failed()) {

            //         foreach ($response['errors'] as $error) {

            //             ErrorsLog::create([
            //                 'description' => $error,
            //                 'http_code' => $token->status(),
            //                 'module' => 'CopsisController',
            //                 'prefix_code' => $this->prefixCode . 'X809'
            //             ]);
            //         }
            //     }

            //     $auth_token = $response['result']['token'];

            //     // Se solicita la URL de pago para el cliente.
            //     $confirm_payment = Http::withHeaders([
            //         'Authorization' => "Bearer " . $auth_token,
            //         'content_type' => 'application/json',
            //         'x-api-key' => $this->x_api_key
            //     ])
            //         ->timeout(120)
            //         ->post('https://api.quattrocrm.mx/polizas/pagador/pago', [
            //             "fechaPago" => Carbon::now()->format('Y-m-d'),
            //             "comentario" => 'Pago de póliza' . $policy->policy_code . 'por Trintias.',
            //             "recibo" => [
            //                 "reciboID" => $policy->receipt_code
            //             ]
            //         ]);

            //     // Se trata la respuesta para poder leerla como un objeto
            //     $confirm_payment_response = json_decode($confirm_payment, true);

            //     if (!$confirm_payment_response['ok']) {

            //         if (isset($confirm_payment_response['result'])) {

            //             ErrorsLog::create([
            //                 'description' => $confirm_payment_response['result']['error'],
            //                 'http_code' => $confirm_payment->status(),
            //                 'module' => 'ConfirmPayment',
            //                 'prefix_code' => $this->prefixCode . 'X805'
            //             ]);

            //             return response()->json([
            //                 'title' => 'Error Copsis',
            //                 'message' => $confirm_payment_response['result']['error'],
            //                 'code' => $this->prefixCode . 'X806'
            //             ], 400);
            //         } else {

            //             ErrorsLog::create([
            //                 'description' => $confirm_payment_response['message'],
            //                 'http_code' => $confirm_payment->status(),
            //                 'module' => 'ConfirmPayment',
            //                 'prefix_code' => $this->prefixCode . 'X807'
            //             ]);

            //             return response()->json([
            //                 'title' => 'Error Copsis',
            //                 'message' => $confirm_payment_response['message'],
            //                 'code' => $this->prefixCode . 'X808'
            //             ], 400);
            //         }
            //     }

            //     // Si la respuesta falla se inserta en un log los motivos de las fallas
            //     if ($token->failed()) {

            //         foreach ($response['errors'] as $error) {

            //             ErrorsLog::create([
            //                 'description' => $error,
            //                 'http_code' => $token->status(),
            //                 'module' => 'ConfirmPayment',
            //                 'prefix_code' => $this->prefixCode . 'X809'
            //             ]);
            //         }
            //     }
            // }

            return response()->json([
                'title' => 'Proceso completo',
                'message' => 'Póliza emitida correctamente'
            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X899'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X899'
            ], 500);
        }
    }

    public function printPDF(Request $request)
    {
        try {
            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'client_id' => 'Required|Integer|NotIn:0',
                'policy_id' => 'Required|Integer|NotIn:0'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X901'
                ], 400);

            $user = UserService::checkUser(request('client_id'));

            if (!$user)
                return response()->json([
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X902'
                ], 400);

            $policy = Policy::whereIn('status_id', [5, 2])->find($request->policy_id);

            // Conexión con Copsis para obtener el token de autenticación
            $token = Http::withHeaders([
                'Authorization' => "Basic Q0VSVFlfVFJJTklUQVM6UXpOU1ZGbGZNakF5TXpBeU1qUT0="
            ])
                ->timeout(120)
                ->post('https://api.copsis.com/api/oauth/token', []);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($token, true);

            if (!$response['ok']) {

                if (isset($response['result'])) {

                    ErrorsLog::create([
                        'description' => $response['result']['error'],
                        'http_code' => $token->status(),
                        'module' => 'CopsisChubbToken',
                        'prefix_code' => $this->prefixCode . 'X903'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['result']['error'],
                        'code' => $this->prefixCode . 'X904'
                    ], 400);
                } else {

                    ErrorsLog::create([
                        'description' => $response['message'],
                        'http_code' => $token->status(),
                        'module' => 'CopsisChubbToken',
                        'prefix_code' => $this->prefixCode . 'X905'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['message'],
                        'code' => $this->prefixCode . 'X906'
                    ], 400);
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($token->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $token->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X907'
                    ]);
                }
            }

            $auth_token = $response['result']['token'];

            // Se solicita la URL de pago para el cliente.
            $print_pdf = Http::withHeaders([
                'Authorization' => "Bearer " . $auth_token,
                'content_type' => 'application/json',
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(120)
                ->get('https://api.copsis.com/v1/impresion/' . $policy->issuance_code . '/poliza/' . $policy->policy_code, [
                    "d" => "805/Y+YHzUW3cfBi53XTwtkzgOcbQ+VhFbMbjMdeYJaQNmvG5wra25L/4ml97TC6"
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $response_pdf = json_decode($print_pdf, true);

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($print_pdf->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $print_pdf->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X703'
                    ]);
                }
            }

            if (!$response['ok']) {

                ErrorsLog::create([
                    'description' => $response['result']['error'],
                    'http_code' => $print_pdf->status(),
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
                'pdfs' => $response_pdf['result']['pdfs']

            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X999'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X999'
            ], 500);
        }
    }

    public function qualitasQuotation(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'brand_id' => 'Required|Integer',
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
                    'code' => $this->prefixCode . 'X1201'
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
                    'code' => $this->prefixCode . 'X1202'
                ], 400);

            // Conexión con Copsis, el valor grupoNegocioID y tipoHomologacion son fijos
            $qualitas_quotation = Http::withHeaders([
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(120)
                ->post('https://api.quattrocrm.mx/autos/publics/cotizacion', [
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
            $response = json_decode($qualitas_quotation, true);

            if (!$response['ok']) {

                if (isset($response['result'])) {

                    ErrorsLog::create([
                        'description' => $response['result']['error'],
                        'http_code' => $qualitas_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X1204'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['result']['error'],
                        'code' => $this->prefixCode . 'X1205'
                    ], 400);
                } else {
                    ErrorsLog::create([
                        'description' => $response['message'],
                        'http_code' => $qualitas_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X1206'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['message'],
                        'code' => $this->prefixCode . 'X1207'
                    ], 400);
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($qualitas_quotation->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $qualitas_quotation->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X1208'
                    ]);
                }

                return response()->json([
                    'title' => 'Error Copsis',
                    'message' => $response['errors'],
                    'code' => $this->prefixCode . 'X1209'
                ], 400);
            }

            return response()->json([
                'title' => 'Proceso completo',
                'message' => 'Cotización consultada correctamente',
                'qualitas_quotation' => $response['result']
            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X1299'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X1299'
            ], 500);
        }
    }

    public function qualitasEmission(Request $request)
    {
        try {

            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'client_id' => 'Required|Integer|NotIn:0',
                'quotation_id' => 'Required|Integer|NotIn:0',
                'cotizacionID' => 'Required|Integer|NotIn:0',
                'contratante' => 'Required|Array',
                'vehiculo' => 'Required|Array'
            ]);

            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X1301'
                ], 400);

            // Se validan los datos del cliente
            $client_validator = Validator::make($request->contratante, [
                'nombre' => 'Required|String',
                'apellidoPaterno' => 'Required|String',
                'apellidoMaterno' => 'String|Nullable',
                'rfc' => 'Required|String',
                'estadoCivil' => 'Required|String',
                'sexo' => 'Required|String',
                'tipoPersona' => 'Required|String',
                'correo' => 'Required|String',
                'telefono' => 'Required|String',
                'direccion' => 'Required|Array',
                'direccion.calle' => 'Required|String',
                'direccion.pais' => 'Required|String',
                'direccion.codigoPostal' => 'Required|Numeric',
                'direccion.colonia' => 'Required|String',
                'direccion.numeroExterior' => 'String|Nullable',
                'direccion.numeroInterior' => 'String|Nullable',
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($client_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $client_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X1302'
                ], 400);

            // Se validan los datos del vehículo
            $vehicle_validator = Validator::make($request->vehiculo, [
                'serie' => 'Required|String',
                'placas' => 'String|Nullable',
                'motor' => 'String|Nullable'
            ]);

            // Respuesta en caso de que la validación del vehículo falle
            if ($vehicle_validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $vehicle_validator->messages()->first(),
                    'code' => $this->prefixCode . 'X1303'
                ], 400);

            $quotation = Policy::find($request->quotation_id);

            if (!$quotation || $quotation->status_id != 1)
                return response()->json([
                    'title' => 'Datos Incorrectos',
                    'message' => 'Esta cotización ya no es valida',
                    'code' => $this->prefixCode . 'X1304'
                ], 400);

            // Conexión con Copsis para obtener el token de autenticación
            $token = Http::withHeaders([
                'Authorization' => "Basic Q0VSVFlfVFJJTklUQVM6UXpOU1ZGbGZNakF5TXpBeU1qUT0="
            ])
                ->timeout(120)
                ->post('https://api.copsis.com/api/oauth/token', []);

            // Se trata la respuesta para poder leerla como un objeto
            $response = json_decode($token, true);

            if (!$response['ok']) {

                if (isset($response['result'])) {

                    ErrorsLog::create([
                        'description' => $response['result']['error'],
                        'http_code' => $token->status(),
                        'module' => 'CopsisChubbToken',
                        'prefix_code' => $this->prefixCode . 'X1305'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $response['result']['error'],
                        'code' => $this->prefixCode . 'X1306'
                    ], 400);
                } else {

                    if (isset($response['message'])) {
                        ErrorsLog::create([
                            'description' => $response['message'],
                            'http_code' => $token->status(),
                            'module' => 'CopsisChubbToken',
                            'prefix_code' => $this->prefixCode . 'X1307'
                        ]);

                        return response()->json([
                            'title' => 'Error Copsis',
                            'message' => $response['message'],
                            'code' => $this->prefixCode . 'X1307'
                        ], 400);
                    } else {

                        ErrorsLog::create([
                            'description' => $response,
                            'http_code' => $token->status(),
                            'module' => 'CopsisQualitasToken',
                            'prefix_code' => $this->prefixCode . 'X1308'
                        ]);

                        return response()->json([
                            'title' => 'Problema Conexión',
                            'message' => 'Estamos teniendo inconvenientes para conectarte con la aseguradora, inténtelo nuevamente.',
                            'code' => $this->prefixCode . 'X1308'
                        ], 400);
                    }
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($token->failed()) {

                foreach ($response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $token->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X1309'
                    ]);
                }
            }

            $auth_token = $response['result']['token'];

            // Conexión con Copsis emitir la póliza
            $qualitas_emission = Http::withHeaders([
                'Authorization' => "Bearer " . $auth_token,
                'content_type' => 'application/json',
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(500)
                ->post('https://api.copsis.com/v1/polizas/auto/quattro', [
                    "cotizacionID" => $request->cotizacionID,
                    "contratante" => $request->contratante,
                    "vehiculo" => $request->vehiculo,
                    "quattroPoliza" => [
                        // "grupoEstructuraID" => 10,
                        "grupoEstructuraID" => 2,
                        "vendedorID" => "l66xHtlkmi56Uqz3MEYuqw=="
                    ]
                ]);

            // Se trata la respuesta para poder leerla como un objeto
            $emission_response = json_decode($qualitas_emission, true);

            if (!$emission_response['ok']) {

                if (isset($emission_response['result'])) {

                    ErrorsLog::create([
                        'description' => $emission_response['result']['error'],
                        'http_code' => $token->status(),
                        'module' => 'CopsisChubbToken',
                        'prefix_code' => $this->prefixCode . 'X1310'
                    ]);

                    return response()->json([
                        'title' => 'Error Copsis',
                        'message' => $emission_response['result']['error'],
                        'code' => $this->prefixCode . 'X1311'
                    ], 400);
                } else {

                    if (isset($emission_response['message'])) {
                        ErrorsLog::create([
                            'description' => $emission_response['message'],
                            'http_code' => $token->status(),
                            'module' => 'CopsisChubbToken',
                            'prefix_code' => $this->prefixCode . 'X1312'
                        ]);

                        return response()->json([
                            'title' => 'Error Copsis',
                            'message' => $emission_response['message'],
                            'code' => $this->prefixCode . 'X1312'
                        ], 400);
                    } else {

                        ErrorsLog::create([
                            'description' => $emission_response,
                            'http_code' => $token->status(),
                            'module' => 'CopsisQualitasEmission',
                            'prefix_code' => $this->prefixCode . 'X1313'
                        ]);

                        return response()->json([
                            'title' => 'Problema Conexión',
                            'message' => 'Estamos teniendo inconvenientes para conectarte con la aseguradora, inténtelo nuevamente.',
                            'code' => $this->prefixCode . 'X1313'
                        ], 400);
                    }
                }
            }

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($qualitas_emission->failed()) {

                foreach ($emission_response['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => $error,
                        'http_code' => $qualitas_emission->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X1314'
                    ]);
                }
            }

            DB::beginTransaction();

            // Se guarda la emisión en el registro de emisiones realizadas.
            EmittedPolicy::create([
                'policy_id' => $emission_response['result']['polizaID'],
                'receipt_id' => $emission_response['result']['reciboID'],
                'policy_number' => $emission_response['result']['noPoliza'],
                'emission_id' => $emission_response['result']['emisionID'],
                'insurer' => $emission_response['result']['aseguradora'],
                'date_init' => $emission_response['result']['vigenciaDe'],
                'date_expires' => $emission_response['result']['vigenciaA'],
                'emission_date' => $emission_response['result']['fechaEmision'],
                'payment_frequency' => $emission_response['result']['frecuenciaPago'],
                'status_id' => 1
            ]);

            DB::commit();

            DB::beginTransaction();
            // Se evalúa si la cotización tenia una emisión anterior y se cancela
            if ($quotation->policy_code) {

                $emitted_policy = EmittedPolicy::where('policy_id', $quotation->policy_code)->first();

                if ($emitted_policy) {

                    $emitted_policy->update([
                        'status_id' => 2
                    ]);
                }
            }

            // Se actualiza/inserta la emisión en la cotización.
            $quotation->update([
                'issuance_date' => $emission_response['result']['fechaEmision'],
                'issuance_code' => $emission_response['result']['emisionID'],
                'receipt_code' => $emission_response['result']['reciboID'],
                'policy_code' => $emission_response['result']['polizaID'],
                'policy_number' => $emission_response['result']['noPoliza'],
                'init_date' => $emission_response['result']['vigenciaDe'],
                'date_expire' => $emission_response['result']['vigenciaA'],
                'status_id' => 5
            ]);

            DB::commit();

            // Se solicita la URL de pago para el cliente.
            switch ($quotation->payment_frequency) {

                case 'CONTADO' || 'ANUAL':

                    $payment_frequency = 1;
                    break;

                case 'SEMESTRAL':
                    $payment_frequency = 2;
                    break;

                case 'TRIMESTRAL':
                    $payment_frequency = 3;
                    break;

                case 'MENSUAL':
                    $payment_frequency = 4;
                    break;
            }

            // Se solicita la URL de pago para el cliente.
            $payment_url = Http::withHeaders([
                'Authorization' => "Bearer " . $auth_token,
                'content_type' => 'application/json',
                'x-api-key' => $this->x_api_key
            ])
                ->timeout(120)
                ->post('https://quattro-secure-d4f4hpx6ga-uc.a.run.app/secure/transaccion', [
                    "negocioID" => 3,
                    "cuentaID" => 52,
                    "descripcion" => "Pago Póliza",
                    "parametro" => "https://certytest.trinitas.mx/#/proceso/" . $quotation->id,
                    // "parametro" => "http://localhost:4200/#/proceso/" . $quotation->id,
                    "monto" => $emission_response['result']['recibos'][0]['primaTotal'],
                    "fp_transaccion" => $payment_frequency,
                    "reference" => $emission_response['result']['noPoliza'],
                    "entidad" => $emission_response['result']['emisionID'],
                    "tp_operation" => $emission_response['result']['recibos'][0]['referencia'],
                ]);

            // Si la respuesta falla se inserta en un log los motivos de las fallas
            if ($payment_url->failed()) {

                foreach ($payment_url['errors'] as $error) {

                    ErrorsLog::create([
                        'description' => 'No pudimos proceder con el pago, un agente se pondrá en contacto para continuar.',
                        'http_code' => $payment_url->status(),
                        'module' => 'CopsisController',
                        'prefix_code' => $this->prefixCode . 'X815'
                    ]);
                }
            }

            // Tratamiento de la respuesta para obtener la URL de pago
            // $error_first_slice = Str::after($payment_url, '<status>');
            // $error_second_slice = Str::before($error_first_slice, '</status>');

            // if ($error_second_slice != 'ok') {

            //     // if (isset($emission_response['result'])) {

            //     ErrorsLog::create([
            //         'description' => $payment_url,
            //         'http_code' => $payment_url->status(),
            //         'module' => 'CopsisChubbToken',
            //         'prefix_code' => $this->prefixCode . 'X710'
            //     ]);

            //     return response()->json([
            //         'title' => 'Error Copsis',
            //         'message' => 'No pudimos concluir el pago, un agente te contactara dentro de poco.',
            //         'code' => $this->prefixCode . 'X711'
            //     ], 400);
            //     // } else {

            //     //     ErrorsLog::create([
            //     //         'description' => $emission_response['message'],
            //     //         'http_code' => $token->status(),
            //     //         'module' => 'CopsisChubbToken',
            //     //         'prefix_code' => $this->prefixCode . 'X712'
            //     //     ]);

            //     //     return response()->json([
            //     //         'title' => 'Error Copsis',
            //     //         'message' => $emission_response['message'],
            //     //         'code' => $this->prefixCode . 'X713'
            //     //     ], 400);
            //     // }
            // }

            // Tratamiento de la respuesta para obtener la URL de pago
            $first_slice = Str::after($payment_url, '<url_wp>');
            $url = Str::before($first_slice, '</url_wp>');

            return response()->json([
                'title' => 'Proceso Completado',
                'message' => 'Emisión realizada correctamente',
                'url' => $url

            ]);
        } catch (Exception $e) {

            ErrorsLog::create([
                'description' => $e->getMessage() . '-L:' . $e->getLine(),
                'http_code' => 500,
                'module' => 'CopsisController',
                'prefix_code' => $this->prefixCode . 'X1399'
            ]);

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X1399'
            ], 500);
        }
    }
}
