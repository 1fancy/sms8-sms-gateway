<?php
/**
 * send_sms — send a single SMS through the user's paired Android device(s).
 *
 * Wraps Message::sendMessages() so credits, validation, multi-device routing,
 * and webhooks behave identically to a direct API call.
 *
 * Routing options (match the dashboard's sender.php exactly):
 *   - device_id     → single device (int)
 *   - sim_slot      → optional SIM index on the chosen device (e.g. "1", "2")
 *   - devices       → array of device IDs or "deviceID|simSlot" entries
 *   - option=1      → broadcast across all enabled devices
 *   - option=2      → broadcast across all enabled SIMs across all devices
 *   - random_device → pick one random sender from the resolved list
 */
ToolRegistry::register([
    'name' => 'send_sms',
    'description' => 'Send an SMS to one phone number through the user\'s paired SMS8 device(s). Returns the message ID. Supports per-device + per-SIM routing identical to the SMS8 dashboard.',
    'inputSchema' => [
        'type' => 'object',
        'required' => ['phone', 'message'],
        'properties' => [
            'phone'        => ['type' => 'string',  'description' => 'E.164 phone number, e.g. +1234567890'],
            'message'      => ['type' => 'string',  'description' => 'SMS body (max ~1600 chars).'],
            'device_id'    => ['type' => 'integer', 'description' => 'Optional. Specific paired device. Default: primary, falling back to first enabled.'],
            'sim_slot'     => ['type' => 'string',  'description' => 'Optional. SIM slot ID on the chosen device (multi-SIM phones). Combined as "<device_id>|<sim_slot>".'],
            'devices'      => ['type' => 'array',   'description' => 'Optional. Explicit list of device IDs or "<deviceID>|<simSlot>" entries. Overrides device_id.', 'items' => ['type' => 'string']],
            'option'       => ['type' => 'integer', 'description' => 'Optional routing mode. 0=use device_id/devices (default), 1=broadcast all devices, 2=broadcast all SIMs.', 'enum' => [0, 1, 2]],
            'random_device'=> ['type' => 'boolean', 'description' => 'Optional. If true, pick one random sender from the resolved list (load-balancing).'],
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

        $resolved = _sms8_resolve_senders($user, $args);
        if (empty($resolved)) {
            return [
                'error'    => 'No active device on this account — pair an Android first.',
                'pair_url' => 'https://app.sms8.io/devices.php',
                'setup'    => 'https://app.sms8.io/mcp-setup.php',
            ];
        }

        $sentIds = Message::sendMessages([[
            'number'      => $phone,
            'message'     => $message,
            'attachments' => null,
            'type'        => 'sms',
        ]], $user, $resolved);

        $msgId = is_array($sentIds) && !empty($sentIds) ? (int)reset($sentIds) : null;
        return [
            'success'    => true,
            'message_id' => $msgId,
            'phone'      => $phone,
            'senders'    => $resolved,
            'status_url' => $msgId ? "https://app.sms8.io/messages.php?id=$msgId" : null,
        ];
    },
]);

/**
 * Resolve which sender(s) to use from MCP tool args. Returns an array of
 * "<deviceID>" or "<deviceID>|<simSlot>" entries, matching the format
 * Message::sendMessages expects (same as services/send.php).
 */
function _sms8_resolve_senders(User $user, array $args): array
{
    $option       = isset($args['option']) ? (int)$args['option'] : 0;
    $explicitList = $args['devices'] ?? null;
    $deviceID     = isset($args['device_id']) ? (int)$args['device_id'] : 0;
    $simSlot      = isset($args['sim_slot']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string)$args['sim_slot']) : '';
    $random       = !empty($args['random_device']);

    $resolved = [];

    if ($option === 1) {
        foreach ($user->getDevices() as $d) {
            if ($d->getEnabled()) $resolved[] = (string)$d->getID();
        }
    } elseif ($option === 2) {
        $sims = $user->getSims();
        foreach ($user->getDevices() as $d) {
            if (!$d->getEnabled()) continue;
            if (isset($sims[$d->getID()]) && count($sims[$d->getID()]) > 0) {
                foreach (array_keys($sims[$d->getID()]) as $slot) {
                    $resolved[] = $d->getID() . '|' . $slot;
                }
            } else {
                $resolved[] = (string)$d->getID();
            }
        }
    } elseif (is_array($explicitList) && !empty($explicitList)) {
        $owned = [];
        foreach ($user->getDevices() as $d) $owned[(int)$d->getID()] = $d;
        foreach ($explicitList as $entry) {
            $parts = explode('|', (string)$entry, 2);
            $did   = (int)$parts[0];
            if (isset($owned[$did]) && $owned[$did]->getEnabled()) {
                $resolved[] = isset($parts[1])
                    ? ($did . '|' . preg_replace('/[^A-Za-z0-9_-]/', '', $parts[1]))
                    : (string)$did;
            }
        }
    } elseif ($deviceID > 0) {
        foreach ($user->getDevices() as $d) {
            if ($d->getID() === $deviceID && $d->getEnabled()) {
                $resolved[] = $simSlot !== '' ? ($deviceID . '|' . $simSlot) : (string)$deviceID;
                break;
            }
        }
    }

    if (empty($resolved)) {
        $primaryID = $user->getPrimaryDeviceID();
        if ($primaryID) {
            foreach ($user->getDevices() as $d) {
                if ($d->getID() == $primaryID && $d->getEnabled()) { $resolved[] = (string)$d->getID(); break; }
            }
        }
        if (empty($resolved)) {
            foreach ($user->getDevices() as $d) {
                if ($d->getEnabled()) { $resolved[] = (string)$d->getID(); break; }
            }
        }
    }

    if ($random && count($resolved) > 1) {
        $resolved = [$resolved[array_rand($resolved)]];
    }

    return $resolved;
}
