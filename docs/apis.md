# Completed API Curls

This file lists APIs currently implemented in the backend with curl examples, request bodies, and response examples.

Use these placeholders:

```text
{{BASE_URL}} = https://darkgreen-goat-738912.hostingersite.com
{{REQUEST_ID}} = client generated UUID
{{DISCOVERY_TOKEN}} = token returned by /api/auth/v1/accounts/discover
{{ACCOUNT_REF}} = selected account_ref returned by /api/auth/v1/accounts/discover
{{CHALLENGE_TOKEN}} = token returned when login requires 2FA
{{ACCESS_TOKEN}} = platform or tenant access token from unified login
{{PLATFORM_TOKEN}} = platform access token
{{TENANT_TOKEN}} = tenant/client access token
{{TENANT}} = tenant slug or tenant UUID
```

Common public headers:

```http
Accept: application/json
Content-Type: application/json
X-Request-Id: {{REQUEST_ID}}
X-Client-Version: web/1.0.0
X-Timezone: Asia/Kolkata
X-Locale: en
```

---

# 0. Common Master Data APIs

Base URL: `https://darkgreen-goat-738912.hostingersite.com`

## 0.1 Countries List

List active countries for any form that needs a country dropdown. Supports optional `search` by country name, ISO2, or ISO3.

```bash
curl -X GET "{{BASE_URL}}/api/common/v1/locations/countries?search=ind" \
  -H "Accept: application/json" \
  -H "X-Request-Id: {{REQUEST_ID}}"
```

Request body: none.

Response example:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "countries": [
      {
        "id": 1,
        "name": "India",
        "iso2": "IN",
        "iso3": "IND",
        "phone_code": "+91",
        "currency_code": "INR"
      }
    ]
  },
  "meta": {"request_id": "{{REQUEST_ID}}"},
  "errors": null
}
```

## 0.2 States List

List active states for any form after a country is selected. Pass either `country_id` or `country_iso2`; supports optional `search` by state name or code.

```bash
curl -X GET "{{BASE_URL}}/api/common/v1/locations/states?country_id=1&search=guj" \
  -H "Accept: application/json" \
  -H "X-Request-Id: {{REQUEST_ID}}"
```

Request body: none.

Response example:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "states": [
      {
        "id": 1,
        "country_id": 1,
        "name": "Gujarat",
        "code": "GJ"
      }
    ]
  },
  "meta": {"request_id": "{{REQUEST_ID}}"},
  "errors": null
}
```

Error example:

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": null,
  "meta": {"request_id": "{{REQUEST_ID}}"},
  "errors": {
    "code": "VALIDATION_ERROR",
    "details": {
      "country_id": ["The country id field is required when country iso2 is not present."]
    }
  }
}
```

## 0.3 Cities List

List active cities for any form after a state is selected. `country_id` is optional and can be used to additionally constrain the result; supports optional `search` by city name.

```bash
curl -X GET "{{BASE_URL}}/api/common/v1/locations/cities?state_id=1&country_id=1&search=ahm" \
  -H "Accept: application/json" \
  -H "X-Request-Id: {{REQUEST_ID}}"
```

Request body: none.

Response example:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "cities": [
      {
        "id": 1,
        "country_id": 1,
        "state_id": 1,
        "name": "Ahmedabad"
      }
    ]
  },
  "meta": {"request_id": "{{REQUEST_ID}}"},
  "errors": null
}
```

Error example:

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": null,
  "meta": {"request_id": "{{REQUEST_ID}}"},
  "errors": {
    "code": "VALIDATION_ERROR",
    "details": {
      "state_id": ["The state id field is required."]
    }
  }
}

