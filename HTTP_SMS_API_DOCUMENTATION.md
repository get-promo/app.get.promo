# httpSMS API Server - Dokumentacja

## Przegląd

Zaimplementowano własny serwer httpSMS API zgodny z oryginalną specyfikacją [httpSMS](https://httpsms.com/). Serwer pozwala aplikacji Android na łączenie się z `app.get.promo/httpSMS` zamiast z `httpsms.com`.

## URL Serwera

- **Base URL**: `https://app.get.promo/httpSMS/v1`
- **Test endpoint**: `https://app.get.promo/httpSMS/test`

## Autentykacja

Wszystkie endpointy (oprócz rejestracji urządzenia) wymagają autentykacji przez API key:

- **Header**: `X-API-Key: your_api_key`
- **Query parameter**: `?api_key=your_api_key`

## Endpointy API

### 1. Rejestracja urządzenia

**POST** `/httpSMS/v1/devices/register`

Rejestruje nowe urządzenie Android w systemie.

**Request Body:**
```json
{
    "phone_number": "+48123456789",
    "device_name": "Samsung Galaxy S21",
    "device_info": {
        "model": "SM-G991B",
        "android_version": "12",
        "manufacturer": "Samsung"
    }
}
```

**Response:**
```json
{
    "device_id": "dev_abc123...",
    "api_key": "sms_xyz789...",
    "message": "Device registered successfully"
}
```

### 2. Wysyłanie SMS

**POST** `/httpSMS/v1/messages/send`

Wysyła wiadomość SMS przez urządzenie Android.

**Headers:**
```
X-API-Key: your_api_key
Content-Type: application/json
```

**Request Body:**
```json
{
    "content": "Treść wiadomości SMS",
    "from": "+48123456789",
    "to": "+48987654321"
}
```

**Response:**
```json
{
    "id": "msg_abc123...",
    "content": "Treść wiadomości SMS",
    "from": "+48123456789",
    "to": "+48987654321",
    "status": "pending",
    "created_at": "2025-01-15T10:30:00.000Z"
}
```

### 3. Lista wiadomości

**GET** `/httpSMS/v1/messages`

Pobiera listę wiadomości dla urządzenia.

**Headers:**
```
X-API-Key: your_api_key
```

**Query Parameters:**
- `status` (opcjonalne): `pending`, `sent`, `delivered`, `failed`
- `type` (opcjonalne): `sent`, `received`
- `limit` (opcjonalne): maksymalnie 100, domyślnie 50

**Response:**
```json
{
    "data": [
        {
            "id": "msg_abc123...",
            "content": "Treść wiadomości",
            "from": "+48123456789",
            "to": "+48987654321",
            "type": "sent",
            "status": "delivered",
            "created_at": "2025-01-15T10:30:00.000Z",
            "sent_at": "2025-01-15T10:30:05.000Z",
            "delivered_at": "2025-01-15T10:30:10.000Z"
        }
    ]
}
```

### 4. Szczegóły wiadomości

**GET** `/httpSMS/v1/messages/{messageId}`

Pobiera szczegóły konkretnej wiadomości.

**Headers:**
```
X-API-Key: your_api_key
```

**Response:**
```json
{
    "id": "msg_abc123...",
    "content": "Treść wiadomości",
    "from": "+48123456789",
    "to": "+48987654321",
    "type": "sent",
    "status": "delivered",
    "created_at": "2025-01-15T10:30:00.000Z",
    "sent_at": "2025-01-15T10:30:05.000Z",
    "delivered_at": "2025-01-15T10:30:10.000Z",
    "metadata": {
        "delivery_report": "success",
        "error_code": null
    }
}
```

### 5. Odbieranie SMS

**POST** `/httpSMS/v1/messages/receive`

Endpoint wywoływany przez aplikację Android gdy odbierze SMS.

**Headers:**
```
X-API-Key: your_api_key
Content-Type: application/json
```

**Request Body:**
```json
{
    "content": "Otrzymana wiadomość SMS",
    "from": "+48987654321",
    "to": "+48123456789",
    "received_at": "2025-01-15T10:30:00.000Z"
}
```

**Response:**
```json
{
    "id": "msg_xyz789...",
    "status": "received"
}
```

### 6. Aktualizacja statusu wiadomości

**POST** `/httpSMS/v1/messages/{messageId}/status`

Endpoint wywoływany przez aplikację Android do aktualizacji statusu wysłanej wiadomości.

**Headers:**
```
X-API-Key: your_api_key
Content-Type: application/json
```

**Request Body:**
```json
{
    "status": "sent",
    "metadata": {
        "delivery_report": "success",
        "error_code": null
    }
}
```

**Response:**
```json
{
    "id": "msg_abc123...",
    "status": "sent"
}
```

### 7. Status urządzenia

**GET** `/httpSMS/v1/devices/status`

Pobiera status urządzenia.

**Headers:**
```
X-API-Key: your_api_key
```

**Response:**
```json
{
    "device_id": "dev_abc123...",
    "phone_number": "+48123456789",
    "device_name": "Samsung Galaxy S21",
    "is_active": true,
    "last_seen_at": "2025-01-15T10:30:00.000Z",
    "device_info": {
        "model": "SM-G991B",
        "android_version": "12",
        "manufacturer": "Samsung"
    }
}
```

### 8. Heartbeat

**POST** `/httpSMS/v1/devices/heartbeat`

Endpoint wywoływany przez aplikację Android do potwierdzenia, że urządzenie jest aktywne.

**Headers:**
```
X-API-Key: your_api_key
```

**Response:**
```json
{
    "status": "ok",
    "timestamp": "2025-01-15T10:30:00.000Z"
}
```

## Konfiguracja aplikacji Android

W aplikacji httpSMS na Androidzie:

1. Otwórz ustawienia aplikacji
2. Znajdź opcję "Custom Server URL" lub "Server Configuration"
3. Wpisz: `https://app.get.promo/httpSMS/v1`
4. Zarejestruj urządzenie używając endpointu `/devices/register`
5. Użyj otrzymanego `api_key` do autentykacji

## Statusy wiadomości

- `pending` - Wiadomość oczekuje na wysłanie
- `sent` - Wiadomość została wysłana
- `delivered` - Wiadomość została dostarczona
- `failed` - Wysłanie wiadomości nie powiodło się

## Typy wiadomości

- `sent` - Wiadomość wysłana przez urządzenie
- `received` - Wiadomość otrzymana przez urządzenie

## Błędy

API zwraca standardowe kody HTTP:

- `200` - Sukces
- `201` - Utworzono
- `202` - Zaakceptowano (wiadomość w kolejce)
- `400` - Błędne żądanie
- `401` - Brak autoryzacji (nieprawidłowy API key)
- `404` - Nie znaleziono
- `500` - Błąd serwera

Przykład błędu:
```json
{
    "error": "API key required"
}
```

## Testowanie

Możesz przetestować API używając curl:

```bash
# Test endpoint
curl -X GET "https://app.get.promo/httpSMS/test"

# Rejestracja urządzenia
curl -X POST "https://app.get.promo/httpSMS/v1/devices/register" \
  -H "Content-Type: application/json" \
  -d '{"phone_number": "+48123456789", "device_name": "Test Device"}'

# Wysyłanie SMS (wymaga API key)
curl -X POST "https://app.get.promo/httpSMS/v1/messages/send" \
  -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"content": "Test message", "from": "+48123456789", "to": "+48987654321"}'
```
