<?php
/**
 * send_sms — send a single SMS through the user's paired Android device.
 *
 * Wraps the existing Message::sendMessages() path so credits, validation,
 * device routing, and webhooks all behave identically to a regular API call.
 */
ToolRegistry::register([
    'name' => 'send_sms',
    'description' => 'Send an SMS to one phone number through the user\'s paired SMS8 device. Returns the message ID. Re-uses the existing SMS8 send pipeline (credits, retries, delivery webhook, …).',
    'inputSchema' => [
        'type' => 'object',
        'required' => ['phone', 'message'],
        'properties' => [
            'phone'     => ['type' => 'string', 'description' => 'E.164 phone number, e.g. +1234567890'],
            'message'   => ['type' => 'string', 'description' => 'SMS body (max ~1600 chars).'],
            'device_id' => ['type' => 'integer', 'description' => 'Optional — pick a specific paired device. Default: user\'s primary device.'],
        ],
    ],
    'handler' => function(array $args, User $user): array {
        $phone   = cleanPhoneNumber($args['phone'] ?? '');
        $message = trim($args['message'] ?? '');
        if (!isValidMobileNumber($phone, false)) {
            return ['error' => 'Invalid phone number — use E.164 like +1234567890'];
        }
        if ($message === '') {
            return ['error' => 'message is required'];
        }

        // Resolve device — explicit, then primary, then first enabled
        $deviceId = (int)($args['device_id'] ?? 0);
        $device = null;
        foreach ($user->getDevices() as $d) {
            if (!$d->getEnabled()) continue;
            if ($deviceId && $d->getID() == $deviceId) { $device = $d; break; }
            if (!$deviceId && $d->getID() == $user->getPrimaryDeviceID()) { $device = $d; break; }
        }
        if (!$device) {
            foreach ($user->getDevices() as $d) {
                if ($d->getEnabled()) { $device = $d; break; }
            }
        }
        if (!$device) {
            return ['error' => 'No active device on this account — pair an Android first at https://app.sms8.io/devices.php'];
        }

        $sentIds = Message::sendMessages([[
            'number'      => $phone,
            'message'     => $message,
            'attachments' => null,
            'type'        => 'sms',
        ]], $user, [$device->getID()]);

        $msgId = is_array($sentIds) && !empty($sentIds) ? (int)reset($sentIds) : null;
        return [
            'success'    => true,
            'message_id' => $msgId,
            'phone'      => $phone,
            'device_id'  => $device->getID(),
            'status_url' => "https://app.sms8.io/messages.php?id=$msgId",
        ];
    },
]);
