<?php

namespace App\Http\Controllers;

use App\Models\SmsDevice;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HttpSmsController extends Controller
{
    /**
     * Walidacja API key (wywoływana w każdej metodzie oprócz registerDevice)
     * Obsługuje zarówno x-api-key (oryginalny format) jak i X-API-Key
     */
    private function authenticateDevice(Request $request)
    {
        $apiKey = $request->header('x-api-key') 
               ?? $request->header('X-API-Key') 
               ?? $request->input('api_key');
        
        if (!$apiKey) {
            return response()->json([
                'data' => null,
                'message' => 'API key required',
                'status' => 'error'
            ], 401);
        }
        
        $device = SmsDevice::where('api_key', $apiKey)->active()->first();
        
        if (!$device) {
            return response()->json([
                'data' => null,
                'message' => 'Invalid API key',
                'status' => 'error'
            ], 401);
        }
        
        // Aktualizuj czas ostatniego kontaktu
        $device->updateLastSeen();
        
        return $device;
    }

    /**
     * POST /httpSMS/v1/messages/send
     * Wysyłanie wiadomości SMS
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;
        
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1600',
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }
        
        // Utwórz wiadomość w bazie danych
        $message = SmsMessage::create([
            'message_id' => SmsMessage::generateMessageId(),
            'device_id' => $device->device_id,
            'from_number' => $request->input('from'),
            'to_number' => $request->input('to'),
            'content' => $request->input('content'),
            'type' => 'sent',
            'status' => 'pending',
        ]);

        // Zwróć odpowiedź zgodną z API httpSMS
        return response()->json([
            'id' => $message->message_id,
            'content' => $message->content,
            'from' => $message->from_number,
            'to' => $message->to_number,
            'status' => $message->status,
            'created_at' => $message->created_at->toISOString(),
        ], 202); // 202 Accepted - wiadomość została zaakceptowana do wysłania
    }

    /**
     * GET /httpSMS/v1/messages
     * Pobieranie listy wiadomości
     */
    public function getMessages(Request $request): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;
        
        $query = SmsMessage::where('device_id', $device->device_id);
        
        // Filtry opcjonalne
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        
        if ($request->has('limit')) {
            $limit = min($request->input('limit', 50), 100); // Max 100
            $query->limit($limit);
        }
        
        $messages = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'data' => $messages->map(function ($message) {
                return [
                    'id' => $message->message_id,
                    'content' => $message->content,
                    'from' => $message->from_number,
                    'to' => $message->to_number,
                    'type' => $message->type,
                    'status' => $message->status,
                    'created_at' => $message->created_at->toISOString(),
                    'sent_at' => $message->sent_at?->toISOString(),
                    'delivered_at' => $message->delivered_at?->toISOString(),
                ];
            })
        ]);
    }

    /**
     * GET /httpSMS/v1/messages/{messageId}
     * Pobieranie konkretnej wiadomości
     */
    public function getMessage(Request $request, string $messageId): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;
        
        $message = SmsMessage::where('device_id', $device->device_id)
            ->where('message_id', $messageId)
            ->first();
        
        if (!$message) {
            return response()->json(['error' => 'Message not found'], 404);
        }
        
        return response()->json([
            'id' => $message->message_id,
            'content' => $message->content,
            'from' => $message->from_number,
            'to' => $message->to_number,
            'type' => $message->type,
            'status' => $message->status,
            'created_at' => $message->created_at->toISOString(),
            'sent_at' => $message->sent_at?->toISOString(),
            'delivered_at' => $message->delivered_at?->toISOString(),
            'metadata' => $message->metadata,
        ]);
    }

    /**
     * POST /httpSMS/v1/messages/receive
     * Odbieranie wiadomości SMS (wywoływane przez aplikację Android)
     */
    public function receiveMessage(Request $request): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;
        
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'from' => 'required|string',
            'to' => 'required|string',
            'received_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }
        
        // Utwórz wiadomość otrzymaną
        $message = SmsMessage::create([
            'message_id' => SmsMessage::generateMessageId(),
            'device_id' => $device->device_id,
            'from_number' => $request->input('from'),
            'to_number' => $request->input('to'),
            'content' => $request->input('content'),
            'type' => 'received',
            'status' => 'delivered',
            'delivered_at' => $request->input('received_at'),
        ]);

        return response()->json([
            'id' => $message->message_id,
            'status' => 'received',
        ], 201);
    }

    /**
     * POST /httpSMS/v1/messages/{messageId}/status
     * Aktualizacja statusu wiadomości (wywoływane przez aplikację Android)
     */
    public function updateMessageStatus(Request $request, string $messageId): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,sent,delivered,failed',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }
        
        $message = SmsMessage::where('device_id', $device->device_id)
            ->where('message_id', $messageId)
            ->first();
        
        if (!$message) {
            return response()->json(['error' => 'Message not found'], 404);
        }
        
        $updateData = ['status' => $request->input('status')];
        
        // Aktualizuj odpowiednie timestampy
        if ($request->input('status') === 'sent') {
            $updateData['sent_at'] = now();
        } elseif ($request->input('status') === 'delivered') {
            $updateData['delivered_at'] = now();
        }
        
        if ($request->has('metadata')) {
            $updateData['metadata'] = $request->input('metadata');
        }
        
        $message->update($updateData);
        
        return response()->json([
            'id' => $message->message_id,
            'status' => $message->status,
        ]);
    }

    /**
     * POST /httpSMS/v1/devices/register
     * Rejestracja nowego urządzenia Android
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'device_name' => 'sometimes|string',
            'device_info' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        // Sprawdź czy urządzenie już istnieje
        $existingDevice = SmsDevice::where('phone_number', $request->input('phone_number'))->first();
        
        if ($existingDevice) {
            return response()->json([
                'device_id' => $existingDevice->device_id,
                'api_key' => $existingDevice->api_key,
                'phone_number' => $existingDevice->phone_number,
                'message' => 'Device already registered'
            ]);
        }

        // Utwórz nowe urządzenie
        $device = SmsDevice::create([
            'device_id' => SmsDevice::generateDeviceId(),
            'api_key' => SmsDevice::generateApiKey(),
            'phone_number' => $request->input('phone_number'),
            'device_name' => $request->input('device_name'),
            'device_info' => $request->input('device_info'),
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'device_id' => $device->device_id,
            'api_key' => $device->api_key,
            'phone_number' => $device->phone_number,
            'message' => 'Device registered successfully'
        ], 201);
    }
    
    /**
     * POST /httpSMS/v1/users/login
     * Alternatywny endpoint logowania (dla kompatybilności z aplikacją)
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'status' => 'error'
            ], 400);
        }

        // Znajdź urządzenie po numerze telefonu
        $device = SmsDevice::where('phone_number', $request->input('phone_number'))->active()->first();
        
        if (!$device) {
            return response()->json([
                'data' => null,
                'message' => 'Device not found. Please register first.',
                'status' => 'error'
            ], 404);
        }

        // Aktualizuj ostatni kontakt
        $device->updateLastSeen();

        return response()->json([
            'data' => [
                'id' => $device->device_id,
                'email' => 'user@get.promo',
                'api_key' => $device->api_key,
                'timezone' => 'Europe/Warsaw',
                'active_phone_id' => $device->device_id,
                'created_at' => $device->created_at->toISOString(),
                'updated_at' => $device->updated_at->toISOString(),
            ],
            'message' => 'user fetched successfully',
            'status' => 'success'
        ]);
    }
    
    /**
     * GET /httpSMS/v1/users/me
     * Informacje o zalogowanym użytkowniku (kompatybilność z httpSMS)
     */
    public function me(Request $request): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;

        return response()->json([
            'data' => [
                'id' => $device->device_id,
                'email' => 'user@get.promo',
                'api_key' => $device->api_key,
                'timezone' => 'Europe/Warsaw',
                'active_phone_id' => $device->device_id,
                'created_at' => $device->created_at->toISOString(),
                'updated_at' => $device->updated_at->toISOString(),
            ],
            'message' => 'user fetched successfully',
            'status' => 'success'
        ]);
    }
    
    /**
     * GET /httpSMS/v1/phones
     * Lista telefonów (kompatybilność z httpSMS)
     */
    public function phones(Request $request): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;

        return response()->json([
            'data' => [[
                'id' => $device->device_id,
                'user_id' => $device->device_id,
                'fcm_token' => 'dummy_fcm_token',
                'phone_number' => $device->phone_number,
                'messages_per_minute' => 10,
                'sim' => 'SIM1',
                'max_send_attempts' => 2,
                'message_expiration_seconds' => 600,
                'missed_call_auto_reply' => null,
                'created_at' => $device->created_at->toISOString(),
                'updated_at' => $device->updated_at->toISOString(),
            ]],
            'message' => 'fetched 1 phone',
            'status' => 'success'
        ]);
    }

    /**
     * GET /httpSMS/v1/devices/status
     * Status urządzenia
     */
    public function getDeviceStatus(Request $request): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;
        
        return response()->json([
            'device_id' => $device->device_id,
            'phone_number' => $device->phone_number,
            'device_name' => $device->device_name,
            'is_active' => $device->is_active,
            'last_seen_at' => $device->last_seen_at?->toISOString(),
            'device_info' => $device->device_info,
        ]);
    }

    /**
     * POST /httpSMS/v1/devices/heartbeat
     * Heartbeat od urządzenia Android
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $device = $this->authenticateDevice($request);
        if ($device instanceof JsonResponse) return $device;
        $device->updateLastSeen();
        
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
