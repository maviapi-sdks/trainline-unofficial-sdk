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
     * The fare window between two places: origin, destination, the window's dates, its
     * cheapest day and price, and `days`, the list of priced days in date order, each with
     * its cheapest single (one-way) fare and the departure times of the cheapest trains.
     * Days Trainline holds no fare data for may be missing or come back with date and
     * cheapest_price null.
     *
     * Each successful request uses 5 credits.
     *
     * Parameters:
     *   $from (string): origin, a place or station name (best match is used, as in
     *     /locations) or a Trainline urn code
     *   $to (string): destination, a place or station name or a Trainline urn code
     *   $date (string): first day of the window, YYYY-MM-DD (UTC); defaults to today
     *   $days (integer): number of days to price starting at `date`, 1-14
     *   $currency (string): currency of the fares (GBP, EUR, USD, CHF, DKK, SEK or NOK).
     *     One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
     *   $limit (integer): Rows in one page, 1 to 200 (default 50)
     *   $offset (integer): Rows to skip before the page starts (default 0)
     *   $undefined (string)
     *   $undefined (string)
     *   $undefined (string)
     *
     * @return array<string, mixed>
     */
    public function fares(?string $from = null, ?string $to = null, ?string $date = null, ?int $days = null, ?string $currency = null, ?int $limit = null, ?int $offset = null, ?string $undefined = null, ?string $undefined = null, ?string $undefined = null): array
    {
        return $this->get('/fares', ['from' => $from, 'to' => $to, 'date' => $date, 'days' => $days, 'currency' => $currency, 'limit' => $limit, 'offset' => $offset, undefined => $undefined, undefined => $undefined, undefined => $undefined]);
    }

    /**
     * Trainline stations and places matching a name, best match first, with the urn codes
     * that /fares accepts.
     *
     * Each successful request uses 5 credits.
     *
     * Parameters:
     *   $q (string): search term, a station or place name (e.g. "london", "paris nord")
     *   $limit (integer): Rows in one page, 1 to 200 (default 50)
     *   $offset (integer): Rows to skip before the page starts (default 0)
     *   $undefined (string)
     *   $undefined (string)
     *   $undefined (string)
     *
     * @return array<string, mixed>
     */
    public function locations(?string $q = null, ?int $limit = null, ?int $offset = null, ?string $undefined = null, ?string $undefined = null, ?string $undefined = null): array
    {
        return $this->get('/locations', ['q' => $q, 'limit' => $limit, 'offset' => $offset, undefined => $undefined, undefined => $undefined, undefined => $undefined]);
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
