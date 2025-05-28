<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CurrencyConversionService
{
    public function obtenerTipoCambio(string $moneda): float
    {
        if ($moneda === 'CLP') {
            return 1.0;
        }

        $response = Http::get('https://api.exchangerate.host/latest', [
            'base' => $moneda,
            'symbols' => 'CLP',
        ]);

        if ($response->ok()) {
            $data = $response->json();
            if (isset($data['rates']['CLP'])) {
                return $data['rates']['CLP'];
            }
        }

        throw new \Exception("No se pudo obtener el tipo de cambio para {$moneda}");
    }
}
