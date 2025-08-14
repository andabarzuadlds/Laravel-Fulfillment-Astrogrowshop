<?php

namespace App\Http\Contracts;

interface PrestashopApiInterface
{
    public function postPrestashopOrder(array $productsToFulfill, array $postPrestashopOrder);
}