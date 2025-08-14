<?php

namespace App\Http\Services;

class PrestashopService 
{
    public static $accesTokens = [
        '1HXXVWVELSV8WE4H712DMMXEWE87RAUV' => SmokehousePrestashop::class,
        // 'KSALVAQ3Y6W9YP4DAJPF26KNGR64LVBQ' => AstrogrowPrestashop::class,
        'UJWRIG3251NR2JNNH69589FABXGRPROD' => DLDSPrestashop::class,
        '2KUN9AE821WGLVL81YT9K1YWC613Y19P' => DLDSDevPrestashop::class,
        'YQUHB2KWMLCLSK8PWEUD6GU9ZC1MJKYE' => BONGLABPrestashop::class
    ];

    public static function getApiServiceBasedOnToken(string $token)
    {
        if(!array_key_exists($token, PrestashopService::$accesTokens)){
            return null;
        }

        return new PrestashopService::$accesTokens[$token]();
    }
}