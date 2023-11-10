<?php

namespace Composite\InvoiceWrapper\Factories;

use Composite\InvoiceWrapper\Services\Billingo\Billingo;
use Composite\InvoiceWrapper\Services\Szamlazzhu\Szamlazzhu;

class InvoiceGatewayFactory
{
    public static function create(array $config)
    {
        return match ($config['selected_provider']) {
            'billingo' => new Billingo($config['providers']['billingo']),
            'szamlazzhu' => new Szamlazzhu($config['providers']['szamlazzhu']),
            default => throw new \Exception('Invalid invoicing provider')
        };
    }
}
