<?php

// Unofficial Trainline API client for PHP.
// Not affiliated with Trainline. Endpoint reference: https://maviapi.com/apis/thetrainline

declare(strict_types=1);

namespace TrainlineSdk;

/** An error status answered by the API. */
class ApiError extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct("$status $errorCode: $message", $status);
    }
}

class TrainlineClient
{
    public const DEMO_KEY = 'mvk_test_playground_5f3c1a8d';

    private string $apiKey;
    private string $baseUrl;

    /**
     * With no $apiKey it uses the MAVIAPI_API_KEY environment variable, then the
     * shared demo key.
     */
    public function __construct(?string $apiKey = null, ?string $baseUrl = null, private int $timeout = 120)
    {
        $this->apiKey = $apiKey ?: (getenv('MAVIAPI_API_KEY') ?: self::DEMO_KEY);
        $root = $baseUrl ?: (getenv('MAVIAPI_BASE_URL') ?: 'https://api.maviapi.com');
        $this->baseUrl = rtrim($root, '/') . '/v1/sites/thetrainline';
    }

    /**
     * Cheapest single fares and departure times between two stations, by day. Resolves
     * free-text place names to Trainline stations, then prices the route.
     *
     * Parameters:
     *   $from (string, required): Origin: a place/station name (resolved to the best
     *     match) or a Trainline urn code
     *   $to (string, required): Destination: a place/station name or a Trainline urn
     *     code
     *   $date (string): First day of the window (YYYY-MM-DD, UTC). Defaults to today
     *   $days (integer): Number of days from `date` to price (1-14)
     *   $currency (string): Fare currency. One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
     *   $limit (integer): Max days to return
     *   $offset (integer): Days to skip (pagination)
     *   $refresh (boolean): Bypass and repopulate the cache
     *
     * @return array<string, mixed>
     */
    public function fares(string $from, string $to, ?string $date = null, ?int $days = null, ?string $currency = null, ?int $limit = null, ?int $offset = null, ?bool $refresh = null): array
    {
        return $this->get('/fares', ['from' => $from, 'to' => $to, 'date' => $date, 'days' => $days, 'currency' => $currency, 'limit' => $limit, 'offset' => $offset, 'refresh' => $refresh]);
    }

    /**
     * Search Trainline stations/locations by name; returns their codes for use with
     * /fares.
     *
     * Parameters:
     *   $q (string, required): Search term (station or place name)
     *   $limit (integer): Max results to return
     *   $offset (integer): Results to skip (pagination)
     *   $refresh (boolean): Bypass and repopulate the cache
     *
     * @return array<string, mixed>
     */
    public function locations(string $q, ?int $limit = null, ?int $offset = null, ?bool $refresh = null): array
    {
        return $this->get('/locations', ['q' => $q, 'limit' => $limit, 'offset' => $offset, 'refresh' => $refresh]);
    }

    /**
     * @param array<string, scalar|null> $params
     * @return array<string, mixed>
     */
    private function get(string $path, array $params): array
    {
        $query = [];
        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }
            $text = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            $token = '{' . $name . '}';
            if (str_contains($path, $token)) {
                $path = str_replace($token, rawurlencode($text), $path);
            } else {
                $query[$name] = $text;
            }
        }
        $curl = curl_init($this->baseUrl . $path . ($query ? '?' . http_build_query($query) : ''));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'trainline-unofficial-sdk/0.1.0 (php)',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey, 'Accept: application/json'],
        ]);
        $body = curl_exec($curl);
        if ($body === false) {
            throw new ApiError(0, 'network_error', curl_error($curl));
        }
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $data = json_decode($body, true);
        if ($status >= 400) {
            $detail = is_array($data) ? $data : [];
            throw new ApiError(
                $status,
                (string) ($detail['error'] ?? 'http_error'),
                (string) ($detail['message'] ?? substr($body, 0, 200)),
            );
        }
        return is_array($data) ? $data : [];
    }
}
