# frozen_string_literal: true

# Unofficial Trainline API client for Ruby.
# Not affiliated with Trainline. Endpoint reference: https://maviapi.com/apis/thetrainline

require "json"
require "net/http"
require "uri"

module TrainlineSdk
  VERSION = "0.1.0"
  DEMO_KEY = "mvk_test_playground_5f3c1a8d"

  # An error status answered by the API.
  class ApiError < StandardError
    attr_reader :status, :code

    def initialize(status, code, message)
      super("#{status} #{code}: #{message}")
      @status = status
      @code = code
    end
  end

  class Client
    # With no api_key it uses the MAVIAPI_API_KEY environment variable, then the
    # shared demo key.
    def initialize(api_key: nil, base_url: nil, timeout: 120)
      given = ->(*values) { values.find { |value| value && !value.empty? } }
      @api_key = given.call(api_key, ENV["MAVIAPI_API_KEY"], DEMO_KEY)
      root = given.call(base_url, ENV["MAVIAPI_BASE_URL"], "https://api.maviapi.com")
      @base_url = root.sub(%r{/+\z}, "") + "/v1/sites/thetrainline"
      @timeout = timeout
    end

    # Cheapest single fares and departure times between two stations, by day. Resolves
    # free-text place names to Trainline stations, then prices the route.
    #
    # Parameters:
    #   from (string, required): Origin: a place/station name (resolved to the best
    #     match) or a Trainline urn code
    #   to (string, required): Destination: a place/station name or a Trainline urn code
    #   date (string): First day of the window (YYYY-MM-DD, UTC). Defaults to today
    #   days (integer): Number of days from `date` to price (1-14)
    #   currency (string): Fare currency. One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
    #   limit (integer): Max days to return
    #   offset (integer): Days to skip (pagination)
    #   refresh (boolean): Bypass and repopulate the cache
    def fares(**params)
      get("/fares", params, %w[from to])
    end

    # Search Trainline stations/locations by name; returns their codes for use with
    # /fares.
    #
    # Parameters:
    #   q (string, required): Search term (station or place name)
    #   limit (integer): Max results to return
    #   offset (integer): Results to skip (pagination)
    #   refresh (boolean): Bypass and repopulate the cache
    def locations(**params)
      get("/locations", params, %w[q])
    end

    private

    def get(path, params, required)
      params = params.transform_keys(&:to_s)
      missing = required.find { |name| params[name].nil? }
      raise ArgumentError, "missing required parameter: #{missing}" if missing

      query = {}
      params.each do |name, value|
        next if value.nil?

        token = "{#{name}}"
        if path.include?(token)
          path = path.sub(token, URI.encode_www_form_component(value.to_s).gsub("+", "%20"))
        else
          query[name] = value.to_s
        end
      end
      uri = URI(@base_url + path)
      uri.query = URI.encode_www_form(query) unless query.empty?
      request = Net::HTTP::Get.new(uri)
      request["Authorization"] = "Bearer #{@api_key}"
      request["Accept"] = "application/json"
      request["User-Agent"] = "trainline-unofficial-sdk/0.1.0 (ruby)"
      response = Net::HTTP.start(uri.hostname, uri.port, use_ssl: uri.scheme == "https", read_timeout: @timeout) do |http|
        http.request(request)
      end
      data = begin
        JSON.parse(response.body.to_s)
      rescue JSON::ParserError
        nil
      end
      return data if response.is_a?(Net::HTTPSuccess)

      detail = data.is_a?(Hash) ? data : {}
      raise ApiError.new(response.code.to_i, detail["error"] || "http_error", detail["message"] || response.body.to_s[0, 200])
    end
  end
end
