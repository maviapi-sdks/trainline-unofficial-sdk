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
| [`GET /fares`](#get-fares) | Cheapest single fares and departure times between two stations, by day. | 1 |
| [`GET /locations`](#get-locations) | Search Trainline stations/locations by name; returns their codes for use with /fares. | 1 |

Try each one in the browser on the [Trainline API page](https://maviapi.com/apis/thetrainline?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline).

## Authentication

A client takes the key you give it. With none, it reads the `MAVIAPI_API_KEY` environment variable, and then uses the shared demo key (30 requests a minute and 300 a day per network). To get your own, [sign up](https://maviapi.com/sign-up?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline) and create a key on the [keys page](https://maviapi.com/dashboard/keys). A successful request uses the credits its endpoint lists; a failed request is free. Plans are on the [pricing page](https://maviapi.com/pricing?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline).

## Errors

A request the API answers with an error status raises an `ApiError` (in Go, returns an `*Error`) with the HTTP status, a machine-readable code and a message. In Java it carries the status and the response body.

| Status | Code | Meaning |
|---|---|---|
| 400 | `bad_request` | A parameter is missing or cannot be used. |
| 401 | `missing_api_key` | No API key was sent, or the key is invalid or revoked (`missing_api_key`, `invalid_api_key`). |
| 402 | `credit_limit_reached` | The plan's credits for this period are used up. |
| 403 | `account_banned` | The account that owns the key is suspended. |
| 429 | `rate_limited` | Too many requests for this key. Wait the number of seconds in `Retry-After`. |
| 502 | `scrape_failed` | The source site could not be read this time. Retrying later usually works. |

## Endpoint reference

Base URL: `https://api.maviapi.com/v1/sites/thetrainline`. API version 2026-11-14. The same contract is in [`openapi.json`](openapi.json).

### `GET /fares`

Cheapest single fares and departure times between two stations, by day. Resolves free-text place names to Trainline stations, then prices the route.

Methods: `fares` (Python, JavaScript/TypeScript, PHP, Ruby, Java) · `Fares` (Go) · `FaresAsync` (C#)

Parameters:

- `from` (string, required): Origin: a place/station name (resolved to the best match) or a Trainline urn code. Example: `london`.
- `to` (string, required): Destination: a place/station name or a Trainline urn code. Example: `manchester`.
- `date` (string, optional): First day of the window (YYYY-MM-DD, UTC). Defaults to today. Example: `2026-12-25`.
- `days` (integer, optional): Number of days from `date` to price (1-14). Example: `3`.
- `currency` (string, optional): Fare currency. One of: `GBP`, `EUR`, `USD`, `CHF`, `DKK`, `SEK`, `NOK`. Example: `GBP`.
- `limit` (integer, optional): Max days to return. Example: `50`.
- `offset` (integer, optional): Days to skip (pagination). Example: `0`.
- `refresh` (boolean, optional): Bypass and repopulate the cache. Example: `false`.

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
    "date_from": "2026-08-11",
    "date_to": "2026-08-13",
    "cheapest_price": 14,
    "cheapest_date": "2026-08-11",
    "days": [
      {
        "date": "2026-08-11",
        "cheapest_price": 14,
        "currency": "GBP",
        "departures": [
          "2026-08-11T05:43:00.000Z"
        ]
      }
    ]
  },
  "cached": false,
  "source": "thetrainline:graphql",
  "pagination": {
    "total": 3,
    "limit": 50,
    "offset": 0,
    "count": 3,
    "has_more": false
  }
}
```

</details>

### `GET /locations`

Search Trainline stations/locations by name; returns their codes for use with /fares.

Methods: `locations` (Python, JavaScript/TypeScript, PHP, Ruby, Java) · `Locations` (Go) · `LocationsAsync` (C#)

Parameters:

- `q` (string, required): Search term (station or place name). Example: `london`.
- `limit` (integer, optional): Max results to return. Example: `10`.
- `offset` (integer, optional): Results to skip (pagination). Example: `0`.
- `refresh` (boolean, optional): Bypass and repopulate the cache. Example: `false`.

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
    ]
  },
  "cached": false,
  "source": "thetrainline:locations-search",
  "pagination": {
    "total": 10,
    "limit": 50,
    "offset": 0,
    "count": 10,
    "has_more": false
  }
}
```

</details>

## More

- [Trainline API page](https://maviapi.com/apis/thetrainline?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline): live examples and the changelog.
- [OpenAPI document](https://api.maviapi.com/v1/apis/thetrainline/openapi.json) and [Postman collection](https://api.maviapi.com/v1/apis/thetrainline/postman.json).
- [maviapi MCP server](https://maviapi.com/docs#mcp): the same API as tools for Claude, Cursor and other AI agents.
- [All maviapi APIs](https://maviapi.com/apis?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline). Need a website that is not there? [Ask for it](https://maviapi.com/?utm_source=github&utm_medium=readme&utm_campaign=sdk-thetrainline) and get an API for it.

Other unofficial SDKs: [Airbnb](https://github.com/maviapi-sdks/airbnb-unofficial-sdk) · [Booking.com](https://github.com/maviapi-sdks/booking-unofficial-sdk) · [GetYourGuide](https://github.com/maviapi-sdks/getyourguide-unofficial-sdk) · [Kiwi.com](https://github.com/maviapi-sdks/kiwi-unofficial-sdk) · [MarineTraffic](https://github.com/maviapi-sdks/marinetraffic-unofficial-sdk) · [Speedtest](https://github.com/maviapi-sdks/speedtest-unofficial-sdk) · [Trendyol](https://github.com/maviapi-sdks/trendyol-unofficial-sdk) · [Tripadvisor](https://github.com/maviapi-sdks/tripadvisor-unofficial-sdk) · [Yemeksepeti](https://github.com/maviapi-sdks/yemeksepeti-unofficial-sdk)

## License

MIT. See [LICENSE](LICENSE).
