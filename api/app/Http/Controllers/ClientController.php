<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    private $prefixCode = 'Client';

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {

            $client = Client::select(
                'complete_name',
                'email',
                'email_verified_at',
                'cellphone',
                'cp',
                'age',
                'genre',
                'rfc',
                'suburb',
                'state',
                'township',
                'street',
                'street_number',
                'int_street_number',
                'password',
                'status_id',
                'created_at'
            )
                ->where('status_id', 1)
                ->find($id);

            if (!$client)
                return response()->json([
                    'title' => 'Usuario no encontrado',
                    'message' => 'Verifique el correo electrónico',
                    'code' => $this->prefixCode . 'X302'
                ], 402);

            return response()->json([
                'title' => 'Proceso concluido',
                'message' => 'Cliente consultado correctamente',
                'client' => $client
            ]);
        } catch (Exception $e) {

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X399'
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Se validan los datos de entrada
            $validator = Validator::make($request->all(), [
                'complete_name' => 'String|Nullable',
                'email' => 'Email|Nullable',
                'cellphone' => 'Integer|Digits:10|Nullable',
                'cp' => 'Integer|Digits:5|Nullable',
                'age' => 'Integer|Min:18|MaxDigits:3|Nullable',
                'genre' => 'String|Min:5|Nullable',
                'rfc' => 'String|Min:12|Max:13|Nullable',
                'suburb' => 'String|Min:5|Nullable',
                'state' => 'String|Min:5|Nullable',
                'township' => 'String|Min:5|Nullable',
                'street' => 'String|Min:5|Nullable',
                'street_number' => 'String|Nullable',
                'int_street_number' => 'String|Nullable',
            ]);


            // Respuesta en caso de que la validación falle
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X501'
                ], 400);

            if (Client::where([['email', $request->email], ['status_id', 1]])->first())
                return response()->json([
                    'title' => 'Fallo en la consulta',
                    'message' => 'Esté correo ya se encuentra en uso.',
                    'code' => $this->prefixCode . 'X502'
                ], 400);

            $client = Client::where([['status_id', 1]])->find($id);

            if (!$client)
                return response()->json([
                    'title' => 'Fallo en la consulta',
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X503'
                ], 400);

            DB::beginTransaction();

            $client->update([
                'complete_name' => $request->complete_name ? $request->complete_name : $client->complete_name,
                'email' => $request->email ? $request->email : $client->email,
                'cellphone' => $request->cellphone ? $request->cellphone : $client->cellphone,
                'cp' => $request->cp ? $request->cp : $client->cp,
                'age' => $request->age ? $request->age : $client->age,
                'genre' => $request->genre ? $request->genre : $client->genre,
                'rfc' => $request->rfc ? $request->rfc : $client->rfc,
                'suburb' => $request->suburb ? $request->suburb : $client->suburb,
                'state' => $request->state ? $request->state : $client->state,
                'township' => $request->township ? $request->township : $client->township,
                'street' => $request->street ? $request->street : $client->street,
                'street_number' => $request->street_number ? $request->street_number : $client->street_number,
                'int_street_number' => $request->int_street_number ? $request->int_street_number : $client->int_street_number
            ]);

            DB::commit();

            return response()->json([
                'title' => 'Proceso completo',
                'message' => 'Datos del usuario actualizados correctamente.'
            ]);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X599'
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
