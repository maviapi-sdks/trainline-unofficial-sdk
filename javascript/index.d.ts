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
   * origin, a place or station name (best match is used, as in /locations) or a
   * Trainline urn code
   */
  "from"?: string;
  /**
   * destination, a place or station name or a Trainline urn code
   */
  "to"?: string;
  /**
   * first day of the window, YYYY-MM-DD (UTC); defaults to today
   */
  "date"?: string;
  /**
   * number of days to price starting at `date`, 1-14
   */
  "days"?: number;
  /**
   * currency of the fares (GBP, EUR, USD, CHF, DKK, SEK or NOK). One of: GBP, EUR, USD,
   * CHF, DKK, SEK, NOK
   */
  "currency"?: string;
  /**
   * Rows in one page, 1 to 200 (default 50)
   */
  "limit"?: number;
  /**
   * Rows to skip before the page starts (default 0)
   */
  "offset"?: number;
  undefined?: string;
  undefined?: string;
  undefined?: string;
}

export interface LocationsParams {
  /**
   * search term, a station or place name (e.g. "london", "paris nord")
   */
  "q"?: string;
  /**
   * Rows in one page, 1 to 200 (default 50)
   */
  "limit"?: number;
  /**
   * Rows to skip before the page starts (default 0)
   */
  "offset"?: number;
  undefined?: string;
  undefined?: string;
  undefined?: string;
}

export declare class TrainlineClient {
  constructor(options?: ClientOptions);
  request(path: string, params?: Record<string, unknown>, required?: string[]): Promise<Record<string, any>>;
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
  fares(params?: FaresParams): Promise<Record<string, any>>;
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
  locations(params?: LocationsParams): Promise<Record<string, any>>;
}
