<?php

namespace App\Services;

use Exception;
use SoapClient;
use SoapHeader;

class CurrencyConversionService
{
    private $client;
    private $user;
    private $password;

    public function __construct()
    {

        $wsdl = 'https://si3.bcentral.cl/estadisticas/Principal1/Web_Services/TipoCambio/TipoCambio.asmx?WSDL';

        $this->user = env('BDE_USER');
        $this->password = env('BDE_PASSWORD');

        $this->client = new SoapClient($wsdl, [
            'trace' => 1,
            'exceptions' => true,
        ]);

        $auth = [
            'UserName' => $this->user,
            'Password' => $this->password,
        ];
        $header = new SoapHeader('http://www.bcentral.cl/estadisticas/Principal1/Web_Services/TipoCambio', 'Autenticacion', $auth, false);
        $this->client->__setSoapHeaders([$header]);
    }

    /**
     * Convierte un monto desde una moneda origen a pesos chilenos usando tipo de cambio del día.
     *
     * @param float $amount Monto a convertir
     * @param string $currency Código moneda origen (ej: 'USD', 'EUR')
     * @return float Monto convertido a pesos chilenos
     * @throws Exception si no puede obtener el tipo de cambio
     */
    public function convertToCLP(float $amount, string $currency): float
    {
        if (strtoupper($currency) === 'CLP') {
            // Si ya es pesos chilenos, no hace falta conversión
            return $amount;
        }

        try {
            $today = date('Y-m-d');
            $params = [
                'tcInformacion' => [
                    'tcFechaInicio' => $today,
                    'tcFechaFinal' => $today,
                    'tcMoneda' => $currency,
                ]
            ];

            $response = $this->client->__soapCall('TipoCambioMonedaPeriodo', [$params]);

            $xmlString = $response->TipoCambioMonedaPeriodoResult->any;

            $xml = new \SimpleXMLElement($xmlString);

            if (isset($xml->TipoCambioPeriodo->TMpromedio)) {
                $tipoCambio = floatval((string) $xml->TipoCambioPeriodo->TMpromedio);
                return round($amount * $tipoCambio, 2);
            } else {
                throw new Exception('No se encontró tipo de cambio para la moneda ' . $currency);
            }

        } catch (Exception $e) {
            throw new Exception('Error al obtener tipo de cambio: ' . $e->getMessage());
        }
    }
}
