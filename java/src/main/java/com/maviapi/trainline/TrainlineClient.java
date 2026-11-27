// Unofficial Trainline API client for Java 11+.
// Not affiliated with Trainline. Endpoint reference: https://maviapi.com/apis/thetrainline

package com.maviapi.trainline;

import java.io.IOException;
import java.io.UncheckedIOException;
import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.Map;

/** Trainline API client. Every method returns the response body: a JSON document. */
public class TrainlineClient {
    public static final String DEMO_KEY = "mvk_test_playground_5f3c1a8d";

    /** An error status answered by the API. Its body is a JSON object with "error" and "message". */
    public static class ApiError extends RuntimeException {
        private final int status;
        private final String body;

        public ApiError(int status, String body) {
            super(status + ": " + body);
            this.status = status;
            this.body = body;
        }

        public int getStatus() {
            return status;
        }

        public String getBody() {
            return body;
        }
    }

    private final String apiKey;
    private final String baseUrl;
    private final HttpClient http = HttpClient.newHttpClient();

    /** Uses the MAVIAPI_API_KEY environment variable, then the shared demo key. */
    public TrainlineClient() {
        this(null, null);
    }

    public TrainlineClient(String apiKey) {
        this(apiKey, null);
    }

    public TrainlineClient(String apiKey, String baseUrl) {
        this.apiKey = first(apiKey, System.getenv("MAVIAPI_API_KEY"), DEMO_KEY);
        String root = first(baseUrl, System.getenv("MAVIAPI_BASE_URL"), "https://api.maviapi.com");
        while (root.endsWith("/")) {
            root = root.substring(0, root.length() - 1);
        }
        this.baseUrl = root + "/v1/sites/thetrainline";
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
     *   from (string): origin, a place or station name (best match is used, as in
     *     /locations) or a Trainline urn code
     *   to (string): destination, a place or station name or a Trainline urn code
     *   date (string): first day of the window, YYYY-MM-DD (UTC); defaults to today
     *   days (integer): number of days to price starting at `date`, 1-14
     *   currency (string): currency of the fares (GBP, EUR, USD, CHF, DKK, SEK or NOK).
     *     One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
     *   limit (integer): Rows in one page, 1 to 200 (default 50)
     *   offset (integer): Rows to skip before the page starts (default 0)
     *   undefined (string)
     *   undefined (string)
     *   undefined (string)
     */
    public String fares(Map<String, Object> params) {
        return get("/fares", params);
    }

    /**
     * Trainline stations and places matching a name, best match first, with the urn codes
     * that /fares accepts.
     *
     * Each successful request uses 5 credits.
     *
     * Parameters:
     *   q (string): search term, a station or place name (e.g. "london", "paris nord")
     *   limit (integer): Rows in one page, 1 to 200 (default 50)
     *   offset (integer): Rows to skip before the page starts (default 0)
     *   undefined (string)
     *   undefined (string)
     *   undefined (string)
     */
    public String locations(Map<String, Object> params) {
        return get("/locations", params);
    }

    private static String first(String... values) {
        for (String value : values) {
            if (value != null && !value.isEmpty()) {
                return value;
            }
        }
        return "";
    }

    private String get(String path, Map<String, Object> params, String... required) {
        for (String name : required) {
            if (params.get(name) == null) {
                throw new IllegalArgumentException("Missing required parameter: " + name);
            }
        }
        StringBuilder query = new StringBuilder();
        for (Map.Entry<String, Object> entry : params.entrySet()) {
            if (entry.getValue() == null) {
                continue;
            }
            String token = "{" + entry.getKey() + "}";
            String text = URLEncoder.encode(String.valueOf(entry.getValue()), StandardCharsets.UTF_8);
            if (path.contains(token)) {
                path = path.replace(token, text.replace("+", "%20"));
            } else {
                query.append(query.length() == 0 ? "?" : "&")
                    .append(URLEncoder.encode(entry.getKey(), StandardCharsets.UTF_8))
                    .append("=")
                    .append(text);
            }
        }
        HttpRequest request = HttpRequest.newBuilder(URI.create(baseUrl + path + query))
            .timeout(Duration.ofSeconds(120))
            .header("Authorization", "Bearer " + apiKey)
            .header("Accept", "application/json")
            .header("User-Agent", "trainline-unofficial-sdk/0.1.0 (java)")
            .GET()
            .build();
        try {
            HttpResponse<String> response = http.send(request, HttpResponse.BodyHandlers.ofString());
            if (response.statusCode() >= 400) {
                throw new ApiError(response.statusCode(), response.body());
            }
            return response.body();
        } catch (IOException e) {
            throw new UncheckedIOException(e);
        } catch (InterruptedException e) {
            Thread.currentThread().interrupt();
            throw new IllegalStateException(e);
        }
    }
}
