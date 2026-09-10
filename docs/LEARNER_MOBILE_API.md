# Libraro Mobile Application - Learner Side API Documentation

Comprehensive API reference and architecture guide for the **Libraro Learner Mobile Application** (Android & iOS).

---

## 📌 1. Architecture & Middleware Pipeline

Every incoming request to `/api/v1/learner/*` passes through a strict two-layer architecture separating **API Security** from **User Authentication**:

```
                    API Request
                         │
                         ▼
              ┌─────────────────────┐
              │ API Security Layer  │  (ApiSecurityMiddleware, ApiKeyMiddleware, CheckDeviceHeader)
              │                     │
              │ • X-API-KEY         │
              │ • X-Timestamp       │
              │ • X-Nonce           │
              │ • X-Signature       │
              │ • X-Device-Id       │
              │ • X-Platform        │
              └──────────┬──────────┘
                         │
              ┌──────────┴──────────┐
              │                     │
           INVALID                 VALID
              │                     │
              ▼                     ▼
     API_SECURITY_FAILED     User Authentication Layer (auth:learner_api / Sanctum)
     (HTTP 403 Forbidden)          │
     • STOPS IMMEDIATELY   ┌────────┴────────┐
                           │                 │
                       INVALID             VALID
                           │                 │
                           ▼                 ▼
                 USER_UNAUTHENTICATED    Controller
                 (HTTP 401 Unauthorized) (HTTP 200 OK)
                 • STOPS IMMEDIATELY     • Business Logic Executed
```

---

## 🚨 2. Error Response Separation & Specifications

API request security validation and logged-in user authentication are **two distinct layers** with different HTTP status codes, state codes, and payloads:

### Layer 1: API Security Failure (`HTTP 403 Forbidden`)
Returned when the incoming request itself is untrusted or malformed (API Key, Signature, Nonce, Timestamp, Device ID, or Platform failure).

- **HTTP Status:** `403 Forbidden`
- **State Code:** `API_SECURITY_FAILED`
- **JSON Structure:**
```json
{
  "status": false,
  "state_code": "API_SECURITY_FAILED",
  "error_code": "API_SECURITY_FAILED",
  "message": "API request security validation failed: [Reason].",
  "code": 403
}
```

### Layer 2: User Authentication Failure (`HTTP 401 Unauthorized`)
Returned when the request passed all API security checks, but the user's Bearer token is missing, expired, invalid, or logged out.

- **HTTP Status:** `401 Unauthorized`
- **State Code:** `USER_UNAUTHENTICATED`
- **JSON Structure:**
```json
{
  "status": false,
  "state_code": "USER_UNAUTHENTICATED",
  "error_code": "USER_UNAUTHENTICATED",
  "message": "Unauthenticated or invalid session token. Please login again.",
  "code": 401
}
```

### Layer 3: Valid Authenticated Request (`HTTP 200 OK`)
When both security headers and user authentication are valid, the request proceeds to the controller and returns normal business data:
```json
{
  "status": true,
  "message": "Operation successful.",
  "data": { ... }
}
```

---

## 🔐 2. Required Request Headers Specification

Every request sent from the mobile application **MUST** include the following standard headers:

### Global Headers Table (All Endpoints)

| Header Key | Supported Fallbacks | Type | Required | Description | Example / Formula |
| :--- | :--- | :--- | :---: | :--- | :--- |
| `Content-Type` | — | `string` | **Yes** | Payload encoding | `application/json` (or `multipart/form-data`) |
| `Accept` | — | `string` | **Yes** | Instructs server to return JSON responses | `application/json` |
| `X-Timestamp` | — | `string` | **Yes** | Current epoch timestamp in **milliseconds** | `1725000000000` (`Date.now().toString()`) |
| `X-Nonce` | — | `string` | **Yes** | Unique UUID v4 generated per request (prevents replay attacks) | `a8b3c1d2-e4f5-4678-90ab-cdef12345678` |
| `X-Signature` | — | `string` | **Yes** | Hex-encoded **HMAC-SHA256** hash calculated over the request payload | `hash_hmac('sha256', payload, secret)` |
| `X-App-Version`| `App-Version` | `string` | **Yes** | Client mobile application version | `1.0.0` |
| `X-Platform` | `Platform` | `string` | **Yes** | Client OS platform (Used across the entire system as device type: `android` or `ios`) | `android` or `ios` |
| `X-Device-Id` | `device-token`, `device_token` | `string` | **Yes** | Unique Device ID / Android ID / iOS IDFV | `d3b07384d113edec` |
| `X-API-KEY` | `x-api-key` | `string` | **Yes** | Application API key from `.env` (`APP_API_KEY`) | `libraro_mobile_api_key_2026` |
| `device-token` | `device_token`, `Device-Token` | `string` | **Yes** | FCM Push Notification Token or Device identifier | `fcm_token_xyz_123...` |

### Authenticated Headers (Protected Routes Only)

| Header Key | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |
| `Authorization` | `string` | **Yes** | Bearer authentication token received after login | `Bearer 1\|abcdef123456...` |

---

## 🛡️ 3. HMAC-SHA256 Signature Algorithm

To prevent tampering and replay attacks, `ApiSecurityMiddleware` validates that:
1. `|TIMESTAMP_NOW - X-Timestamp| <= 300,000 ms` (5-minute sliding window).
2. `X-Nonce` is unique and has not been used within the last 5 minutes (cached server-side).
3. `X-Signature` matches the computed HMAC-SHA256 hash.

### 📐 Signature Payload Formula
```
PAYLOAD = METHOD + "|" + PATH_WITH_QUERY + "|" + TIMESTAMP + "|" + NONCE + "|" + BODY
SIGNATURE = HMAC_SHA256_HEX(PAYLOAD, HMAC_SECRET)
```

- **`METHOD`**: Uppercase HTTP method (`GET`, `POST`).
- **`PATH_WITH_QUERY`**: Full request URI including query params, e.g.:
  - `/api/v1/learner/dashboard`
  - `/api/v1/learner/notifications?page=1&limit=20&tab=all`
- **`TIMESTAMP`**: Current time in milliseconds string (`Date.now().toString()`).
- **`NONCE`**: Unique UUID v4 string.
- **`BODY`**: Raw JSON request body string (`""` for GET or empty body requests).
- **`HMAC_SECRET`**: Pre-shared secret key matching `APP_HMAC_SECRET` in backend `.env`.

---

### 💻 Signature Generation Code Snippets

#### 🅰️ Dart / Flutter Implementation
```dart
import 'dart:convert';
import 'package:crypto/crypto.dart';
import 'package:uuid/uuid.dart';

Map<String, String> generateSecurityHeaders({
  required String method,
  required String pathWithQuery,
  required String body,
  required String hmacSecret,
  required String apiKey,
  required String deviceId,
  required String platform, // 'android' or 'ios'
  required String deviceToken,
  required String appVersion,
  String? bearerToken,
}) {
  final timestamp = DateTime.now().millisecondsSinceEpoch.toString();
  final nonce = const Uuid().v4();
  final payload = '${method.toUpperCase()}|$pathWithQuery|$timestamp|$nonce|$body';
  
  final hmac = Hmac(sha256, utf8.encode(hmacSecret));
  final signature = hmac.convert(utf8.encode(payload)).toString();

  final headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Timestamp': timestamp,
    'X-Nonce': nonce,
    'X-Signature': signature,
    'X-App-Version': appVersion,
    'X-Platform': platform,
    'X-Device-Id': deviceId,
    'X-API-KEY': apiKey,
    'device-token': deviceToken,
  };

  if (bearerToken != null && bearerToken.isNotEmpty) {
    headers['Authorization'] = 'Bearer $bearerToken';
  }

  return headers;
}
```

#### 🅱️ JavaScript / React Native / Axios Implementation
```javascript
import CryptoJS from 'crypto-js';
import { v4 as uuidv4 } from 'uuid';

export function getSecurityHeaders(method, pathWithQuery, bodyString, config) {
  const timestamp = Date.now().toString();
  const nonce = uuidv4();
  const rawBody = bodyString ? (typeof bodyString === 'string' ? bodyString : JSON.stringify(bodyString)) : '';
  const payload = `${method.toUpperCase()}|${pathWithQuery}|${timestamp}|${nonce}|${rawBody}`;
  
  const signature = CryptoJS.HmacSHA256(payload, config.hmacSecret).toString(CryptoJS.enc.Hex);

  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Timestamp': timestamp,
    'X-Nonce': nonce,
    'X-Signature': signature,
    'X-App-Version': config.appVersion || '1.0.0',
    'X-Platform': config.platform || 'android',
    'X-Device-Id': config.deviceId,
    'X-API-KEY': config.apiKey,
    'device-token': config.deviceToken || config.deviceId,
  };

  if (config.bearerToken) {
    headers['Authorization'] = `Bearer ${config.bearerToken}`;
  }

  return headers;
}
```

#### 🅲 Postman Pre-Request Script (for Testing)
```javascript
const timestamp = Date.now().toString();
const nonce = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
});
const method = pm.request.method.toUpperCase();
const path = pm.request.url.getPathWithQuery();
const body = pm.request.body && pm.request.body.raw ? pm.request.body.raw : '';
const secret = pm.collectionVariables.get('hmac_secret') || 'libraro_secret_key_2026';
const payload = `${method}|${path}|${timestamp}|${nonce}|${body}`;
const signature = CryptoJS.HmacSHA256(payload, secret).toString(CryptoJS.enc.Hex);

pm.collectionVariables.set('x_timestamp', timestamp);
pm.collectionVariables.set('x_nonce', nonce);
pm.collectionVariables.set('x_signature', signature);

pm.request.headers.upsert({ key: 'X-Timestamp', value: timestamp });
pm.request.headers.upsert({ key: 'X-Nonce', value: nonce });
pm.request.headers.upsert({ key: 'X-Signature', value: signature });
pm.request.headers.upsert({ key: 'X-App-Version', value: '1.0.0' });
pm.request.headers.upsert({ key: 'X-Platform', value: pm.collectionVariables.get('device_type') || 'android' });
pm.request.headers.upsert({ key: 'X-Device-Id', value: pm.collectionVariables.get('device_id') || 'd3b07384d113edec' });
pm.request.headers.upsert({ key: 'X-API-KEY', value: pm.collectionVariables.get('api_key') });
pm.request.headers.upsert({ key: 'device-token', value: pm.collectionVariables.get('device_id') || 'd3b07384d113edec' });
```

---

## 📊 4. HTTP Status Codes & Error Handling

| Status Code | Reason | Typical Cause | Response Format |
| :--- | :--- | :--- | :--- |
| `200 OK` | Success | Request succeeded | `{"status": true, "data": ...}` |
| `201 Created` | Resource Created | Seat booking created | `{"status": true, "data": ...}` |
| `400 Bad Request` | Missing Headers | Missing X-Platform, X-Device-Id, or security headers | `{"status": false, "message": "..."}` |
| `401 Unauthorized` | Invalid Auth / Signature | Expired token, invalid HMAC signature, or Nonce replay | `{"status": false, "message": "..."}` |
| `403 Forbidden` | Expired / Invalid QR | Scanned attendance QR expired or belongs to another branch | `{"status": false, "message": "..."}` |
| `404 Not Found` | Resource Not Found | Learner ID or Branch UUID not found | `{"status": false, "message": "..."}` |
| `422 Unprocessable`| Validation Failed | Missing required fields, invalid email, duplicate phone | `{"status": false, "errors": {...}}` |
| `426 Upgrade Required` | App Version Outdated | Client app version is below minimum supported version | `{"force_update": true, "message": "..."}` |
| `429 Too Many Requests`| Rate Limit Exceeded | Exceeded 60 requests/minute (or 10 login attempts/minute)| `{"message": "Too Many Attempts."}` |

---

## 🚀 5. API Endpoints Reference

---

### 🟢 Group A: Public & Branch Discovery Endpoints (Pre-Login)

---

#### 1. App Settings & Maintenance Check
Fetch global application configurations, maintenance mode, minimum version requirements, support email/phone, and policy URLs.

- **Method:** `GET`
- **Endpoint:** `/learner/app-settings`
- **Auth Required:** No

##### Headers:
```http
Content-Type: application/json
Accept: application/json
X-Timestamp: 1725000000000
X-Nonce: 4fae3489-42b7-4cbe-bda2-cb52f9c8f001
X-Signature: e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
X-App-Version: 1.0.0
X-Platform: android
X-Device-Id: d3b07384d113edec
X-API-KEY: your-app-api-key
device-token: d3b07384d113edec
```

##### Response (`200 OK`):
```json
{
  "status": true,
  "message": "Learner settings fetched successfully.",
  "data": {
    "android_version": "1.0.0",
    "ios_version": "1.0.0",
    "force_update": false,
    "privacy_policy": "https://www.libraro.in/privacy-policy",
    "terms_and_conditions": "https://www.libraro.in/terms-and-condition",
    "support_email": [
      "support@libraro.in"
    ],
    "support_number": [
      "+91-8114479678"
    ],
    "web_url": "https://www.libraro.in",
    "youtube": "https://www.youtube.com/@Libraroindia",
    "linkedin": "https://www.linkedin.com/in/libraro/",
    "instagram": "https://www.instagram.com/libraro.in/",
    "facebook": "https://www.facebook.com/libraro.in",
    "whatsapp": "https://wa.me/+918114479678",
    "isMaintenance": false
  }
}
```

---

#### 2. Learner Login
Authenticates a learner using their **UID (Learner No)** or **Email**, with their **Registered Mobile Number** acting as the password. *Enforces single-device login: revokes all existing tokens and registers the active device.*

- **Method:** `POST`
- **Endpoint:** `/learner/login`
- **Auth Required:** No
- **Rate Limit:** 10 requests / minute

##### Request Body (`application/json`):
| Field | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |
| `identifier` | `string` | **Yes** | Learner UID (`LBR-2026-001`) OR registered Email | `"LBR-2026-001"` |
| `password` | `string` | **Yes** | Registered 10-digit mobile number | `"9876543210"` |
| `deviceInfo` | `object` | No | Optional extra device payload | `{"osVersion": "14", "deviceId": "xyz"}` |

> *Backward compatibility: Also accepts `uid` as alias for `identifier` and `mobile` as alias for `password`.*

##### Request Example:
```json
{
  "identifier": "LBR-2026-001",
  "password": "9876543210"
}
```

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Login successful.",
  "token": "1|abcdef1234567890...",
  "data": {
    "accessToken": "1|abcdef1234567890...",
    "tokenType": "Bearer",
   
  }
}
```

---

#### 3. Reset Password / Mobile
Allows a learner to change their login mobile / password.

- **Method:** `POST`
- **Endpoint:** `/learner/reset-password`
- **Auth Required:** Yes
- **Rate Limit:** 10 requests / minute

##### Request Body (`application/json`):
| Field | Type | Required | Description | Example |
| `old_password` | `string` | **Yes** | old mobile / password (min 6 characters) | `"9876500000"` |
| `new_password` | `string` | **Yes** | New mobile / password (min 6 characters) | `"9876500000"` |

##### Response (`200 OK`):
```json
{
  "status": true,
  "message": "Password reset successfully. Please login with your new credentials."
}
```


---

### 🔵 Group B: Authenticated Learner Endpoints

> **All endpoints below require:**
> `Authorization: Bearer <accessToken>`

---

#### 7. Learner Home Dashboard
Fetch primary dashboard data including student details, active 3D ID Card information, shift slot, days left until plan expiry, pending fee dues, promotional slider banners, and unread notification badge count.

- **Method:** `POST`
- **Endpoint:** `/learner/dashboard`
- **Auth Required:** Yes (`Bearer`)

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Home dashboard data fetched successfully.",
  "data": {
    "unreadNotificationCount": 2,
    "banners": [
      {
        "tital": "Welcome to Libraro",
        "description": "",
        "image": "https://your-domain.com/public/img/slider/last_banner_1.webp",
        "link": ""
      },
      {
        "tital": "Libraro top features",
        "description": "",
        "image": "https://your-domain.com/public/img/slider/last_banner_4.webp",
        "link": ""
      },
      {
        "tital": "Why fear Libraro is here",
        "description": "",
        "image": "https://your-domain.com/public/img/slider/last_banner_5.webp",
        "link": ""
      }
    ],
    "idCard": {
      "id": "15",
      "learner_no": "LBR-2026-001",
      "fullName": "RAHUL SHARMA",
      "status": "ACTIVE",
      "planName": "Monthly Full Day",
      "planType": "Full Day Slot",
      "shiftTime": "08:00 AM - 08:00 PM",
      "planStartDate": "2026-08-01",
      "planExpiryDate": "2026-08-31",
      "daysLeft": 1,
      "extend_days": 5,
      "profileImageUrl": "https://your-domain.com/upload/profile_picture/avatar.jpg",
      "pendingPayment": {
        "hasPending": false,
        "amount": 0,
        "dueDate": ""
      },
      "library": {
        "id": "2",
        "name": "Libraro Central Branch",
        "address": "Plot 45, Sector 5, City Center"
      },
      "qrPayload": "T0XMHHkbZPKdW84aELp2tjrlR6LEgcQ9CZ fQZOEO4TgwGTHSR**fPPfQkYP3uAvFj 7bQOYUNBIG*spW4pzqA=="
    }
  }
}
```


#### 8. Learner Profile & Complete Details
Fetch complete details including seat number, active plan, transaction history, locker allocation, and uploaded ID proofs.


##### Full Detailed Profile:
- **Method:** `POST`
- **Endpoint:** `/learner/detail`

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "data": {
    "qr_key": "EpEmUutV...",
    "personal_info": {
       "id": "15",
      "learner_no": "LBR-2026-001",
      "name": "Rahul Sharma",
      "mobile": "9876543210",
      "email": "rahul.sharma@example.com",
      "dob": "2000-01-15",
      "father_name": "Suresh Sharma",
      "profile_picture": "https://your-domain.com/upload/profile_picture/avatar.jpg"
    },
    "plan_info": {
      "seat_id": 12,
      "seat_no": "A-12",
      "seat_with_floor": "1F - A-12",
      "plan": "Monthly Full Day",
      "plan_type": "Full Day Slot",
      "plan_id": 1,
      "plan_type_id": 2,
      "price": 1200,
      "monthdays": "Calendar wise",
      "start_date": "2026-08-01",
      "end_date": "2026-08-31",
      "start_time": "08:00:00",
      "end_time": "20:00:00",
      "status": "Active",
      "mainstatus": "Active",
      "next_plan": 0,
      "frozen_status": 0,
      "freeze_date": null,
      "deleted_at": "",
      "locker": "No",
      "locker_no": "",
      "days_left": 1,
      "extend_days_left": 1,
      "current_days_left": 1,
      "current_extend_days_left": 1,
      "plan_days": 31,
      "plantype_detail": {
        "id": 2,
        "name": "Full Day Slot",
        "start_time": "08:00:00",
        "end_time": "20:00:00"
      },
      "total_gift_days": 0
    },
  
    "other_details": {
      "alternate_mobile": "9876543211",
      "id_proof_id": "1",
      "id_proof_name": "Aadhar Card",
      "id_proof_image": "https://your-domain.com/upload/id_proof/aadhar.jpg",
      "id_proof_no": "1234 5678 9012",
      "address": "Flat 201, Green Heights",
      "remark": ""
    },
     "library": {
        "id": "2",
        "name": "Libraro Central Branch",
        "address": "Plot 45, Sector 5, City Center"
      },
      
  }
}

---

#### 8.1 Learner Profile Setting
Fetch profile setting banners (including festival wishes, birthday wishes, subscription status, and promotional image banners).

- **Method:** `POST` / `GET`
- **Endpoint:** `/learner/profile-setting` or `/learner/profile/setting`
- **Auth Required:** Yes (`Bearer`)

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Profile setting data fetched successfully.",
  "data": {
    "banners": [
      {
        "type": "other_wishes",
        "tital": "Wish you happy Diwali",
        "description": "May this festival of lights bring success and joy.",
        "days_in_left": "",
        "image_resource": "",
        "banner_link": ""
      },
      {
        "type": "birthday_wishes",
        "tital": "Wish you happy birthay",
        "description": "",
        "days_in_left": "",
        "image_resource": "",
        "banner_link": ""
      },
      {
        "type": "subscription",
        "tital": "Monthly Full Day",
        "description": "",
        "days_in_left": 15,
        "image_resource": "",
        "banner_link": ""
      },
      {
        "type": "image",
        "tital": "Welcome to Libraro",
        "description": "",
        "image_resource": "https://your-domain.com/public/img/slider/last_banner_1.webp",
        "days_in_left": "",
        "banner_link": ""
      },
      {
        "type": "image",
        "tital": "Libraro top features",
        "description": "",
        "image_resource": "https://your-domain.com/public/img/slider/last_banner_4.webp",
        "days_in_left": "",
        "banner_link": ""
      },
      {
        "type": "image",
        "tital": "Why fear Libraro is here",
        "description": "",
        "image_resource": "https://your-domain.com/public/img/slider/last_banner_5.webp",
        "days_in_left": "",
        "banner_link": ""
      }
    ]
  }
}
```

---

#### 9. Upload Temporary Image (For Avatar / Document)
Allows the learner to upload an image file first and retrieve temporary file storage path (`temp_path` and `url`) to preview and submit with profile update or other forms.

- **Method:** `POST`
- **Endpoint:** `/learner/upload/temp-images`
- **Auth Required:** Yes (`Bearer`)
- **Content-Type:** `multipart/form-data`

##### Request Parameters (Form-Data):
| Parameter | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |
| `files` / `profile_picture` / `image` | `file` | Yes | Image file (jpg, jpeg, png, webp; max 3MB) | binary file | | `array of files` | No | Multiple images support | `[file1, file2]` |

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "File(s) uploaded successfully.",
  
  "data": {
    "files": [
      {
        "temp_path": "temp/550e8400-e29b-41d4-a716-446655440000.jpg",
        "url": "https://your-domain.com/storage/temp/550e8400-e29b-41d4-a716-446655440000.jpg"
      }
    ],
  }
}
```

---

#### 10. Update Profile & Upload Avatar
Allows the learner to update their personal details and upload or remove their avatar image.

- **Method:** `POST`
- **Endpoint:** `/learner/profile/update`
- **Auth Required:** Yes (`Bearer`)
- **Content-Type:** `multipart/form-data` or `application/json`

##### Request Parameters:
| Parameter | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |
| `name` | `string` | No | Updated full name | `"Rahul Sharma"` |
| `email` | `string` | No | Valid unique email | `"rahul@example.com"` |
| `mobile` | `string` | No | 10-digit mobile number | `"9876543210"` |
| `dob` | `date` | No | `YYYY-MM-DD` | `"2000-01-15"` |
| `father_name` | `string` | No | Father's name | `"Suresh Sharma"` |
| `alternate_mobile` | `string` | No | 10-digit alternate phone | `"9123456780"` |
| `address` | `string` | No | Address text | `"New Delhi, India"` |
| `profile_picture` | `file\|string` | No | Image file upload (jpg, png, webp) or image path | `avatar.jpg` |


##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Profile updated successfully.",
  "data": {
    "student": {
      "id": "15",
      "learner_no": "LBR-2026-001",
      "name": "Rahul Sharma",
      "email": "rahul@example.com",
      "mobile": "9876543210",
      "dob": "2000-01-15",
      "father_name": "Suresh Sharma",
      "alternate_mobile": "9123456780",
      "address": "New Delhi, India",
      "profile_picture": "https://your-domain.com/upload/profile_picture/avatar.jpg"
      
    }
  }
}
```

---



---

#### 11. Notifications List & Tab Filtering
Retrieve learner database notifications supporting the 3 UI tabs (**All**, **Active**, **Expired**), unread green dot indicators, download attachments, and unread counters.

- **Method:** `GET` or `POST`
- **Endpoint:** `/learner/notifications`
- **Auth Required:** Yes (`Bearer`)

##### Query / Body Parameters:
| Parameter | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |

| `page` | `integer`| No | Current page number (Default: `1`) | `1` |
| `limit` | `integer`| No | Items per page (Default: `20`) | `20` |

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Notifications retrieved successfully.",
  "data": {

    "notifications": [
      {
        "id": "e4f8b912-3a5c-4d8e-9f1a-bc0123456789",
        
        "title": "NEET main exam is scheduled on 26-05-2026",
        "description": "NEET main exam is scheduled on 26-05-2026. View schedule and download details.",
        "message": "NEET main exam is scheduled on 26-05-2026. View schedule and download details.",
        "notification_type": "exam",
       
        "is_read": false,
      
        "date_time": "10-02-2026 12:05:20",
       
        "attachment": [{
          "has_attachment": true,
          "url": "https://your-domain.com/upload/attachments/exam_schedule.pdf",
          "name": "exam_schedule.pdf"
        },
        ]
     
      },
      {
       
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 5,
      "last_page": 1,
      "has_more": false
    }
  }
}
```


```

---

#### 13. My Subscriptions List & Tabs (Active, Upcoming, Expired)
Retrieve all seat membership subscriptions and plans for the learner, supporting 4 tabs (**All**, **Active**, **Upcoming**, **Expired**), plan card colors, days used/progress bar metrics, and receipt download links.

- **Method:** `GET` or `POST`
- **Endpoint:** `/learner/subscriptions`
- **Auth Required:** Yes (`Bearer`)

##### Query / Body Parameters:
| Parameter | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |
| `tab` | `string` | No | Tab filter: `all`, `active`, `upcoming`, `expired` (Default: `all`) | `"active"` |
| `page` | `integer`| No | Current page number (Default: `1`) | `1` |
| `limit` | `integer`| No | Items per page (Default: `20`) | `20` |

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Subscriptions fetched successfully.",
  "data": {
    "current_tab": "all",
    "counts": {
      "all": 3,
      "active": 1,
      "upcoming": 1,
      "expired": 1
    },
    "subscriptions": [
      {
        "id": 105,
        "plan_id": 1,
        "plan_type_id": 2,
        "plan_name": "Premium Plan",
        "plan_type": "Monthly",
        "plan_color": "#eab308",
        "status": "active",
        "status_label": "Active",
        "status_color": "#22c55e",
        "start_date": "2026-05-03",
        "end_date": "2026-06-03",
        "formatted_start_date": "03 May 2026",
        "formatted_end_date": "03 Jun 2026",
        "amount_paid": 599,
        "formatted_amount_paid": "₹599",
        "total_days": 32,
        "used_days": 24,
        "days_left": 8,
        "progress_percentage": 75,
        "progress_label": "24 of 32 days used",
        "can_renew": true,
        "download_receipt_url": "https://your-domain.com/receipt/download/105",
        "seat_no": "A-12"
      },
      {
        "id": 104,
        "plan_id": 2,
        "plan_type_id": 1,
        "plan_name": "Smart Plan",
        "plan_type": "Quarterly",
        "plan_color": "#3b82f6",
        "status": "upcoming",
        "status_label": "Upcoming",
        "status_color": "#0ea5e9",
        "start_date": "2026-07-01",
        "end_date": "2026-10-30",
        "formatted_start_date": "01 Jul 2026",
        "formatted_end_date": "30 Oct 2026",
        "amount_paid": 1599,
        "formatted_amount_paid": "₹1,599",
        "total_days": 122,
        "used_days": 0,
        "days_left": 122,
        "progress_percentage": 0,
        "progress_label": "Not start yet",
        "can_renew": false,
        "download_receipt_url": "https://your-domain.com/receipt/download/104",
        "seat_no": "A-12"
      },
      {
        "id": 101,
        "plan_id": 3,
        "plan_type_id": 2,
        "plan_name": "Basic Plan",
        "plan_type": "Monthly",
        "plan_color": "#ec4899",
        "status": "expired",
        "status_label": "Expired",
        "status_color": "#ef4444",
        "start_date": "2026-01-01",
        "end_date": "2026-01-31",
        "formatted_start_date": "01 Jan 2026",
        "formatted_end_date": "31 Jan 2026",
        "amount_paid": 599,
        "formatted_amount_paid": "₹599",
        "total_days": 31,
        "used_days": 31,
        "days_left": 0,
        "progress_percentage": 100,
        "progress_label": "Expired (31 of 31 days used)",
        "can_renew": true,
        "download_receipt_url": "https://your-domain.com/receipt/download/101",
        "seat_no": "A-12"
      }
    ],
    "subscribe_cta": {
      "branch_uuid": "branch-uuid-12345",
      "button_text": "Subscribe"
    },
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 3,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

---

#### 14. My Payment Transactions History
Retrieve payment transaction logs showing Transaction ID, Amount Paid, Payment Mode (Online/Offline/Paylater), Date, Status Badge (SUCCESS/FAILED/PENDING), and Download Receipt link.

- **Method:** `GET` or `POST`
- **Endpoint:** `/learner/transactions`
- **Auth Required:** Yes (`Bearer`)

##### Query / Body Parameters:
- `page` *(optional, integer, default: `1`)*
- `limit` *(optional, integer, default: `20`)*

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Transactions fetched successfully.",
  "data": {
    "transactions": [
      {
        "id": 201,
        "trxn_id": "26652145214",
        "transaction_id": "26652145214",
        "amt_paid": 800,
        "amount_paid": 800,
        "formatted_amount_paid": "₹800",
        "payment_mode": "Online",
        "trxn_date": "12/12/2025",
        "transaction_date": "12/12/2025",
        "status": "SUCCESS",
        "status_color": "#22c55e",
        "plan_name": "Premium Plan",
        "plan_type": "Monthly",
        "total_amount": 800,
        "pending_amount": 0,
        "download_receipt_url": "https://your-domain.com/receipt/download/201",
        "created_at": "2025-12-12T10:30:00.000000Z"
      },
      {
        "id": 200,
        "trxn_id": "26652145210",
        "transaction_id": "26652145210",
        "amt_paid": 0,
        "amount_paid": 0,
        "formatted_amount_paid": "₹0",
        "payment_mode": "Online",
        "trxn_date": "10/12/2025",
        "transaction_date": "10/12/2025",
        "status": "FAILED",
        "status_color": "#ef4444",
        "plan_name": "Premium Plan",
        "plan_type": "Monthly",
        "total_amount": 800,
        "pending_amount": 800,
        "download_receipt_url": "",
        "created_at": "2025-12-10T14:15:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 2,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

---

#### 15. Attendance Summary & History
Retrieve attendance statistics (total members, present days, absent days) and daily records for a given date range. *The backend automatically scopes records to the authenticated learner.*

- **Method:** `POST`
- **Endpoint:** `/learner/attendance/summary`
- **Auth Required:** Yes (`Bearer`)

##### Request Body (`application/json`):
| Field | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |
| `from_date` | `date` | No | Start of range (`YYYY-MM-DD`, default: today) | `"2026-08-01"` |
| `to_date` | `date` | No | End of range (`YYYY-MM-DD`, default: today) | `"2026-08-30"` |

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Attendance fetched successfully",
  "data": {
    "summary": {
      "total_members": 30,
      "present_members": 26,
      "absent_members": 4
    },
    "joining_date": "2026-01-10",
    "filters": {
      "from_date": "01/08/2026",
      "to_date": "30/08/2026"
    },
    "attendance": [
      {
        "learner_id": 15,
        "name": "Rahul Sharma",
        "seat_no": "12",
        "plan_type": "Full Day",
        "attendance_date": "2026-08-30",
        "punch_in": "08:15 AM",
        "punch_out": "07:45 PM",
        "duration_in_library": "11:30 Hrs",
        "attendance_status": "Present"
      }
    ]
  }
}
```

---

#### 16. Daily Detailed Attendance Punch Logs
Fetch raw timestamp records showing every **Punch IN** and **Punch OUT** for a specific day.

- **Method:** `POST`
- **Endpoint:** `/learner/attendance/logs`
- **Auth Required:** Yes (`Bearer`)

##### Request Body (`application/json`):
| Field | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |
| `date` | `date` | **Yes** | Date format `YYYY-MM-DD` | `"2026-08-30"` |

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Attendance logs fetched successfully",
  "data": {
    "learner_id": 15,
    "date": "2026-08-30",
    "logs": [
      {
        "punch": "IN",
        "punch_time": "08:15 AM",
        "source": "QR",
        "datetime": "2026-08-30 08:15 AM"
      },
      {
        "punch": "OUT",
        "punch_time": "01:10 PM",
        "source": "QR",
        "datetime": "2026-08-30 01:10 PM"
      },
      {
        "punch": "IN",
        "punch_time": "02:00 PM",
        "source": "QR",
        "datetime": "2026-08-30 02:00 PM"
      },
      {
        "punch": "OUT",
        "punch_time": "07:45 PM",
        "source": "QR",
        "datetime": "2026-08-30 07:45 PM"
      }
    ]
  }
}
```

---

#### 17. Dynamic QR Attendance Punch (In / Out)
Learner scans the rotating dynamic QR code displayed on the library screen/wall to capture attendance.

- **Method:** `POST`
- **Endpoint:** `/learner/attendance/qr-scan`
- **Auth Required:** Yes (`Bearer`)

##### Request Body (`application/json`):
| Field | Type | Required | Description | Example |
| :--- | :--- | :---: | :--- | :--- |
| `qr` | `string` | **Yes** | Scanned base64 dynamic QR token | `"YnJhbmNoXzF8MTI5ODcy...=="` |

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Punch IN recorded successfully at 08:15 AM"
}
```

##### Error Response (Invalid  QR - `403 Forbidden`):
```json
{
  "status": false,
  "message": "QR invalid"
}
```

---

#### 18. Learner Logout
Revokes all active auth tokens for the learner and unregisters the device token.

- **Method:** `POST`
- **Endpoint:** `/learner/logout`
- **Auth Required:** Yes (`Bearer`)

##### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Logged out from all devices successfully."
}
```

---

## 📋 6. Complete Endpoints Quick Reference Table

| HTTP Method | Endpoint URI | Auth Required | Purpose & Key Features |
| :--- | :--- | :---: | :--- |
| `GET` | `/api/v1/learner/app-settings` | ❌ Public | App versioning, maintenance mode, policy links & support contacts |
| `POST` | `/api/v1/learner/login` | ❌ Public | Learner login (UID/Email + Mobile) with single-device enforcement |
| `POST` | `/api/v1/learner/reset-password` | ❌ Public | Reset login mobile / password |
| `POST` | `/api/v1/learner/dashboard` | 🔒 `learner_api` | Main home dashboard, 3D ID card data & unread notifications |
| `GET` | `/api/v1/learner/profile` | 🔒 `learner_api` | Quick learner profile summary |
| `POST` | `/api/v1/learner/detail` | 🔒 `learner_api` | Comprehensive profile, seat, plan, transactions & ID proofs |
| `GET/POST`| `/api/v1/learner/profile-setting` | 🔒 `learner_api` | Profile setting banners (wishes, subscription, image banners) |
| `POST` | `/api/v1/learner/profile/update` | 🔒 `learner_api` | Update profile information & avatar photo |
| `GET/POST`| `/api/v1/learner/notifications` | 🔒 `learner_api` | Notification list with tabs (`all`, `active`, `expired`) & unread count |
| `POST` | `/api/v1/learner/notifications/read` | 🔒 `learner_api` | Mark single or all notifications as read |
| `GET/POST`| `/api/v1/learner/subscriptions` | 🔒 `learner_api` | My Subscriptions list with tabs (`all`, `active`, `upcoming`, `expired`) & progress |
| `GET/POST`| `/api/v1/learner/transactions` | 🔒 `learner_api` | My Payment Transactions history with status badges & receipt download |
| `POST` | `/api/v1/learner/attendance/summary` | 🔒 `learner_api` | Range-based attendance summary & list |
| `POST` | `/api/v1/learner/attendance/logs` | 🔒 `learner_api` | Daily punch IN / OUT timestamps |
| `POST` | `/api/v1/learner/attendance/qr-scan` | 🔒 `learner_api` | Mark attendance by scanning branch dynamic QR |
| `POST` | `/api/v1/learner/logout` | 🔒 `learner_api` | Revoke token and logout from all devices |
