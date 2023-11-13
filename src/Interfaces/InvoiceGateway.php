<?php

namespace Composite\InvoiceWrapper\Interfaces;

use Exception;

interface InvoiceGateway
{
    public function issueInvoice(array $invoicePayload): array;
    public function getInvoice(string $invoiceId): array;
    public function downloadInvoice(string $invoiceId): void;
}
