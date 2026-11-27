// Unofficial Trainline API client for JavaScript and TypeScript.
// Not affiliated with Trainline. Endpoint reference: https://maviapi.com/apis/thetrainline

export const DEMO_KEY = "mvk_test_playground_5f3c1a8d";

/** An error status answered by the API. */
export class ApiError extends Error {
  constructor(status, code, message) {
    super(`${status} ${code}: ${message}`);
    this.name = "ApiError";
    this.status = status;
    this.code = code;
  }
}

export class TrainlineClient {
  /**
   * With no apiKey it uses the MAVIAPI_API_KEY environment variable, then the
   * shared demo key.
   */
  constructor({ apiKey, baseUrl, timeout = 120_000, fetch: fetcher } = {}) {
    const env = globalThis.process?.env ?? {};
    this.apiKey = apiKey || env.MAVIAPI_API_KEY || DEMO_KEY;
    let root = baseUrl || env.MAVIAPI_BASE_URL || "https://api.maviapi.com";
    while (root.endsWith("/")) root = root.slice(0, -1);
    this.baseUrl = root + "/v1/sites/thetrainline";
    this.timeout = timeout;
    this.fetch = fetcher ?? ((...args) => globalThis.fetch(...args));
  }

  /**
   * The fare window between two places: origin, destination, the window's dates, its
   * cheapest day and price, and `days`, the list of priced days in date order, each with
   * its cheapest single (one-way) fare and the departure times of the cheapest trains.
   * Days Trainline holds no fare data for may be missing or come back with date and
   * cheapest_price null.
   *
   * Each successful request uses 5 credits.
   */
  fares(params = {}) {
    return this.request("/fares", params, []);
  }

  /**
   * Trainline stations and places matching a name, best match first, with the urn codes
   * that /fares accepts.
   *
   * Each successful request uses 5 credits.
   */
  locations(params = {}) {
    return this.request("/locations", params, []);
  }

  /** GET one endpoint path. Parameters named in the path go there, the rest in the query. */
  async request(path, params = {}, required = []) {
    for (const name of required) {
      if (params[name] == null) throw new TypeError(`Missing required parameter: ${name}`);
    }
    const query = new URLSearchParams();
    for (const [name, value] of Object.entries(params)) {
      if (value == null) continue;
      const token = `{${name}}`;
      if (path.includes(token)) path = path.replace(token, encodeURIComponent(String(value)));
      else query.set(name, String(value));
    }
    const search = query.toString();
    const response = await this.fetch(this.baseUrl + path + (search ? `?${search}` : ""), {
      headers: {
        Authorization: `Bearer ${this.apiKey}`,
        Accept: "application/json",
        "User-Agent": "trainline-unofficial-sdk/0.1.0 (javascript)",
      },
      signal: AbortSignal.timeout(this.timeout),
    });
    const text = await response.text();
    let body = null;
    try {
      body = JSON.parse(text);
    } catch {}
    if (!response.ok) {
      throw new ApiError(response.status, body?.error ?? "http_error", body?.message ?? text.slice(0, 200));
    }
    return body;
  }
}
