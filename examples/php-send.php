<?php
/**
 * SMS8 — minimal PHP sender service.
 *
 * Drop-in class for any PHP project. No SDK, no dependencies — just curl.
 * Pair with .env for credentials; never commit your key.
 *
 *   $sms = new Sms8Sender(getenv('SMS8_API_KEY'));
 *   $sms->send('+1234567890', 'Hello from SMS8!');
 */
final class Sms8Sender
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $apiKey, string $baseUrl = 'https://app.sms8.io')
    {
        if ($apiKey === '') throw new InvalidArgumentException('SMS8 API key required');
        $this->apiKey  = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /** Send a single SMS. Returns the new message ID. */
    public function send(string $phone, string $message, ?int $deviceId = null): int
    {
        $payload = [
            'api_key' => $this->apiKey,
            'phone'   => $phone,
            'message' => $message,
        ];
        if ($deviceId) $payload['device_id'] = $deviceId;

        $res = $this->post('/api.php?action=send', $payload);
        if (empty($res['success'])) {
            throw new RuntimeException('SMS8 send failed: ' . ($res['error'] ?? 'unknown error'));
        }
        return (int)($res['message_id'] ?? 0);
    }

    /** Send a one-time verification code. Verify later with verifyOtp(). */
    public function sendOtp(string $phone, int $length = 6, int $expiresIn = 300): array
    {
        return $this->post('/ajax/otp-send.php', [
            'api_key'    => $this->apiKey,
            'phone'      => $phone,
            'length'     => $length,
            'expires_in' => $expiresIn,
        ]);
    }

    /** Check a user-typed code against the most recent OTP for this phone. */
    public function verifyOtp(string $phone, string $code): bool
    {
        $r = $this->post('/ajax/otp-verify.php', [
            'api_key' => $this->apiKey,
            'phone'   => $phone,
            'code'    => $code,
        ]);
        return !empty($r['verified']);
    }

    private function post(string $path, array $form): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($form),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode((string)$body, true);
        if (!is_array($json)) {
            throw new RuntimeException("SMS8 returned non-JSON (HTTP $http)");
        }
        return $json;
    }
}
