<?php

namespace Composite\InvoiceWrapper\Services\Billingo\BillingoApiService;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;

class BillingoClient extends GuzzleClientBase
{
    public function __construct(array $config)
    {
        parent::__construct($config['api_key'], $config['base_url']);
    }

    /**
     * @param array $payload
     * @return mixed|StreamInterface
     * @throws GuzzleException
     */
    public function getDocuments(array $payload = []): mixed
    {
        return $this->get('documents', $payload);
    }

    /**
     * @param int $invoiceId
     * @return mixed
     * @throws GuzzleException
     */
    public function getDocument(int $invoiceId): mixed
    {
        return $this->get("documents/{$invoiceId}");
    }

    /**
     * @param int $invoiceId
     * @return mixed
     * @throws GuzzleException
     */
    public function downloadDocument(int $invoiceId): mixed
    {
        return $this->get("documents/{$invoiceId}/download", [], true);
    }

    /**
     * @param int $invoiceId
     * @return mixed
     * @throws GuzzleException
     */
    public function getPublicUrl(int $invoiceId): mixed
    {
        return $this->get("documents/{$invoiceId}/public-url");
    }

    /**
     * @param string $tax_number
     * @return mixed
     * @throws GuzzleException
     */
    public function checkTaxNumber(string $tax_number): mixed
    {
        return $this->get("utils/check-tax-number/{$tax_number}");
    }

    /**
     * @param int $partnerId
     * @param array $partner
     * @return mixed|StreamInterface
     * @throws GuzzleException
     */
    public function updateBillingoPartner(int $partnerId, array $partner): mixed
    {
        return $this->put('partners/'.$partnerId, $partner);
    }

    /**
     * @param array $partner
     * @return mixed
     * @throws GuzzleException
     */
    public function createBillingoPartner(array $partner): mixed
    {
        return $this->post('partners', $partner);
    }

    /**
     * @param array $invoice
     * @return mixed
     * @throws GuzzleException
     */
    public function createDocument(array $invoice): mixed
    {
        return $this->post('documents', $invoice);
    }

    /**
     * @param int $invoiceId
     * @return mixed
     * @throws GuzzleException
     */
    public function sendInvoice(int $invoiceId): mixed
    {
        return $this->post('documents/'.$invoiceId.'/send');
    }
}
