// Package trainline is an unofficial Trainline API client.
//
// Train ticket fares and departure times between any two stations on Trainline
// (thetrainline.com).
//
// Not affiliated with Trainline. Endpoint reference: https://maviapi.com/apis/thetrainline
package trainline

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"
	"strings"
	"time"
)

const (
	Version = "0.1.0"
	// DemoKey is the shared demo key a client uses when it is given no key.
	DemoKey = "mvk_test_playground_5f3c1a8d"
)

// Params are an endpoint's parameters by name. Those named in the endpoint's
// path go there, the rest in the query string.
type Params map[string]any

// Error is an error status answered by the API.
type Error struct {
	Status  int
	Code    string
	Message string
}

func (e *Error) Error() string { return fmt.Sprintf("%d %s: %s", e.Status, e.Code, e.Message) }

// Client calls the Trainline API. Its fields may be changed after New.
type Client struct {
	APIKey     string
	BaseURL    string
	HTTPClient *http.Client
}

// New returns a client. With an empty apiKey it uses the MAVIAPI_API_KEY
// environment variable, then the shared demo key.
func New(apiKey string) *Client {
	if apiKey == "" {
		apiKey = os.Getenv("MAVIAPI_API_KEY")
	}
	if apiKey == "" {
		apiKey = DemoKey
	}
	root := os.Getenv("MAVIAPI_BASE_URL")
	if root == "" {
		root = "https://api.maviapi.com"
	}
	return &Client{
		APIKey:     apiKey,
		BaseURL:    strings.TrimRight(root, "/") + "/v1/sites/thetrainline",
		HTTPClient: &http.Client{Timeout: 120 * time.Second},
	}
}

// Fares: Cheapest single fares and departure times between two stations, by day. Resolves
// free-text place names to Trainline stations, then prices the route.
//
// Parameters:
//
//   - from (string, required): Origin: a place/station name (resolved to the best
//     match) or a Trainline urn code
//   - to (string, required): Destination: a place/station name or a Trainline urn code
//   - date (string): First day of the window (YYYY-MM-DD, UTC). Defaults to today
//   - days (integer): Number of days from `date` to price (1-14)
//   - currency (string): Fare currency. One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
//   - limit (integer): Max days to return
//   - offset (integer): Days to skip (pagination)
//   - refresh (boolean): Bypass and repopulate the cache
func (c *Client) Fares(ctx context.Context, params Params) (json.RawMessage, error) {
	return c.get(ctx, "/fares", params, "from", "to")
}

// Locations: Search Trainline stations/locations by name; returns their codes for use with
// /fares.
//
// Parameters:
//
//   - q (string, required): Search term (station or place name)
//   - limit (integer): Max results to return
//   - offset (integer): Results to skip (pagination)
//   - refresh (boolean): Bypass and repopulate the cache
func (c *Client) Locations(ctx context.Context, params Params) (json.RawMessage, error) {
	return c.get(ctx, "/locations", params, "q")
}

func (c *Client) get(ctx context.Context, path string, params Params, required ...string) (json.RawMessage, error) {
	for _, name := range required {
		if params[name] == nil {
			return nil, fmt.Errorf("missing required parameter: %s", name)
		}
	}
	query := url.Values{}
	for name, value := range params {
		if value == nil {
			continue
		}
		text := fmt.Sprint(value)
		if token := "{" + name + "}"; strings.Contains(path, token) {
			path = strings.ReplaceAll(path, token, url.PathEscape(text))
		} else {
			query.Set(name, text)
		}
	}
	target := c.BaseURL + path
	if len(query) > 0 {
		target += "?" + query.Encode()
	}
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, target, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Authorization", "Bearer "+c.APIKey)
	req.Header.Set("Accept", "application/json")
	req.Header.Set("User-Agent", "trainline-unofficial-sdk/0.1.0 (go)")
	res, err := c.HTTPClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer res.Body.Close()
	body, err := io.ReadAll(res.Body)
	if err != nil {
		return nil, err
	}
	if res.StatusCode >= 400 {
		failure := &Error{Status: res.StatusCode, Code: "http_error", Message: string(body)}
		var detail struct{ Error, Message string }
		if json.Unmarshal(body, &detail) == nil && detail.Error != "" {
			failure.Code, failure.Message = detail.Error, detail.Message
		}
		return nil, failure
	}
	return body, nil
}
