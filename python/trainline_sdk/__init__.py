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

    def fares(self, *, from_: str, to: str, date: Optional[str] = None, days: Optional[int] = None, currency: Optional[str] = None, limit: Optional[int] = None, offset: Optional[int] = None, refresh: Optional[bool] = None) -> Dict[str, Any]:
        """
        Cheapest single fares and departure times between two stations, by day. Resolves
        free-text place names to Trainline stations, then prices the route.

        Parameters:
          from_ (string, required): Origin: a place/station name (resolved to the best
            match) or a Trainline urn code
          to (string, required): Destination: a place/station name or a Trainline urn code
          date (string): First day of the window (YYYY-MM-DD, UTC). Defaults to today
          days (integer): Number of days from `date` to price (1-14)
          currency (string): Fare currency. One of: GBP, EUR, USD, CHF, DKK, SEK, NOK
          limit (integer): Max days to return
          offset (integer): Days to skip (pagination)
          refresh (boolean): Bypass and repopulate the cache
        """
        return self._get("/fares", {"from": from_, "to": to, "date": date, "days": days, "currency": currency, "limit": limit, "offset": offset, "refresh": refresh})

    def locations(self, *, q: str, limit: Optional[int] = None, offset: Optional[int] = None, refresh: Optional[bool] = None) -> Dict[str, Any]:
        """
        Search Trainline stations/locations by name; returns their codes for use with
        /fares.

        Parameters:
          q (string, required): Search term (station or place name)
          limit (integer): Max results to return
          offset (integer): Results to skip (pagination)
          refresh (boolean): Bypass and repopulate the cache
        """
        return self._get("/locations", {"q": q, "limit": limit, "offset": offset, "refresh": refresh})

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
