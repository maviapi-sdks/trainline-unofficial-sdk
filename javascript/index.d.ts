export declare const DEMO_KEY: string;

export interface ClientOptions {
  /** Your maviapi API key. Default: the MAVIAPI_API_KEY environment variable, then the shared demo key. */
  apiKey?: string;
  baseUrl?: string;
  /** Milliseconds. Default 120000. */
  timeout?: number;
  fetch?: typeof fetch;
}

/** An error status answered by the API. */
export declare class ApiError extends Error {
  status: number;
  code: string;
  constructor(status: number, code: string, message: string);
}

export interface FaresParams {
  /**
   * Origin: a place/station name (resolved to the best match) or a Trainline urn code
   */
  "from": string;
  /**
   * Destination: a place/station name or a Trainline urn code
   */
  "to": string;
  /**
   * First day of the window (YYYY-MM-DD, UTC). Defaults to today
   */
  "date"?: string;
  /**
   * Number of days from `date` to price (1-14)
   */
  "days"?: number;
  /**
   * Fare currency. One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
   */
  "currency"?: string;
  /**
   * Max days to return
   */
  "limit"?: number;
  /**
   * Days to skip (pagination)
   */
  "offset"?: number;
  /**
   * Bypass and repopulate the cache
   */
  "refresh"?: boolean;
}

export interface LocationsParams {
  /**
   * Search term (station or place name)
   */
  "q": string;
  /**
   * Max results to return
   */
  "limit"?: number;
  /**
   * Results to skip (pagination)
   */
  "offset"?: number;
  /**
   * Bypass and repopulate the cache
   */
  "refresh"?: boolean;
}

export declare class TrainlineClient {
  constructor(options?: ClientOptions);
  request(path: string, params?: Record<string, unknown>, required?: string[]): Promise<Record<string, any>>;
  /**
   * Cheapest single fares and departure times between two stations, by day. Resolves
   * free-text place names to Trainline stations, then prices the route.
   *
   * Parameters:
   *   from (string, required): Origin: a place/station name (resolved to the best
   *     match) or a Trainline urn code
   *   to (string, required): Destination: a place/station name or a Trainline urn code
   *   date (string): First day of the window (YYYY-MM-DD, UTC). Defaults to today
   *   days (integer): Number of days from `date` to price (1-14)
   *   currency (string): Fare currency. One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
   *   limit (integer): Max days to return
   *   offset (integer): Days to skip (pagination)
   *   refresh (boolean): Bypass and repopulate the cache
   */
  fares(params: FaresParams): Promise<Record<string, any>>;
  /**
   * Search Trainline stations/locations by name; returns their codes for use with
   * /fares.
   *
   * Parameters:
   *   q (string, required): Search term (station or place name)
   *   limit (integer): Max results to return
   *   offset (integer): Results to skip (pagination)
   *   refresh (boolean): Bypass and repopulate the cache
   */
  locations(params: LocationsParams): Promise<Record<string, any>>;
}
