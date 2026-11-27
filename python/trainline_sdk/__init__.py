"""Unofficial Trainline API client for Python.

Train ticket fares and departure times between any two stations on Trainline (thetrainline.com).

Not affiliated with Trainline. Endpoint reference: https://maviapi.com/apis/thetrainline
"""

from __future__ import annotations

import json
import os
import urllib.error
import urllib.parse
import urllib.request
from typing import Any, Dict, Optional

__version__ = "0.1.0"
__all__ = ["TrainlineClient", "ApiError", "DEMO_KEY"]

DEMO_KEY = "mvk_test_playground_5f3c1a8d"


class ApiError(Exception):
    """An error status answered by the API."""

    def __init__(self, status: int, code: str, message: str) -> None:
        super().__init__(f"{status} {code}: {message}")
        self.status = status
        self.code = code
        self.message = message


class TrainlineClient:
    """Trainline API client.

    With no api_key it uses the MAVIAPI_API_KEY environment variable, then the
    shared demo key.
    """

    def __init__(
        self,
        api_key: Optional[str] = None,
        *,
        base_url: Optional[str] = None,
        timeout: float = 120.0,
    ) -> None:
        self.api_key = api_key or os.environ.get("MAVIAPI_API_KEY") or DEMO_KEY
        root = base_url or os.environ.get("MAVIAPI_BASE_URL") or "https://api.maviapi.com"
        self.base_url = root.rstrip("/") + "/v1/sites/thetrainline"
        self.timeout = timeout

    def fares(self, *, from_: Optional[str] = None, to: Optional[str] = None, date: Optional[str] = None, days: Optional[int] = None, currency: Optional[str] = None, limit: Optional[int] = None, offset: Optional[int] = None, undefined: Optional[str] = None, undefined: Optional[str] = None, undefined: Optional[str] = None) -> Dict[str, Any]:
        """
        The fare window between two places: origin, destination, the window's dates, its
        cheapest day and price, and `days`, the list of priced days in date order, each with
        its cheapest single (one-way) fare and the departure times of the cheapest trains.
        Days Trainline holds no fare data for may be missing or come back with date and
        cheapest_price null.

        Each successful request uses 5 credits.

        Parameters:
          from_ (string): origin, a place or station name (best match is used, as in
            /locations) or a Trainline urn code
          to (string): destination, a place or station name or a Trainline urn code
          date (string): first day of the window, YYYY-MM-DD (UTC); defaults to today
          days (integer): number of days to price starting at `date`, 1-14
          currency (string): currency of the fares (GBP, EUR, USD, CHF, DKK, SEK or NOK).
            One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
          limit (integer): Rows in one page, 1 to 200 (default 50)
          offset (integer): Rows to skip before the page starts (default 0)
          undefined (string)
          undefined (string)
          undefined (string)
        """
        return self._get("/fares", {"from": from_, "to": to, "date": date, "days": days, "currency": currency, "limit": limit, "offset": offset, undefined: undefined, undefined: undefined, undefined: undefined})

    def locations(self, *, q: Optional[str] = None, limit: Optional[int] = None, offset: Optional[int] = None, undefined: Optional[str] = None, undefined: Optional[str] = None, undefined: Optional[str] = None) -> Dict[str, Any]:
        """
        Trainline stations and places matching a name, best match first, with the urn codes
        that /fares accepts.

        Each successful request uses 5 credits.

        Parameters:
          q (string): search term, a station or place name (e.g. "london", "paris nord")
          limit (integer): Rows in one page, 1 to 200 (default 50)
          offset (integer): Rows to skip before the page starts (default 0)
          undefined (string)
          undefined (string)
          undefined (string)
        """
        return self._get("/locations", {"q": q, "limit": limit, "offset": offset, undefined: undefined, undefined: undefined, undefined: undefined})

    def _get(self, path: str, params: Dict[str, Any]) -> Dict[str, Any]:
        query = {}
        for name, value in params.items():
            if value is None:
                continue
            text = str(value).lower() if isinstance(value, bool) else str(value)
            token = "{" + name + "}"
            if token in path:
                path = path.replace(token, urllib.parse.quote(text, safe=""))
            else:
                query[name] = text
        url = self.base_url + path + ("?" + urllib.parse.urlencode(query) if query else "")
        request = urllib.request.Request(
            url,
            headers={
                "Authorization": "Bearer " + self.api_key,
                "Accept": "application/json",
                "User-Agent": "trainline-unofficial-sdk/0.1.0 (python)",
            },
        )
        try:
            with urllib.request.urlopen(request, timeout=self.timeout) as response:
                return json.loads(response.read())
        except urllib.error.HTTPError as error:
            body = error.read().decode("utf-8", "replace")
            try:
                detail = json.loads(body)
            except ValueError:
                detail = None
            if not isinstance(detail, dict):
                detail = {}
            raise ApiError(
                error.code,
                str(detail.get("error", "http_error")),
                str(detail.get("message", body[:200])),
            ) from None
