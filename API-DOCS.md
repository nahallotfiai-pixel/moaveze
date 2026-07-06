# Moaveze Plus REST API Documentation

> Comprehensive REST API for the Moaveze Plus WordPress plugin.
> Designed for Flutter mobile app integration.

## Base URL

```
https://your-site.com/wp-json/moaveze/v1/
```

All endpoints are prefixed with `moaveze/v1`. Responses are always JSON.

---

## Authentication

### Overview

The API uses Bearer token authentication. Tokens are generated on login and stored server-side in WordPress user meta.

- **Public endpoints**: No authentication required
- **User endpoints**: Require valid Bearer token
- **Staff endpoints**: Require admin or `moaveze_consultant` role

### Authentication Flow

1. Call `POST /auth/login` with phone + password
2. Receive a token in the response
3. Include the token in all authenticated requests via the `Authorization` header
4. Token expires after **30 days**
5. Use `POST /auth/refresh` to get a new token before expiry

### Header Format

```
Authorization: Bearer <your_token_here>
```

### Flutter/Dart Example

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class MoavezeApiService {
  static const String baseUrl = 'https://your-site.com/wp-json/moaveze/v1';
  String? _token;

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  /// Login and store token
  Future<Map<String, dynamic>> login(String phone, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'phone': phone, 'password': password}),
    );

    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      _token = data['data']['token']['token'];
      // Store token securely (e.g., flutter_secure_storage)
    }
    return data;
  }

  /// Get exchanges list with filters
  Future<Map<String, dynamic>> getExchanges({
    int page = 1,
    int perPage = 12,
    String? type,
    String? district,
    String? search,
  }) async {
    final params = {
      'page': page.toString(),
      'per_page': perPage.toString(),
      if (type != null) 'type': type,
      if (district != null) 'district': district,
      if (search != null) 'search': search,
    };

    final uri = Uri.parse('$baseUrl/exchanges')
        .replace(queryParameters: params);

    final response = await http.get(uri, headers: _headers);
    return jsonDecode(response.body);
  }

  /// Get single exchange details
  Future<Map<String, dynamic>> getExchange(int id) async {
    final response = await http.get(
      Uri.parse('$baseUrl/exchanges/$id'),
      headers: _headers,
    );
    return jsonDecode(response.body);
  }

  /// Submit an offer
  Future<Map<String, dynamic>> submitOffer({
    required int exchangeId,
    String? message,
    int? cashOffered,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/offers'),
      headers: _headers,
      body: jsonEncode({
        'exchange_id': exchangeId,
        if (message != null) 'message': message,
        if (cashOffered != null) 'cash_offered': cashOffered,
      }),
    );
    return jsonDecode(response.body);
  }

  /// Toggle favorite
  Future<void> addFavorite(int listingId) async {
    await http.post(
      Uri.parse('$baseUrl/favorites/$listingId'),
      headers: _headers,
    );
  }

  Future<void> removeFavorite(int listingId) async {
    await http.delete(
      Uri.parse('$baseUrl/favorites/$listingId'),
      headers: _headers,
    );
  }
}
```

---

## Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    "items": [...],
  },
  "meta": {
    "total": 156,
    "page": 1,
    "per_page": 12,
    "pages": 13
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "invalid_credentials",
    "message": "شماره تلفن یا رمز عبور اشتباه است."
  }
}
```

---

## Pagination

All list endpoints support pagination via query parameters:

| Parameter | Type | Default | Max | Description |
|-----------|------|---------|-----|-------------|
| `page` | integer | 1 | - | Page number |
| `per_page` | integer | 12 | 100 | Items per page |

Pagination metadata is included in the `meta` object and as HTTP headers:
- `X-WP-Total`: Total number of items
- `X-WP-TotalPages`: Total number of pages

---

## Rate Limiting

Response headers include rate limit information:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in window

---


## Endpoints Reference

### 1. Authentication

#### POST `/auth/login`

Login with phone number and password.

**Auth Required**: No

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `phone` | string | Yes | Iranian mobile (09XXXXXXXXX) |
| `password` | string | Yes | User password |

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 42,
      "display_name": "علی محمدی",
      "phone": "09121234567",
      "email": "ali@example.com",
      "avatar": "https://...",
      "roles": ["subscriber"],
      "registered": "2024-01-15T10:30:00+00:00"
    },
    "token": {
      "token": "abc123...xyz",
      "expires_at": "2025-02-14T10:30:00+00:00",
      "expires_in": 2592000
    }
  }
}
```

---

#### POST `/auth/register`

Register a new user account.

**Auth Required**: No

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `phone` | string | Yes | Iranian mobile (09XXXXXXXXX) |
| `password` | string | Yes | Min 6 characters |
| `display_name` | string | Yes | User's display name |
| `email` | string | No | Email address |

**Response:** Same format as login (includes token)

---

#### GET `/auth/me`

Get current authenticated user profile.

**Auth Required**: Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 42,
      "display_name": "علی محمدی",
      "phone": "09121234567",
      "email": "ali@example.com",
      "avatar": "https://...",
      "roles": ["subscriber"],
      "registered": "2024-01-15T10:30:00+00:00",
      "favorites_count": 5,
      "listings_count": 2
    }
  }
}
```

---

#### POST `/auth/refresh`

Get a new token (extends expiry).

**Auth Required**: Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "token": {
      "token": "new_token_here...",
      "expires_at": "2025-03-15T10:30:00+00:00",
      "expires_in": 2592000
    }
  }
}
```

---

### 2. Exchange Listings (Public)

#### GET `/exchanges`

Paginated list of exchange listings with filters.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 12 | Items per page (max 100) |
| `type` | string | - | Property type slug |
| `district` | string | - | District slug |
| `exchange_type` | string | - | `exact`, `flexible`, `with_cash` |
| `min_value` | integer | - | Minimum property value (Toman) |
| `max_value` | integer | - | Maximum property value (Toman) |
| `search` | string | - | Keyword search |
| `orderby` | string | `date` | `date`, `value_asc`, `value_desc`, `area_asc`, `area_desc` |

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 123,
        "title": "آپارتمان 120 متری ولیعصر",
        "slug": "apartment-120-valiasr",
        "url": "https://site.com/exchange/apartment-120-valiasr/",
        "value": 15000000000,
        "value_formatted": "15 میلیارد تومان",
        "area": 120,
        "exchange_type": "flexible",
        "property_type": {
          "id": 5,
          "name": "آپارتمان",
          "slug": "apartment"
        },
        "district": {
          "id": 12,
          "name": "ولیعصر",
          "slug": "valiasr"
        },
        "thumbnail": {
          "id": 456,
          "thumbnail": {"url": "https://...", "width": 150, "height": 150},
          "medium": {"url": "https://...", "width": 300, "height": 200},
          "large": {"url": "https://...", "width": 1024, "height": 683},
          "full": {"url": "https://...", "width": 1920, "height": 1280},
          "alt": ""
        },
        "verified": true,
        "featured": false,
        "views": 234,
        "created_at": "2024-12-01T14:30:00+00:00",
        "created_at_jalali": "۱۴۰۳/۰۹/۱۱",
        "time_ago": "۳ روز پیش"
      }
    ]
  },
  "meta": {
    "total": 156,
    "page": 1,
    "per_page": 12,
    "pages": 13
  }
}
```

---

#### GET `/exchanges/{id}`

Full details for a single listing.

**Response includes additional fields:**
- `description` / `description_raw`
- `rooms`, `floor`, `year_built`
- `latitude`, `longitude`
- `cash_difference`, `cash_difference_formatted`, `cash_direction`
- `gallery[]` (array of image objects with multiple sizes)
- `features[]` (array of feature objects)
- `conditions`, `preferred_types`, `preferred_districts`
- `video_url`
- `has_valuation`
- `price_per_sqm`, `price_per_sqm_formatted`
- `author` (id, display_name, avatar)

---

#### GET `/exchanges/featured`

Featured/VIP listings.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | integer | 10 | Max items (max 50) |

---

#### GET `/exchanges/recent`

Latest listings.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | integer | 10 | Max items (max 50) |

---


### 3. Exchange Listings (Authenticated)

#### GET `/exchanges/my`

Current user's own listings (all statuses).

**Auth Required**: Yes

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 12 | Items per page |
| `status` | string | - | Filter by status: `publish`, `pending`, `draft` |

---

#### POST `/exchanges`

Submit a new exchange listing.

**Auth Required**: Yes

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | Yes | Listing title |
| `description` | string | No | Description text |
| `property_value` | integer | No | Value in Toman |
| `area_sqm` | integer | No | Area in square meters |
| `rooms` | integer | No | Number of rooms |
| `floor` | string | No | Floor number/info |
| `year_built` | integer | No | Year built |
| `exchange_type` | string | No | `exact`, `flexible`, `with_cash` |
| `latitude` | float | No | GPS latitude |
| `longitude` | float | No | GPS longitude |
| `cash_difference` | integer | No | Cash difference amount |
| `cash_direction` | string | No | `give` or `receive` |
| `contact_name` | string | No | Contact name |
| `contact_phone` | string | No | Contact phone |
| `property_type` | string | No | Property type term slug or name |
| `district` | string | No | District term slug or name |
| `features` | array | No | Array of feature slugs |
| `gallery` | array | No | Array of attachment IDs |

**Note**: New listings start with `pending` status and require admin approval.

---

#### PUT `/exchanges/{id}`

Update an existing listing (owner only).

**Auth Required**: Yes (must be listing owner or admin)

Same parameters as POST, all optional.

---

#### DELETE `/exchanges/{id}`

Soft-delete a listing (moves to trash).

**Auth Required**: Yes (must be listing owner or admin)

---

### 4. Search & Filters (Public)

#### GET `/search`

Advanced search combining all filter types.

Includes all parameters from `GET /exchanges` plus:

| Parameter | Type | Description |
|-----------|------|-------------|
| `min_area` | integer | Minimum area (sqm) |
| `max_area` | integer | Maximum area (sqm) |
| `rooms` | integer | Minimum rooms |
| `features` | string | Comma-separated feature slugs |
| `lat` | float | Center latitude for geo search |
| `lng` | float | Center longitude for geo search |
| `radius` | float | Search radius in kilometers |

---

#### GET `/filters/types`

All property types (taxonomy terms).

```json
{
  "success": true,
  "data": {
    "items": [
      {"id": 5, "name": "آپارتمان", "slug": "apartment", "count": 45},
      {"id": 6, "name": "ویلایی", "slug": "villa", "count": 23}
    ]
  }
}
```

---

#### GET `/filters/districts`

All districts.

```json
{
  "success": true,
  "data": {
    "items": [
      {"id": 12, "name": "ولیعصر", "slug": "valiasr", "count": 15, "parent": 0},
      {"id": 13, "name": "شهرک قدس", "slug": "shahrak-qods", "count": 8, "parent": 0}
    ]
  }
}
```

---

#### GET `/filters/features`

All property features/amenities.

---

### 5. Map (Public)

#### GET `/map/markers`

Lightweight markers for all geolocated listings.

| Parameter | Type | Description |
|-----------|------|-------------|
| `type` | string | Filter by property type slug |
| `district` | string | Filter by district slug |

**Response:**
```json
{
  "success": true,
  "data": {
    "markers": [
      {
        "id": 123,
        "title": "آپارتمان 120 متری",
        "lat": 38.0722,
        "lng": 46.2987,
        "value": 15000000000,
        "value_formatted": "15 میلیارد تومان",
        "type": "آپارتمان",
        "type_slug": "apartment",
        "thumbnail": "https://..."
      }
    ],
    "total": 89
  }
}
```

---

#### GET `/map/cluster`

Clustered markers for map performance.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `north` | float | Yes | North bound latitude |
| `south` | float | Yes | South bound latitude |
| `east` | float | Yes | East bound longitude |
| `west` | float | Yes | West bound longitude |
| `zoom` | integer | No | Map zoom level (default: 12) |

**Response:**
```json
{
  "success": true,
  "data": {
    "clusters": [
      {
        "lat": 38.0722,
        "lng": 46.2987,
        "count": 5,
        "items": [
          {"id": 123, "title": "...", "value": 15000000000}
        ]
      }
    ],
    "total": 89
  }
}
```

---


### 6. Offers (Authenticated)

#### POST `/offers`

Submit an offer on a listing.

**Auth Required**: Yes

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `exchange_id` | integer | Yes | Target listing exchange ID |
| `offer_type` | string | No | `direct` (default), `counter` |
| `message` | string | No | Message to listing owner |
| `cash_offered` | integer | No | Cash amount offered (Toman) |
| `assets_offered` | string | No | Description of assets offered |
| `from_exchange_id` | integer | No | User's own listing ID (for property swap) |

**Error Codes:**
- `invalid_exchange` — Listing not found or inactive
- `self_offer` — Cannot offer on own listing
- `duplicate_offer` — Already have a pending offer

---

#### GET `/offers/my`

Current user's sent offers.

**Auth Required**: Yes

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 12 | Items per page |
| `status` | string | - | Filter: `pending`, `accepted`, `rejected`, `negotiating`, `countered`, `withdrawn` |

---

#### GET `/offers/received`

Offers received on user's listings.

**Auth Required**: Yes

Same pagination parameters as above.

---

#### PUT `/offers/{id}/status`

Accept, reject, or negotiate an offer.

**Auth Required**: Yes (listing owner or staff only)

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | Yes | `accepted`, `rejected`, `negotiating` |
| `notes` | string | No | Response notes |

---

### 7. Favorites / Wishlist (Authenticated)

#### GET `/favorites`

Get current user's favorites list.

**Auth Required**: Yes

---

#### POST `/favorites/{listing_id}`

Add a listing to favorites.

**Auth Required**: Yes

---

#### DELETE `/favorites/{listing_id}`

Remove a listing from favorites.

**Auth Required**: Yes

---

### 8. Notifications (Authenticated)

#### GET `/notifications`

User's notifications (paginated).

**Auth Required**: Yes

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 15 | Items per page (max 50) |

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "type": "new_offer",
        "icon": "📩",
        "label": "پیشنهاد جدید",
        "title": "پیشنهاد معاوضه جدید",
        "message": "پیشنهاد جدیدی برای آگهی شما دریافت شد.",
        "data": {"offer_id": 15, "exchange_id": 42},
        "is_read": false,
        "created_at": "2024-12-05T14:30:00+00:00",
        "created_at_jalali": "۱۴۰۳/۰۹/۱۵ ۱۴:۳۰",
        "time_ago": "۲ ساعت پیش"
      }
    ]
  },
  "meta": {"total": 25, "page": 1, "per_page": 15, "pages": 2}
}
```

---

#### GET `/notifications/unread-count`

Quick endpoint for badge count.

**Auth Required**: Yes

```json
{"success": true, "data": {"unread_count": 3}}
```

---

#### PUT `/notifications/{id}/read`

Mark single notification as read.

**Auth Required**: Yes

---

#### PUT `/notifications/read-all`

Mark all notifications as read.

**Auth Required**: Yes

---


### 9. Valuation

#### POST `/valuation/estimate`

Public price estimation based on comparable listings.

**Auth Required**: No

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `area` | integer | Yes | Area in sqm (min 10) |
| `type` | string | No | Property type |
| `district` | string | No | District name |
| `rooms` | integer | No | Number of rooms |
| `year_built` | integer | No | Year built |

**Response:**
```json
{
  "success": true,
  "data": {
    "estimate": {
      "value": 12000000000,
      "value_formatted": "12 میلیارد تومان",
      "min_value": 10200000000,
      "min_formatted": "10.2 میلیارد تومان",
      "max_value": 13800000000,
      "max_formatted": "13.8 میلیارد تومان",
      "price_per_sqm": 100000000,
      "price_per_sqm_formatted": "100 میلیون تومان",
      "comparables_count": 15,
      "confidence": "high"
    },
    "input": {
      "area": 120,
      "type": "آپارتمان",
      "district": "ولیعصر",
      "rooms": 3
    }
  }
}
```

**Confidence Levels:**
- `high` — 10+ comparable listings found
- `medium` — 5-9 comparable listings
- `low` — fewer than 5 comparables

---

#### GET `/valuation/{post_id}`

Get expert valuation for a listing.

**Auth Required**: Staff only (admin or moaveze_consultant)

---

#### GET `/valuation/{post_id}/history`

Valuation history for a listing.

**Auth Required**: Staff only

---

### 10. Config & Stats (Public)

#### GET `/config`

App configuration — use this on app startup to get available options.

```json
{
  "success": true,
  "data": {
    "property_types": [
      {"id": 5, "name": "آپارتمان", "slug": "apartment", "count": 45}
    ],
    "districts": [
      {"id": 12, "name": "ولیعصر", "slug": "valiasr", "count": 15}
    ],
    "exchange_types": [
      {"value": "exact", "label": "معاوضه دقیق"},
      {"value": "flexible", "label": "معاوضه انعطاف‌پذیر"},
      {"value": "with_cash", "label": "معاوضه با مابه‌التفاوت"}
    ],
    "features": {
      "offers_enabled": true,
      "favorites_enabled": true,
      "notifications_enabled": true,
      "map_enabled": true,
      "valuation_enabled": true,
      "auction_enabled": false
    },
    "app": {
      "name": "معاوضه پلاس",
      "version": "2.0.0",
      "currency": "تومان",
      "locale": "fa_IR",
      "rtl": true
    }
  }
}
```

---

#### GET `/stats`

Platform statistics for display in the app.

```json
{
  "success": true,
  "data": {
    "total_listings": 156,
    "total_completed": 43,
    "total_users": 1250,
    "total_offers": 890,
    "by_type": [
      {"name": "آپارتمان", "slug": "apartment", "count": 45}
    ],
    "top_districts": [
      {"name": "ولیعصر", "slug": "valiasr", "count": 15}
    ]
  }
}
```

---

### 11. Contact / Communication (Authenticated)

#### POST `/contact/{listing_id}`

Request contact info for a listing owner. May require payment (credit deduction) depending on site settings.

**Auth Required**: Yes

**Response:**
```json
{
  "success": true,
  "data": {
    "contact": {
      "name": "حسین رضایی",
      "phone": "09121234567"
    }
  }
}
```

**Error Code:**
- `insufficient_credit` (HTTP 402) — Not enough credit; message includes the cost.

---

#### GET `/contact/requests`

User's contact request history.

**Auth Required**: Yes

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 12 | Items per page |

---


## Error Codes Reference

| HTTP Status | Code | Description |
|-------------|------|-------------|
| 400 | `missing_title` | Required field missing |
| 400 | `invalid_area` | Area value invalid |
| 400 | `self_offer` | Cannot offer on own listing |
| 401 | `unauthorized` | Authentication required |
| 401 | `invalid_credentials` | Wrong phone/password |
| 402 | `insufficient_credit` | Not enough credit for action |
| 403 | `forbidden` | Insufficient permissions |
| 404 | `not_found` | Resource not found |
| 404 | `invalid_exchange` | Exchange listing not found/inactive |
| 409 | `phone_exists` | Phone number already registered |
| 409 | `email_exists` | Email already registered |
| 409 | `duplicate_offer` | Already have pending offer |
| 422 | `insufficient_data` | Not enough data for operation |
| 500 | `create_failed` | Server error creating resource |
| 500 | `offer_failed` | Server error submitting offer |
| 500 | `registration_failed` | Server error during registration |

---

## Data Format Notes

### Prices
All price fields include both raw integer and formatted string:
```json
{
  "value": 15000000000,
  "value_formatted": "15 میلیارد تومان"
}
```

### Dates
All date fields include ISO 8601, Jalali (Shamsi), and relative time:
```json
{
  "created_at": "2024-12-01T14:30:00+00:00",
  "created_at_jalali": "۱۴۰۳/۰۹/۱۱",
  "time_ago": "۳ روز پیش"
}
```

### Images
Image objects include multiple sizes with dimensions:
```json
{
  "id": 456,
  "thumbnail": {"url": "https://...", "width": 150, "height": 150},
  "medium": {"url": "https://...", "width": 300, "height": 200},
  "large": {"url": "https://...", "width": 1024, "height": 683},
  "full": {"url": "https://...", "width": 1920, "height": 1280},
  "alt": "توضیح تصویر"
}
```

### Taxonomy Objects
Type, district, and feature fields use consistent object format:
```json
{
  "id": 5,
  "name": "آپارتمان",
  "slug": "apartment"
}
```

---

## Flutter Service Class Skeleton

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

/// Complete API service for Moaveze Plus Flutter app
class MoavezeApi {
  final String baseUrl;
  String? _token;

  MoavezeApi({required this.baseUrl});

  // ─── Auth ────────────────────────────────────────────
  bool get isAuthenticated => _token != null;

  void setToken(String token) => _token = token;
  void clearToken() => _token = null;

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  Future<ApiResponse> login(String phone, String password) =>
      _post('/auth/login', {'phone': phone, 'password': password});

  Future<ApiResponse> register(String phone, String password, String name, {String? email}) =>
      _post('/auth/register', {
        'phone': phone,
        'password': password,
        'display_name': name,
        if (email != null) 'email': email,
      });

  Future<ApiResponse> getProfile() => _get('/auth/me');
  Future<ApiResponse> refreshToken() => _post('/auth/refresh', {});

  // ─── Exchanges ───────────────────────────────────────
  Future<ApiResponse> getExchanges({
    int page = 1, int perPage = 12,
    String? type, String? district, String? exchangeType,
    int? minValue, int? maxValue, String? search, String? orderby,
  }) => _get('/exchanges', params: {
    'page': '$page', 'per_page': '$perPage',
    if (type != null) 'type': type,
    if (district != null) 'district': district,
    if (exchangeType != null) 'exchange_type': exchangeType,
    if (minValue != null) 'min_value': '$minValue',
    if (maxValue != null) 'max_value': '$maxValue',
    if (search != null) 'search': search,
    if (orderby != null) 'orderby': orderby,
  });

  Future<ApiResponse> getExchange(int id) => _get('/exchanges/$id');
  Future<ApiResponse> getFeaturedExchanges({int limit = 10}) =>
      _get('/exchanges/featured', params: {'limit': '$limit'});
  Future<ApiResponse> getRecentExchanges({int limit = 10}) =>
      _get('/exchanges/recent', params: {'limit': '$limit'});
  Future<ApiResponse> getMyExchanges({int page = 1, String? status}) =>
      _get('/exchanges/my', params: {'page': '$page', if (status != null) 'status': status});

  Future<ApiResponse> createExchange(Map<String, dynamic> data) =>
      _post('/exchanges', data);
  Future<ApiResponse> updateExchange(int id, Map<String, dynamic> data) =>
      _put('/exchanges/$id', data);
  Future<ApiResponse> deleteExchange(int id) => _delete('/exchanges/$id');

  // ─── Search & Filters ────────────────────────────────
  Future<ApiResponse> search(Map<String, String> params) =>
      _get('/search', params: params);
  Future<ApiResponse> getPropertyTypes() => _get('/filters/types');
  Future<ApiResponse> getDistricts() => _get('/filters/districts');
  Future<ApiResponse> getFeatures() => _get('/filters/features');

  // ─── Map ─────────────────────────────────────────────
  Future<ApiResponse> getMapMarkers({String? type, String? district}) =>
      _get('/map/markers', params: {
        if (type != null) 'type': type,
        if (district != null) 'district': district,
      });
  Future<ApiResponse> getMapClusters(double n, double s, double e, double w, {int zoom = 12}) =>
      _get('/map/cluster', params: {
        'north': '$n', 'south': '$s', 'east': '$e', 'west': '$w', 'zoom': '$zoom',
      });

  // ─── Offers ──────────────────────────────────────────
  Future<ApiResponse> createOffer(Map<String, dynamic> data) => _post('/offers', data);
  Future<ApiResponse> getMyOffers({int page = 1, String? status}) =>
      _get('/offers/my', params: {'page': '$page', if (status != null) 'status': status});
  Future<ApiResponse> getReceivedOffers({int page = 1, String? status}) =>
      _get('/offers/received', params: {'page': '$page', if (status != null) 'status': status});
  Future<ApiResponse> updateOfferStatus(int id, String status, {String? notes}) =>
      _put('/offers/$id/status', {'status': status, if (notes != null) 'notes': notes});

  // ─── Favorites ───────────────────────────────────────
  Future<ApiResponse> getFavorites() => _get('/favorites');
  Future<ApiResponse> addFavorite(int id) => _post('/favorites/$id', {});
  Future<ApiResponse> removeFavorite(int id) => _delete('/favorites/$id');

  // ─── Notifications ───────────────────────────────────
  Future<ApiResponse> getNotifications({int page = 1, int perPage = 15}) =>
      _get('/notifications', params: {'page': '$page', 'per_page': '$perPage'});
  Future<ApiResponse> getUnreadCount() => _get('/notifications/unread-count');
  Future<ApiResponse> markNotificationRead(int id) => _put('/notifications/$id/read', {});
  Future<ApiResponse> markAllRead() => _put('/notifications/read-all', {});

  // ─── Valuation ───────────────────────────────────────
  Future<ApiResponse> estimatePrice(Map<String, dynamic> data) =>
      _post('/valuation/estimate', data);

  // ─── Config & Stats ──────────────────────────────────
  Future<ApiResponse> getConfig() => _get('/config');
  Future<ApiResponse> getStats() => _get('/stats');

  // ─── Contact ─────────────────────────────────────────
  Future<ApiResponse> requestContact(int listingId) => _post('/contact/$listingId', {});
  Future<ApiResponse> getContactRequests({int page = 1}) =>
      _get('/contact/requests', params: {'page': '$page'});

  // ─── HTTP Helpers ────────────────────────────────────
  Future<ApiResponse> _get(String path, {Map<String, String>? params}) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: params);
    final response = await http.get(uri, headers: _headers);
    return ApiResponse.fromResponse(response);
  }

  Future<ApiResponse> _post(String path, Map<String, dynamic> body) async {
    final response = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: _headers,
      body: jsonEncode(body),
    );
    return ApiResponse.fromResponse(response);
  }

  Future<ApiResponse> _put(String path, Map<String, dynamic> body) async {
    final response = await http.put(
      Uri.parse('$baseUrl$path'),
      headers: _headers,
      body: jsonEncode(body),
    );
    return ApiResponse.fromResponse(response);
  }

  Future<ApiResponse> _delete(String path) async {
    final response = await http.delete(Uri.parse('$baseUrl$path'), headers: _headers);
    return ApiResponse.fromResponse(response);
  }
}

/// Parsed API response wrapper
class ApiResponse {
  final bool success;
  final Map<String, dynamic>? data;
  final Map<String, dynamic>? error;
  final Map<String, dynamic>? meta;
  final int statusCode;

  ApiResponse({
    required this.success,
    this.data,
    this.error,
    this.meta,
    required this.statusCode,
  });

  factory ApiResponse.fromResponse(http.Response response) {
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    return ApiResponse(
      success: body['success'] == true,
      data: body['data'] as Map<String, dynamic>?,
      error: body['error'] as Map<String, dynamic>?,
      meta: body['meta'] as Map<String, dynamic>?,
      statusCode: response.statusCode,
    );
  }

  String get errorMessage => error?['message'] ?? 'Unknown error';
  String get errorCode => error?['code'] ?? 'unknown';
}
```

---

## Notes for Flutter Developers

1. **RTL Support**: All text content is in Persian (RTL). Ensure your app uses `Directionality.rtl`.

2. **Image Sizes**: Use `thumbnail` for lists, `medium` for cards, `large` for detail views, `full` for gallery zoom.

3. **Offline Support**: Cache the `/config` response locally — it rarely changes. Use it to populate filter dropdowns.

4. **Token Storage**: Store the auth token in `flutter_secure_storage`, not shared preferences.

5. **Polling Notifications**: Use `GET /notifications/unread-count` with a timer (every 30-60 seconds) for badge updates. Consider WebSocket/FCM for real-time in the future.

6. **Price Display**: Always use the `_formatted` version for display. The raw integer is for calculations/sorting.

7. **Jalali Dates**: The API provides both ISO and Jalali dates. Use `time_ago` for feed-style displays.

8. **Error Handling**: Always check `response.success` first. If `false`, display `response.error.message` to the user.

9. **Map Performance**: Use `/map/cluster` instead of `/map/markers` when the user has zoomed out. Switch to individual markers at zoom >= 15.

10. **Image Upload**: To upload images for a new listing, first upload to WordPress media endpoint (`/wp-json/wp/v2/media`) with the same auth token, then pass the returned attachment IDs in the `gallery` array.
