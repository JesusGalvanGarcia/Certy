<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Type;
use App\Models\Version;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class CopsisService extends ServiceProvider
{
    static function actualizeToken()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic Q0VSVFlfVFJJTklUQVM6UXpOU1ZGbGZNakF5TXpBeU1qUT0='
        ])
            ->post('https://apiuat.copsis.com/api/oauth/token');

        $content = json_decode($response, true);

        return $content;
    }

    static function checkBrand($model)
    {

        return Brand::select(
            'brand_id as marca',
            'name as nombre',
        )->where('model', $model)->get();
    }

    static function storeNewBrands($new_brands, $model)
    {
        foreach ($new_brands as $new_brand) {
            Brand::create([
                'brand_id' => $new_brand['marca'],
                'name' => $new_brand['nombre'],
                'model' => $model,
            ]);
        }
    }

    static function checkTypes($model, $brand_id)
    {

        return Type::select(
            'description as tipo',
        )->where([
            ['model', $model],
            ['brand_id', $brand_id]
        ])->get();
    }

    static function storeNewTypes($new_types, $model, $brand_id)
    {
        foreach ($new_types as $new_type) {
            Type::create([
                'description' => $new_type['tipo'],
                'brand_id' => $brand_id,
                'model' => $model
            ]);
        }
    }

    static function checkVersions($model, $brand_id, $type)
    {

        return Version::select(
            'amis',
            'description as descripcion',
        )->where([
            ['model', $model],
            ['brand_id', $brand_id],
            ['type', $type]
        ])->get();
    }

    static function storeNewVersions($new_versions, $model, $brand_id, $type)
    {
        foreach ($new_versions as $new_version) {
            Version::create([
                'amis' => $new_version['amis'],
                'description' => $new_version['descripcion'],
                'model' => $model,
                'brand_id' => $brand_id,
                'type' => $type
            ]);
        }
    }
}
