// Unofficial Trainline API client for .NET.
// Not affiliated with Trainline. Endpoint reference: https://maviapi.com/apis/thetrainline

using System.Globalization;
using System.Net.Http.Headers;
using System.Text.Json;

namespace TrainlineSdk;

/// <summary>An error status answered by the API.</summary>
public class ApiError : Exception
{
    public int Status { get; }
    public string Code { get; }

    public ApiError(int status, string code, string message) : base($"{status} {code}: {message}")
    {
        Status = status;
        Code = code;
    }
}

public class TrainlineClient
{
    public const string DemoKey = "mvk_test_playground_5f3c1a8d";

    private readonly string _apiKey;
    private readonly string _baseUrl;
    private readonly HttpClient _http;

    /// <summary>
    /// With no apiKey it uses the MAVIAPI_API_KEY environment variable, then the
    /// shared demo key.
    /// </summary>
    public TrainlineClient(string? apiKey = null, string? baseUrl = null, HttpClient? http = null)
    {
        _apiKey = First(apiKey, Environment.GetEnvironmentVariable("MAVIAPI_API_KEY"), DemoKey);
        var root = First(baseUrl, Environment.GetEnvironmentVariable("MAVIAPI_BASE_URL"), "https://api.maviapi.com");
        _baseUrl = root.TrimEnd('/') + "/v1/sites/thetrainline";
        _http = http ?? new HttpClient { Timeout = TimeSpan.FromSeconds(120) };
    }

    // Cheapest single fares and departure times between two stations, by day. Resolves
    // free-text place names to Trainline stations, then prices the route.
    //
    // Parameters:
    //   from (string, required): Origin: a place/station name (resolved to the best
    //     match) or a Trainline urn code
    //   to (string, required): Destination: a place/station name or a Trainline urn code
    //   date (string): First day of the window (YYYY-MM-DD, UTC). Defaults to today
    //   days (integer): Number of days from `date` to price (1-14)
    //   currency (string): Fare currency. One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
    //   limit (integer): Max days to return
    //   offset (integer): Days to skip (pagination)
    //   refresh (boolean): Bypass and repopulate the cache
    public Task<JsonElement> FaresAsync(IDictionary<string, object?> parameters, CancellationToken cancellationToken = default) =>
        GetAsync("/fares", parameters, cancellationToken, "from", "to");

    // Search Trainline stations/locations by name; returns their codes for use with
    // /fares.
    //
    // Parameters:
    //   q (string, required): Search term (station or place name)
    //   limit (integer): Max results to return
    //   offset (integer): Results to skip (pagination)
    //   refresh (boolean): Bypass and repopulate the cache
    public Task<JsonElement> LocationsAsync(IDictionary<string, object?> parameters, CancellationToken cancellationToken = default) =>
        GetAsync("/locations", parameters, cancellationToken, "q");

    private static string First(params string?[] values) => values.First(value => !string.IsNullOrEmpty(value))!;

    private async Task<JsonElement> GetAsync(string path, IDictionary<string, object?> parameters, CancellationToken cancellationToken, params string[] required)
    {
        foreach (var name in required)
        {
            if (!parameters.TryGetValue(name, out var given) || given is null)
            {
                throw new ArgumentException($"Missing required parameter: {name}");
            }
        }
        var query = new List<string>();
        foreach (var (name, value) in parameters)
        {
            if (value is null)
            {
                continue;
            }
            var text = value switch
            {
                bool flag => flag ? "true" : "false",
                IFormattable number => number.ToString(null, CultureInfo.InvariantCulture),
                _ => value.ToString() ?? "",
            };
            var token = "{" + name + "}";
            if (path.Contains(token))
            {
                path = path.Replace(token, Uri.EscapeDataString(text));
            }
            else
            {
                query.Add(Uri.EscapeDataString(name) + "=" + Uri.EscapeDataString(text));
            }
        }
        var url = _baseUrl + path + (query.Count > 0 ? "?" + string.Join("&", query) : "");
        using var request = new HttpRequestMessage(HttpMethod.Get, url);
        request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", _apiKey);
        request.Headers.Accept.ParseAdd("application/json");
        request.Headers.UserAgent.ParseAdd("trainline-unofficial-sdk/0.1.0 (csharp)");
        using var response = await _http.SendAsync(request, cancellationToken);
        var body = await response.Content.ReadAsStringAsync(cancellationToken);
        JsonElement? data = null;
        try
        {
            using var document = JsonDocument.Parse(body);
            data = document.RootElement.Clone();
        }
        catch (JsonException)
        {
        }
        if (response.IsSuccessStatusCode && data is JsonElement result)
        {
            return result;
        }
        string Field(string name, string fallback) =>
            data is { ValueKind: JsonValueKind.Object } detail && detail.TryGetProperty(name, out var field) && field.ValueKind == JsonValueKind.String
                ? field.GetString()!
                : fallback;
        throw new ApiError((int)response.StatusCode, Field("error", "http_error"), Field("message", body.Length > 200 ? body[..200] : body));
    }
}
