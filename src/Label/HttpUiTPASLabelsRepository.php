<?php

namespace CultuurNet\UDB3\UiTPAS\EventConsumer\Label;

use Guzzle\Http\Client;

class HttpUiTPASLabelsRepository implements UiTPASLabelsRepository
{
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @param Client $httpClient
     * @param string $endpoint
     *   Endpoint to query for UiTPAS labels.
     */
    public function __construct(
        Client $httpClient,
        $endpoint
    ) {
        $this->httpClient = $httpClient;
        $this->endpoint = (string) $endpoint;
    }

    public function loadAll()
    {
        $response = $this->httpClient->get($this->endpoint)->send();
        $content = $response->getBody();
        $data = json_decode($content, true);
        return array_values($data);
    }
}
