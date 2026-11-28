# Trainline Unofficial SDK

Unofficial **Trainline API** client for Python, JavaScript/TypeScript, Go, PHP, Ruby, Java and C#. Train ticket fares and departure times between any two stations on Trainline (thetrainline.com).

- **2 endpoints**, each returning JSON, with one method per endpoint in every language.
- **Works with no setup.** With no key, a client uses a shared demo key (30 requests a minute and 300 a day per network).
- **Your own key lifts the limit.** [Create a free key](https://maviapi.com/sign-up?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline): a 7-day trial with 5,000 credits, no card.
- **Kept working.** The API is hosted by [maviapi](https://maviapi.com/apis/thetrainline?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline), which monitors it and repairs it when the website changes.

> This project is not affiliated with, endorsed by or sponsored by Trainline (thetrainline.com). All trademarks belong to their owners.

## Quick start

### Python

```bash
pip install "git+https://github.com/maviapi-sdks/trainline-unofficial-sdk#subdirectory=python"
```

```python
from trainline_sdk import TrainlineClient

client = TrainlineClient()  # or TrainlineClient("mvk_live_...")
result = client.fares(from_="london", to="manchester")
print(result)
```

### JavaScript / TypeScript

```bash
npm install github:maviapi-sdks/trainline-unofficial-sdk
```

```javascript
import { TrainlineClient } from "trainline-unofficial-sdk";

const client = new TrainlineClient(); // or new TrainlineClient({ apiKey: "mvk_live_..." })
const result = await client.fares({ from: "london", to: "manchester" });
console.log(result);
```

### Go

```bash
go get github.com/maviapi-sdks/trainline-unofficial-sdk
```

```go
package main

import (
	"context"
	"fmt"
	"log"

	trainline "github.com/maviapi-sdks/trainline-unofficial-sdk"
)

func main() {
	client := trainline.New("") // or trainline.New("mvk_live_...")
	result, err := client.Fares(context.Background(), trainline.Params{"from": "london", "to": "manchester"})
	if err != nil {
		log.Fatal(err)
	}
	fmt.Println(string(result))
}
```

### PHP

```bash
composer config repositories.trainline-unofficial-sdk vcs https://github.com/maviapi-sdks/trainline-unofficial-sdk
composer require maviapi-sdks/trainline-unofficial-sdk:dev-main
```

```php
<?php

require 'vendor/autoload.php';

use TrainlineSdk\TrainlineClient;

$client = new TrainlineClient(); // or new TrainlineClient('mvk_live_...')
$result = $client->fares(from: 'london', to: 'manchester');
print_r($result);
```

### Ruby

```ruby
# Gemfile
gem "trainline_sdk", git: "https://github.com/maviapi-sdks/trainline-unofficial-sdk", branch: "main"
```

```ruby
require "trainline_sdk"

client = TrainlineSdk::Client.new # or TrainlineSdk::Client.new(api_key: "mvk_live_...")
result = client.fares(from: "london", to: "manchester")
puts result
```

### Java

```xml
<!-- pom.xml: the JitPack repository, then the dependency -->
<repository>
  <id>jitpack.io</id>
  <url>https://jitpack.io</url>
</repository>

<dependency>
  <groupId>com.github.maviapi-sdks</groupId>
  <artifactId>trainline-unofficial-sdk</artifactId>
  <version>main-SNAPSHOT</version>
</dependency>
```

The client is one file with no dependencies, so you can also copy [`TrainlineClient.java`](java/src/main/java/com/maviapi/trainline/TrainlineClient.java) into your project.

```java
import com.maviapi.trainline.TrainlineClient;
import java.util.Map;

public class Example {
    public static void main(String[] args) {
        TrainlineClient client = new TrainlineClient(); // or new TrainlineClient("mvk_live_...")
        String json = client.fares(Map.of("from", "london", "to", "manchester"));
        System.out.println(json);
    }
}
```

### C#

```bash
git clone https://github.com/maviapi-sdks/trainline-unofficial-sdk
dotnet add reference trainline-unofficial-sdk/csharp/TrainlineSdk.csproj
```

The client is one file with no dependencies, so you can also copy [`TrainlineClient.cs`](csharp/TrainlineClient.cs) into your project.

```csharp
using TrainlineSdk;

var client = new TrainlineClient(); // or new TrainlineClient("mvk_live_...")
var result = await client.FaresAsync(new Dictionary<string, object?> { ["from"] = "london", ["to"] = "manchester" });
Console.WriteLine(result);
```

## Endpoints

| Endpoint | What it returns | Credits per call |
|---|---|---|
| [`GET /fares`](#get-fares) | The fare window between two places: origin, destination, the window's dates, its cheapest day and price, and `days`, the list of priced days in date order, each with its cheapest single (one-way) fare and the departure times of the cheapest trains. | 5 |
| [`GET /locations`](#get-locations) | Trainline stations and places matching a name, best match first, with the urn codes that /fares accepts. | 5 |

Try each one in the browser on the [Trainline API page](https://maviapi.com/apis/thetrainline?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline).

## Authentication

A client takes the key you give it. With none, it reads the `MAVIAPI_API_KEY` environment variable, and then uses the shared demo key (30 requests a minute and 300 a day per network). To get your own, [sign up](https://maviapi.com/sign-up?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline) and create a key on the [keys page](https://maviapi.com/dashboard/keys). A successful request uses the credits its endpoint lists; a failed request is free. Plans are on the [pricing page](https://maviapi.com/pricing?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline).

## Errors

A request the API answers with an error status raises an `ApiError` (in Go, returns an `*Error`) with the HTTP status, a machine-readable code and a message. In Java it carries the status and the response body.

| Status | Code | Meaning |
|---|---|---|
| 400 | `bad_request` | A parameter is missing or cannot be used (`bad_request`), or, with the `x-maviapi-strict: 1` request header, a query param the operation does not take was sent (`unknown_param`, with the names in `params`). |
| 401 | `missing_api_key` | No API key was sent, or the key is invalid or revoked (`missing_api_key`, `invalid_api_key`). |
| 402 | `credit_limit_reached` | The plan's credits for this period are used up. |
| 403 | `account_banned` | The account that owns the key is suspended. |
| 410 | `cursor_expired` | The stored copy of the list this cursor walks is gone. Start the list again without cursor. |
| 429 | `rate_limited` | Too many requests for this key (`rate_limited`), or too many at once for its plan (`concurrency_limited`). Wait the number of seconds in `Retry-After`. |
| 502 | `scrape_failed` | The source site could not be read this time. Retrying later usually works. |

## Endpoint reference

Base URL: `https://api.maviapi.com/v1/sites/thetrainline`. API version 2026-11-27. The same contract is in [`openapi.json`](openapi.json).

### `GET /fares`

The fare window between two places: origin, destination, the window's dates, its cheapest day and price, and `days`, the list of priced days in date order, each with its cheapest single (one-way) fare and the departure times of the cheapest trains. Days Trainline holds no fare data for may be missing or come back with date and cheapest_price null.

Each successful request uses 5 credits.

Methods: `fares` (Python, JavaScript/TypeScript, PHP, Ruby, Java) · `Fares` (Go) · `FaresAsync` (C#)

Parameters:

- `from` (string, optional): origin, a place or station name (best match is used, as in /locations) or a Trainline urn code. Example: `london`.
- `to` (string, optional): destination, a place or station name or a Trainline urn code. Example: `manchester`.
- `date` (string, optional): first day of the window, YYYY-MM-DD (UTC); defaults to today.
- `days` (integer, optional): number of days to price starting at `date`, 1-14. Example: `3`.
- `currency` (string, optional): currency of the fares (GBP, EUR, USD, CHF, DKK, SEK or NOK). One of: `GBP`, `EUR`, `USD`, `CHF`, `DKK`, `SEK`, `NOK`. Example: `GBP`.
- `limit` (integer, optional): Rows in one page, 1 to 200 (default 50).
- `offset` (integer, optional): Rows to skip before the page starts (default 0).
- `undefined` (string, optional)
- `undefined` (string, optional)
- `undefined` (string, optional)

<details>
<summary>Example response</summary>

```json
{
  "data": {
    "origin": {
      "name": "London",
      "code": "urn:trainline:generic:loc:182gb"
    },
    "destination": {
      "name": "Manchester Piccadilly",
      "code": "urn:trainline:generic:loc:MAN2968gb"
    },
    "currency": "GBP",
    "journey_type": "single",
    "date_from": "2026-11-27",
    "date_to": "2026-11-29",
    "cheapest_price": 21.9,
    "cheapest_date": "2026-11-28",
    "days": [
      {
        "date": "2026-11-27",
        "cheapest_price": 36,
        "currency": "GBP",
        "departures": [
          "2026-11-27T19:46:00.000Z"
        ]
      }
    ],
    "pagination": {
      "count": 3,
      "has_more": false,
      "next_cursor": null
    },
    "fetched_at": "2026-11-27T10:16:44.709Z",
    "age": 0
  },
  "cached": null,
  "source": null,
  "pagination": {
    "total": null,
    "limit": null,
    "offset": null,
    "count": null,
    "has_more": null,
    "next_cursor": null
  },
  "fetched_at": null,
  "age": null
}
```

</details>

### `GET /locations`

Trainline stations and places matching a name, best match first, with the urn codes that /fares accepts.

Each successful request uses 5 credits.

Methods: `locations` (Python, JavaScript/TypeScript, PHP, Ruby, Java) · `Locations` (Go) · `LocationsAsync` (C#)

Parameters:

- `q` (string, optional): search term, a station or place name (e.g. "london", "paris nord"). Example: `london`.
- `limit` (integer, optional): Rows in one page, 1 to 200 (default 50).
- `offset` (integer, optional): Rows to skip before the page starts (default 0).
- `undefined` (string, optional)
- `undefined` (string, optional)
- `undefined` (string, optional)

<details>
<summary>Example response</summary>

```json
{
  "data": {
    "query": "london",
    "locations": [
      {
        "name": "London",
        "code": "urn:trainline:generic:loc:182gb",
        "type": "stationGroup",
        "country": "GB",
        "subtitle": "Any"
      }
    ],
    "pagination": {
      "count": 30,
      "has_more": false,
      "next_cursor": null
    },
    "fetched_at": "2026-11-27T10:15:54.995Z",
    "age": 0
  },
  "cached": null,
  "source": null,
  "pagination": {
    "total": null,
    "limit": null,
    "offset": null,
    "count": null,
    "has_more": null,
    "next_cursor": null
  },
  "fetched_at": null,
  "age": null
}
```

</details>

## More

- [Trainline API page](https://maviapi.com/apis/thetrainline?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline): live examples and the changelog.
- [OpenAPI document](https://api.maviapi.com/v1/apis/thetrainline/openapi.json) and [Postman collection](https://api.maviapi.com/v1/apis/thetrainline/postman.json).
- [maviapi MCP server](https://maviapi.com/docs#mcp): the same API as tools for Claude, Cursor and other AI agents.
- [All maviapi APIs](https://maviapi.com/apis?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline). Need a website that is not there? [Ask for it](https://maviapi.com/?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline) and get an API for it.

Other unofficial SDKs: [Airbnb](https://github.com/maviapi-sdks/airbnb-unofficial-sdk) · [Booking.com](https://github.com/maviapi-sdks/booking-unofficial-sdk) · [FlightList](https://github.com/maviapi-sdks/flightlist-unofficial-sdk) · [GetYourGuide](https://github.com/maviapi-sdks/getyourguide-unofficial-sdk) · [Google Search](https://github.com/maviapi-sdks/google-search-unofficial-sdk) · [Kiwi.com](https://github.com/maviapi-sdks/kiwi-unofficial-sdk) · [Madrid Cultural Agenda](https://github.com/maviapi-sdks/madrid-cultural-agenda-unofficial-sdk) · [MarineTraffic](https://github.com/maviapi-sdks/marinetraffic-unofficial-sdk) · [Speedtest](https://github.com/maviapi-sdks/speedtest-unofficial-sdk) · [Trendyol](https://github.com/maviapi-sdks/trendyol-unofficial-sdk) · [Tripadvisor](https://github.com/maviapi-sdks/tripadvisor-unofficial-sdk) · [Yemeksepeti](https://github.com/maviapi-sdks/yemeksepeti-unofficial-sdk)

## License

MIT. See [LICENSE](LICENSE).
