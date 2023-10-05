<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\Quotation;
use App\Services\UserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class QuotationController extends Controller
{

    private $prefixCode = 'Quotation';

    public function index()
    {
        try {

            $validator = Validator::make(request()->all(), [
                'client_id' => 'Required|Integer|NotIn:0|Min:0'
            ]);

            if ($validator->fails())
                return response()->json([
                    'message' => $validator->messages(),
                    'code' => $this->prefixCode . 'X301'
                ], 400);

            $user = UserService::checkUser(request('client_id'));

            if (!$user)
                return response()->json([
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X302'
                ], 400);

            DB::beginTransaction();

            //Se verifica la vigencia de las pólizas
            $expired_policies = Policy::where([
                ['status_id', 2],
                ['date_expire', '<=', Carbon::now()->format('Y-m-d')]
            ])
                ->get();

            //Se actualiza el status de las pólizas vencidas
            foreach ($expired_policies as $expired_policie)

                $expired_policie->update([
                    'status_id' => 3
                ]);

            DB::commit();

            // Se consultan las polizas del usuario
            $policies = Policy::select(
                'policies.id',
                'policies.client_id',
                'policies.model',
                'policies.brand_id',
                'policies.brand',
                'policies.unit_type',
                'policies.type',
                'policies.amis',
                'policies.vehicle_description',
                'policies.pack_id',
                'policies.pack_name',
                'policies.payment_frequency',
                'policies.quotation_code',
                'policies.brand_logo',
                'policies.vehicle_code',
                'policies.serial_no',
                'policies.plate_no',
                'policies.motor_no',
                'policies.insurer',
                'policies.insurer_logo',
                'policies.paid_amount',
                'policies.total_amount',
                DB::raw("(policies.total_amount - policies.paid_amount) as pending_amount"),
                'policies.issuance_date',
                'policies.issuance_code',
                'policies.receipt_code',
                'policies.policy_code',
                'policies.policy_number',
                'policies.init_date',
                'policies.date_expire',
                'policies.status_id',
                'S.description as status'
            )
                ->where([
                    ['policies.client_id', request('client_id')],
                    ['policies.status_id', '!=', 4]
                ])
                ->when(request('filter') == 1, function ($when) {
                    return $when->whereIn('policies.status_id', [2, 3, 5]);
                })
                ->join('status as S', function ($join_status) {

                    return $join_status->on('S.status_id', 'policies.status_id')
                        ->where('table_name', 'policies');
                })
                ->orderBy('policies.status_id', 'desc')
                ->get();

            return response()->json([
                'title' => 'Proceso correcto',
                'message' => 'Cotizaciones consultadas correctamente',
                'policies' => $policies,
                'total_policies' => $policies->count(),
                'total_amount' => $policies->sum('total_amount'),
                'paid_amount' => $policies->sum('paid_amount'),
                'pending_amount' => $policies->where('status_id', 2)->sum('pending_amount'),
                'expired_amount' => $policies->where('status_id', 3)->sum('pending_amount')
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X099'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'client_id' => 'Required|Integer|NotIn:0|Min:0'
            ]);

            if ($validator->fails())
                return response()->json([
                    'message' => $validator->messages(),
                    'code' => $this->prefixCode . 'X301'
                ], 400);

            $user = UserService::checkUser($request->client_id);

            if (!$user)
                return response()->json([
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X302'
                ], 400);

            DB::beginTransaction();

            $quotation = Policy::create([
                'client_id' => $request->client_id,
                'model' => $request->model,
                'brand_id' => $request->brand_id,
                'brand' => $request->brand,
                'unit_type' => $request->unit_type,
                'type' => $request->type,
                'amis' => $request->amis,
                'vehicle_description' => $request->vehicle_description,
                'pack_id' => $request->pack_id,
                'pack_name' => $request->pack_name,
                'payment_frequency' => $request->payment_frequency,
                'quotation_code' => $request->quotation_code,
                'brand_logo' => $request->brand_logo,
                'vehicle_code' => $request->vehicle_code,
                'insurer' => $request->insurer,
                'insurer_logo' => $request->insurer_logo,
                'paid_amount' => 0,
                'total_amount' => $request->total_amount,
                'status_id' => 1,
                'lead_id' => $request->lead_id
            ]);

            DB::commit();

            return response()->json([
                'title' => 'Proceso Correcto',
                'message' => 'Datos de la cotización almacenados correctamente',
                'quotation' => $quotation
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X199'
            ], 500);
        }
    }

    public function show($id)
    {
        try {

            $validator = Validator::make(request()->all(), [
                'user_id' => 'Required|Integer|NotIn:0|Min:0'
            ]);

            if ($validator->fails())
                return response()->json([
                    'message' => $validator->messages(),
                    'code' => $this->prefixCode . 'X201'
                ], 400);

            $user = UserService::checkUser(request('user_id'));

            if (!$user)
                return response()->json([
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X202'
                ], 400);

            $quotation = Policy::where([['client_id', request('user_id')], ['status_id', 1]])->find($id);

            if (!$quotation)
                return response()->json([
                    'message' => 'Cotización no encontrada.',
                    'code' => $this->prefixCode . 'X203'
                ], 400);

            return response()->json([
                'title' => 'Proceso Correcto',
                'message' => 'Cotización consultada correctamente',
                'quotation' => $quotation
            ]);
        } catch (Exception $e) {

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X299'
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $validator = Validator::make(request()->all(), [
                'client_id' => 'Required|Integer|NotIn:0|Min:0'
            ]);

            if ($validator->fails())
                return response()->json([
                    'message' => $validator->messages(),
                    'code' => $this->prefixCode . 'X301'
                ], 400);

            $user = UserService::checkUser(request('client_id'));

            if (!$user)
                return response()->json([
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X302'
                ], 400);

            DB::beginTransaction();

            $quotation = Policy::where([['client_id', request('client_id')], ['status_id', 1]])->find($id);

            if (!$quotation)
                return response()->json([
                    'message' => 'Cotización no encontrada.',
                    'code' => $this->prefixCode . 'X303'
                ], 400);

            $quotation->update([
                'vehicle_description' => $request->vehicle_description,
                'pack_id' => $request->pack_id,
                'pack_name' => $request->pack_name,
                'payment_frequency' => $request->payment_frequency,
                'quotation_code' => $request->quotation_code,
                'vehicle_code' => $request->vehicle_code,
                'insurer' => $request->insurer,
                'insurer_logo' => $request->insurer_logo,
                'total_amount' => $request->total_amount
            ]);

            DB::commit();

            return response()->json([
                'title' => 'Proceso Correcto',
                'message' => 'Datos de la cotización actualizados correctamente',
                'quotation' => $quotation
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X399'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
        } catch (Exception $e) {

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X399'
            ]);
        }
    }

    public function lastUpdate(Request $request)
    {
        try {

            $validator = Validator::make(request()->all(), [
                'client_id' => 'Required|Integer|NotIn:0|Min:0'
            ]);

            if ($validator->fails())
                return response()->json([
                    'message' => $validator->messages(),
                    'code' => $this->prefixCode . 'X501'
                ], 400);

            $user = UserService::checkUser(request('client_id'));

            if (!$user)
                return response()->json([
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X502'
                ], 400);

            DB::beginTransaction();

            $quotation = Policy::where([['client_id', request('client_id')], ['status_id', 1]])->find($request->quotation_id);

            if (!$quotation)
                return response()->json([
                    'message' => 'Cotización no encontrada.',
                    'code' => $this->prefixCode . 'X503'
                ], 400);

            $quotation->update([
                'serial_no' => $request->serial_no,
                'motor_no' => $request->motor_no,
                'plate_no' => $request->plate_no
            ]);

            DB::commit();

            return response()->json([
                'title' => 'Proceso Correcto',
                'message' => 'Datos de la cotización actualizados correctamente',
                'quotation' => $quotation
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X599'
            ], 500);
        }
    }
}
