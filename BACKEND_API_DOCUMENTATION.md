# Libraro Student App — Complete Backend API Specification

> **Base URL:** `{{base_url}}/api/v1`  
> **Protocol:** `HTTPS (TLS 1.3 Recommended)`  
> **Content-Type:** `application/json`  
> **Authentication:** `Bearer JWT Token` + `HMAC-SHA256 Request Signing`

---

## 1. Global Security & Standard Headers

Every request from the mobile application must include the following standard security headers to protect against reverse engineering, replay attacks, and unauthorized bots:

### Required Headers Table

| Header Key | Type | Description | Example |
| :--- | :--- | :--- | :--- |
| `Authorization` | `String` | Bearer JWT access token (omitted only on public login/config) | `Bearer eyJhbGciOiJIUzI1Ni...` |
| `X-Timestamp` | `String` | Current UTC timestamp in milliseconds | `1771982400000` |
| `X-Nonce` | `String` | Unique UUIDv4 per request (Prevents Replay Attacks) | `f47ac10b-58cc-4372-a567-0e02b2c3d479` |
| `X-Signature` | `String` | `HMAC-SHA256(Method + Path + Timestamp + Nonce + Body, Secret)` | `a9f4c3...d8e` |
| `X-App-Version` | `String` | App version name | `1.0.0` |
| `X-Platform` | `String` | Mobile OS Platform | `android` or `ios` |
| `X-Device-Id` | `String` | Unique hardware identifier/app instance ID | `d3b07384d113edec` |
| `X-Play-Integrity`| `String` | *(Optional / High Security)* Play Integrity attestation token | `eyJraWQiOiIx...` |

---

## 2. Standard Response Wrapper

All API responses follow a consistent JSON envelope format:

### Success Response Format (`200 OK`, `201 Created`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Operation completed successfully",
  "data": {},
  "timestamp": "2026-08-26T08:56:00.000Z"
}
```

### Error Response Format (`400`, `401`, `403`, `404`, `429`, `500`)
```json
{
  "success": false,
  "statusCode": 401,
  "errorCode": "AUTH_TOKEN_EXPIRED",
  "message": "The provided session token has expired. Please refresh your token.",
  "errors": [],
  "timestamp": "2026-08-26T08:56:00.000Z"
}
```

---

## 3. Authentication & Security APIs

---

### 3.1 Member Login
Authenticates the student using Username/UID/Email and Password/Mobile number.

- **API Name:** Member Login
- **Method:** `POST`
- **Endpoint:** `/auth/login`
- **Auth Required:** No

#### Headers
```http
Content-Type: application/json
X-Timestamp: 1771982400000
X-Nonce: e1b80c32-a590-4c3e-8b90-95c557c50a01
X-Signature: <computed-hmac-sha256>
X-Platform: android
X-App-Version: 1.0.0
```

#### Request Body
```json
{
  "identifier": "LN000123",
  "password": "9876543210",
  "deviceInfo": {
    "deviceId": "d3b07384d113edec",
    "deviceModel": "Samsung Galaxy S23",
    "osVersion": "Android 14",
    "fcmToken": "fcm_token_string_here"
  }
}
```

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Login successful",
  "data": {
    "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "dGhpcy1pcy1hLXJlZnJlc2gtdG9rZW4...",
    "expiresIn": 900,
    "tokenType": "Bearer",
    "student": {
      "id": "std_982341",
      "uid": "LN000123",
      "name": "Pawan Kumar",
      "fullName": "PAWAN KUMAR RATHORE",
      "email": "pawan.rathore@example.com",
      "phone": "+919876543210",
      "profileImageUrl": "https://cdn.libraro.com/avatars/std_982341.jpg",
      "status": "ACTIVE",
      "library": {
        "id": "lib_001",
        "name": "Libraro Central Study Hub",
        "address": "Plot 42, Sector 62, Knowledge Park, Noida"
      }
    }
  }
}
```

---

### 3.2 Refresh Auth Token
Generates a new short-lived Access Token using a valid Refresh Token.

- **API Name:** Refresh Access Token
- **Method:** `POST`
- **Endpoint:** `/auth/refresh-token`
- **Auth Required:** No

#### Request Body
```json
{
  "refreshToken": "dGhpcy1pcy1hLXJlZnJlc2gtdG9rZW4...",
  "deviceId": "d3b07384d113edec"
}
```

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Token refreshed successfully",
  "data": {
    "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "dGhpcy1pcy1hLW5ldy1yZWZyZXNoLXRva2Vu...",
    "expiresIn": 900,
    "tokenType": "Bearer"
  }
}
```

---

### 3.3 Member Logout
Revokes the refresh token and terminates the active session.

- **API Name:** Member Logout
- **Method:** `POST`
- **Endpoint:** `/auth/logout`
- **Auth Required:** Yes (`Bearer <token>`)

#### Request Body
```json
{
  "deviceId": "d3b07384d113edec"
}
```

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Logged out successfully",
  "data": null
}
```

---

## 4. Home & Dashboard APIs

---

### 4.1 Get Home Screen Summary & ID Card Data
Fetches banners, student membership card details, and QR payload for the 3D flip card.

- **API Name:** Get Home Summary
- **Method:** `GET`
- **Endpoint:** `/student/home`
- **Auth Required:** Yes (`Bearer <token>`)

#### Headers
```http
Authorization: Bearer eyJhbGciOiJIUzI1Ni...
X-Timestamp: 1771982400000
X-Nonce: a2c3e4f5-1111-2222-3333-444455556666
X-Signature: <computed-hmac-sha256>
```

#### Request Parameters: None

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Home data fetched successfully",
  "data": {
    "student": {
      "uid": "LN000123",
      "firstName": "Pawan",
      "fullName": "PAWAN KUMAR RATHORE",
      "profileImageUrl": null,
      "status": "ACTIVE",
      "unreadNotificationCount": 2
    },
    "banners": [
      {
        "id": "ban_01",
        "title": "Smart Library System",
        "subtitle": "Track your study sessions effortlessly",
        "imageUrl": "https://cdn.libraro.com/banners/banner1.jpg",
        "actionUrl": "libraro://plans"
      },
      {
        "id": "ban_02",
        "title": "Extended Night Shifts",
        "subtitle": "Now open 24x7 with high speed WiFi",
        "imageUrl": "https://cdn.libraro.com/banners/banner2.jpg",
        "actionUrl": null
      }
    ],
    "idCard": {
      "uid": "LN000123",
      "status": "ACTIVE",
      "planName": "1 MONTH",
      "planType": "6 AM to 10 PM",
      "shiftTime": "06:00 AM to 10:00 PM",
      "planStartDate": "2026-02-26",
      "planExpiryDate": "2026-03-26",
      "daysLeft": 29,
      "pendingPayment": {
        "hasPending": true,
        "amount": 550,
        "dueDate": "2026-03-03",
        "formattedNotice": "Pending Payment 550 on 3 Mar 2026"
      },
      "qrPayload": "LIBRARO:UID:LN000123|NAME:PAWAN_KUMAR_RATHORE|PLAN:1_MONTH|EXP:1774483200"
    }
  }
}
```

---

### 4.2 Get Notifications
Fetches recent notifications and announcements.

- **API Name:** Get Notifications
- **Method:** `GET`
- **Endpoint:** `/student/notifications?page=1&limit=20`
- **Auth Required:** Yes (`Bearer <token>`)

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Notifications fetched",
  "data": {
    "totalUnread": 2,
    "notifications": [
      {
        "id": "notif_101",
        "title": "Attendance Recorded",
        "message": "You punched IN at 09:15 AM today via QR scan.",
        "type": "ATTENDANCE",
        "isRead": false,
        "createdAt": "2026-08-26T09:15:22.000Z"
      },
      {
        "id": "notif_102",
        "title": "Subscription Due Reminder",
        "message": "Your pending fee of 550 is due on 3 Mar 2026.",
        "type": "PAYMENT",
        "isRead": false,
        "createdAt": "2026-08-25T14:30:00.000Z"
      }
    ]
  }
}
```

---

## 5. Attendance & QR Scanner APIs

---

### 5.1 Punch In / Out (Attendance Verification)
Called when the student scans the library QR code or the library scanner scans the student's QR code.

- **API Name:** Submit Attendance Punch
- **Method:** `POST`
- **Endpoint:** `/attendance/punch`
- **Auth Required:** Yes (`Bearer <token>`)

#### Headers
```http
Authorization: Bearer eyJhbGciOiJIUzI1Ni...
X-Timestamp: 1771982400000
X-Nonce: b8a92f44-1234-5678-9abc-def012345678
X-Signature: <computed-hmac-sha256>
```

#### Request Body
```json
{
  "qrData": "LIBRARO:UID:LN000123|NAME:PAWAN_KUMAR_RATHORE|PLAN:1_MONTH",
  "method": "QR",
  "type": "AUTO",
  "deviceLocation": {
    "latitude": 28.6280,
    "longitude": 77.3649,
    "accuracyMeters": 8.5
  },
  "scannedAt": "2026-08-26T09:15:30.000Z"
}
```
> **Notes on `method` enum:** `"QR"`, `"SCAN"`, `"MANUAL"`  
> **Notes on `type` enum:** `"IN"`, `"OUT"`, `"AUTO"` (Backend computes whether this is Punch In or Out)

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Punch IN recorded successfully",
  "data": {
    "punchId": "pnch_872361",
    "punchType": "Punch IN",
    "method": "QR",
    "timestamp": "26 Aug, 09:15 AM",
    "student": {
      "uid": "LN000123",
      "fullName": "PAWAN KUMAR RATHORE"
    },
    "summary": {
      "todayStatus": "fullyPresent",
      "totalHoursToday": "0h 0m",
      "firstPunchIn": "09:15 AM",
      "lastPunchOut": null
    }
  }
}
```

---

### 5.2 Get Monthly Attendance Summary & Calendar
Returns monthly metrics (present days, absent days, half days, total hours) and the daily records grid.

- **API Name:** Get Monthly Attendance
- **Method:** `GET`
- **Endpoint:** `/attendance/monthly?year=2026&month=8`
- **Auth Required:** Yes (`Bearer <token>`)

#### Request Query Parameters
- `year` (int, required): e.g. `2026`
- `month` (int, required): `1` to `12`

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Monthly attendance retrieved",
  "data": {
    "month": "2026-08",
    "presentDays": 18,
    "absentDays": 2,
    "halfDays": 3,
    "totalHoursFormatted": "142h 30m",
    "totalRecords": 23,
    "calendarDays": [
      {
        "date": "2026-07-27",
        "status": "outsideMonth",
        "hours": 0.0,
        "isOutsideMonth": true
      },
      {
        "date": "2026-08-01",
        "status": "fullyPresent",
        "hours": 10.1,
        "punchIn": "08:30 AM",
        "punchOut": "06:36 PM",
        "timeSpendFormatted": "10h 6m",
        "isOutsideMonth": false
      },
      {
        "date": "2026-08-02",
        "status": "halfPresent",
        "hours": 4.5,
        "punchIn": "09:00 AM",
        "punchOut": "01:30 PM",
        "timeSpendFormatted": "4h 30m",
        "isOutsideMonth": false
      },
      {
        "date": "2026-08-03",
        "status": "absent",
        "hours": 0.0,
        "punchIn": null,
        "punchOut": null,
        "timeSpendFormatted": "0h",
        "isOutsideMonth": false
      },
      {
        "date": "2026-08-28",
        "status": "future",
        "hours": 0.0,
        "isOutsideMonth": false
      }
    ],
    "dailyRecords": [
      {
        "date": "2026-08-26",
        "status": "fullyPresent",
        "hours": 8.5,
        "punchIn": "09:00 AM",
        "punchOut": "05:30 PM",
        "timeSpendFormatted": "8h 30m"
      }
    ]
  }
}
```

---

### 5.3 Get Single Day Attendance Detail Timeline
Provides the detailed punch log history for a specific day.

- **API Name:** Get Day Attendance Summary
- **Method:** `GET`
- **Endpoint:** `/attendance/day-detail?date=2026-08-26`
- **Auth Required:** Yes (`Bearer <token>`)

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Day detail retrieved",
  "data": {
    "date": "2026-08-26",
    "status": "fullyPresent",
    "totalHours": 8.5,
    "timeSpendFormatted": "8h 30m",
    "firstPunchIn": "09:00 AM",
    "lastPunchOut": "05:30 PM",
    "punchLogs": [
      {
        "id": "log_01",
        "timestamp": "26 Aug, 09:00 AM",
        "type": "Punch IN",
        "method": "QR",
        "isHighlighted": false
      },
      {
        "id": "log_02",
        "timestamp": "26 Aug, 01:15 PM",
        "type": "Punch OUT",
        "method": "SCAN",
        "isHighlighted": false
      },
      {
        "id": "log_03",
        "timestamp": "26 Aug, 02:00 PM",
        "type": "Punch IN",
        "method": "SCAN",
        "isHighlighted": false
      },
      {
        "id": "log_04",
        "timestamp": "26 Aug, 05:30 PM",
        "type": "Punch OUT",
        "method": "QR",
        "isHighlighted": true
      }
    ]
  }
}
```

---

## 6. Profile & Subscription APIs

---

### 6.1 Get Student Profile & Active Plans
- **API Name:** Get Profile
- **Method:** `GET`
- **Endpoint:** `/student/profile`
- **Auth Required:** Yes (`Bearer <token>`)

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Profile fetched successfully",
  "data": {
    "student": {
      "id": "std_982341",
      "uid": "LN000123",
      "name": "Pawan Rathore",
      "fullName": "PAWAN KUMAR RATHORE",
      "email": "pawan.rathore@example.com",
      "phone": "+919876543210",
      "profileImageUrl": "https://cdn.libraro.com/avatars/std_982341.jpg",
      "status": "ACTIVE",
      "joinedDate": "2025-11-15"
    },
    "subscriptionPlans": [
      {
        "id": "sub_01",
        "title": "FULL DAY (Monthly)",
        "subtitle": "Enjoy your plan for the next 17 days",
        "type": "FULL_DAY",
        "shiftTime": "06:00 AM to 10:00 PM",
        "startDate": "2026-02-26",
        "expiryDate": "2026-03-26",
        "daysRemaining": 17,
        "price": 1200,
        "isCurrentActive": true
      },
      {
        "id": "sub_02",
        "title": "MORNING SHIFT (Monthly)",
        "subtitle": "Valid from 06:00 AM to 02:00 PM",
        "type": "MORNING",
        "shiftTime": "06:00 AM to 02:00 PM",
        "price": 700,
        "isCurrentActive": false
      },
      {
        "id": "sub_03",
        "title": "EVENING SHIFT (Monthly)",
        "subtitle": "Valid from 02:00 PM to 10:00 PM",
        "type": "EVENING",
        "shiftTime": "02:00 PM to 10:00 PM",
        "price": 700,
        "isCurrentActive": false
      }
    ],
    "socialLinks": {
      "instagram": "https://instagram.com/libraro_official",
      "facebook": "https://facebook.com/libraro",
      "youtube": "https://youtube.com/@libraro",
      "twitter": "https://x.com/libraro"
    }
  }
}
```

---

### 6.2 Upload / Update Profile Photo
- **API Name:** Upload Profile Photo
- **Method:** `POST`
- **Endpoint:** `/student/profile/avatar`
- **Auth Required:** Yes (`Bearer <token>`)
- **Content-Type:** `multipart/form-data`

#### Request Body
- `file`: Binary image file (JPEG / PNG, max 5 MB).

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Profile photo updated successfully",
  "data": {
    "profileImageUrl": "https://cdn.libraro.com/avatars/std_982341_1771982400.jpg"
  }
}
```

---

### 6.3 Remove Profile Photo
- **API Name:** Delete Profile Photo
- **Method:** `DELETE`
- **Endpoint:** `/student/profile/avatar`
- **Auth Required:** Yes (`Bearer <token>`)

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Profile photo removed successfully",
  "data": null
}
```

---

### 6.4 Delete Account Request
- **API Name:** Request Account Deletion
- **Method:** `POST`
- **Endpoint:** `/student/account/delete-request`
- **Auth Required:** Yes (`Bearer <token>`)

#### Request Body
```json
{
  "reason": "Course completed / leaving city",
  "confirm": true
}
```

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Account deletion request submitted. Processing will complete within 30 days.",
  "data": {
    "requestId": "del_req_9921",
    "scheduledPurgeDate": "2026-09-25T00:00:00.000Z"
  }
}
```

---

## 7. App Configuration & Security Attestation

---

### 7.1 Play Integrity / Device Attestation Verification
- **API Name:** Verify Device Integrity
- **Method:** `POST`
- **Endpoint:** `/security/verify-integrity`
- **Auth Required:** Yes (`Bearer <token>`)

#### Request Body
```json
{
  "integrityToken": "eyJhbGciOiJSUzI1NiIsImtpZCI6...",
  "packageName": "com.libraro.student",
  "nonce": "f47ac10b-58cc-4372-a567-0e02b2c3d479"
}
```

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Device integrity verified",
  "data": {
    "isLicensed": true,
    "meetsDeviceIntegrity": true,
    "securityTrustLevel": "HIGH"
  }
}
```

---

### 7.2 App Version & Remote Config
- **API Name:** Get App Config
- **Method:** `GET`
- **Endpoint:** `/app/config?platform=android&version=1.0.0`
- **Auth Required:** No

#### Response JSON (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Config fetched",
  "data": {
    "minSupportedVersion": "1.0.0",
    "latestVersion": "1.0.2",
    "forceUpdate": false,
    "updateUrl": "https://play.google.com/store/apps/details?id=com.libraro.student",
    "maintenanceMode": false,
    "maintenanceMessage": null,
    "securityPolicy": {
      "sslPinningRequired": true,
      "hmacRequired": true,
      "blockRootedDevices": true
    }
  }
}
```

---

## 8. Summary API Matrix

| Module | Method | Endpoint | Auth | Purpose |
| :--- | :---: | :--- | :---: | :--- |
| **Auth** | `POST` | `/auth/login` | ❌ | Login with UID/Email & Password |
| **Auth** | `POST` | `/auth/refresh-token` | ❌ | Refresh expired Access Token |
| **Auth** | `POST` | `/auth/logout` | ✔️ | Terminate active session |
| **Home** | `GET` | `/student/home` | ✔️ | Home screen data, ID Card & QR |
| **Home** | `GET` | `/student/notifications` | ✔️ | Notification list & unread count |
| **Attendance**| `POST` | `/attendance/punch` | ✔️ | Record Punch IN / Punch OUT |
| **Attendance**| `GET` | `/attendance/monthly` | ✔️ | Monthly attendance summary & calendar |
| **Attendance**| `GET` | `/attendance/day-detail` | ✔️ | Single day punch log timeline |
| **Profile** | `GET` | `/student/profile` | ✔️ | Student profile & subscription plans |
| **Profile** | `POST` | `/student/profile/avatar` | ✔️ | Upload / update profile picture |
| **Profile** | `DELETE`| `/student/profile/avatar` | ✔️ | Delete profile photo |
| **Profile** | `POST` | `/student/account/delete-request` | ✔️ | Request GDPR/compliance deletion |
| **Security**| `POST` | `/security/verify-integrity` | ✔️ | Google Play Integrity token exchange |
| **Config** | `GET` | `/app/config` | ❌ | App version, force update, remote config |
