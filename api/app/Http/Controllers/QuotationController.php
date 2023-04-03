<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class QuotationController extends Controller
{

    private $code = 'Quotation';

    public function index()
    {
        try {

            $quotations = 1;

            return response()->json([
                'title' => 'Proceso correcto',
                'message' => 'Cotizaciones consultadas correctamente',
                'quotations' => $quotations
            ]);
        } catch (Exception $e) {

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->code . 'X099'
            ]);
        }
    }

    public function store(Request $request)
    {
        try {

            return 2;
        } catch (Exception $e) {

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->code . 'X199'
            ]);
        }
    }

    public function update()
    {
        try {
        } catch (Exception $e) {

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->code . 'X299'
            ]);
        }
    }

    public function delete()
    {
        try {
        } catch (Exception $e) {

            return response()->json([
                'title' => 'Error en el servidor',
                'message' => $e->getMessage() . '-L:' . $e->getLine(),
                'code' => $this->code . 'X399'
            ]);
        }
    }
}
