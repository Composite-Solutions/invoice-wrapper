<?php

namespace Composite\InvoiceWrapper\Factories;

use Composite\InvoiceWrapper\Services\Billingo\Billingo;
use Composite\InvoiceWrapper\Services\Szamlazzhu\Szamlazzhu;

class InvoiceGatewayFactory
{
    public static function create(string $provider)
    {
        return match ($provider) {
            'billingo' => new Billingo(),
            'szamlazzhu' => new Szamlazzhu(),
            default => throw new \Exception('Invalid invoicing provider')
        };
    }
}
