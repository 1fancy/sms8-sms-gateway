<?php
/**
 * wait_for_otp — block until an OTP-shaped SMS lands on the user's paired
 * Android, or until the timeout elapses. Closes the agent automation gap
 * AgentSIM addresses (autonomous signup flows that need to receive a code)
 * without renting disposable numbers: the user's own SIM does the work.
 *
 * Strategy:
 *   1. Capture the current max(Message.ID) for this user as a watermark.
 *   2. Poll Message every 2 seconds for new rows where status='Received'
 *      and an OTP-like substring (4-8 contiguous digits) appears in the
 *      body. Optionally filter to a sender_phone if the caller pre-knows
 *      who is sending. Optionally filter to a recipient device/SIM.
 *   3. Stop on first match or when (start + timeout) is reached.
 *   4. Extract the code via regex; return it along with the full message.
 *
 * Polling is acceptable here because:
 *   - This is an MCP tool, called sparingly by an AI agent.
 *   - The user's paired phone pushes SMS to our server in real time, so
 *     by the time the second poll fires the row is usually present.
 *   - We bound max poll work via 5 second cap on poll interval and a hard
 *     timeout ceiling of 180 seconds.
 */
ToolRegistry::register([
    'name' => 'wait_for_otp',
    'description' => 'Block until a one-time-password SMS arrives on the user\'s paired Android, or the timeout elapses. Returns the extracted numeric code plus the raw SMS body and sender. Use this when an agent has just submitted a form that triggers an OTP and needs to read it back to complete a signup or 2FA flow. Polls the user\'s real inbox; no disposable numbers, no third-party VoIP rental.',
    'inputSchema' => [
        'type' => 'object',
        'properties' => [
            'sender_phone' => [
                'type' => 'string',
                'description' => 'Optional sender phone in E.164 to filter on (e.g. "+44778..."). If omitted, matches any incoming SMS that contains a 4-8 digit code.',
            ],
            'device_id' => [
                'type' => 'integer',
                'description' => 'Optional device ID to restrict the wait to a specific paired Android.',
            ],
            'sim_slot' => [
                'type' => 'integer',
                'description' => 'Optional SIM slot on the chosen device (0 or 1).',
            ],
            'timeout_seconds' => [
                'type' => 'integer',
                'description' => 'How long to wait. 5-180 seconds. Default 60.',
            ],
            'code_min_length' => [
                'type' => 'integer',
                'description' => 'Minimum digit count for the code to extract. Default 4.',
            ],
            'code_max_length' => [
                'type' => 'integer',
                'description' => 'Maximum digit count for the code to extract. Default 8.',
            ],
            'contains' => [
                'type' => 'string',
                'description' => 'Optional substring the message body must contain (case-insensitive). Useful when multiple OTP flows might be active at once. Example: "Acme" matches messages from your Acme test.',
            ],
        ],
    ],
    'handler' => function(array $args, User $user): array {
        $userID  = (int) $user->getID();
        $sender  = isset($args['sender_phone']) ? cleanPhoneNumber((string)$args['sender_phone']) : '';
        $devID   = isset($args['device_id']) ? (int)$args['device_id'] : 0;
        $simSlot = array_key_exists('sim_slot', $args) ? (int)$args['sim_slot'] : -1;
        $timeout = max(5, min(180, (int)($args['timeout_seconds'] ?? 60)));
        $minLen  = max(3, min(10, (int)($args['code_min_length'] ?? 4)));
        $maxLen  = max($minLen, min(12, (int)($args['code_max_length'] ?? 8)));
        $contains = isset($args['contains']) ? (string)$args['contains'] : '';
        $containsLower = $contains !== '' ? mb_strtolower($contains) : '';

        $db = MysqliDb::getInstance();

        // 1. Watermark: ignore anything that was already in the inbox before we started
        $row = $db->rawQueryOne(
            "SELECT MAX(ID) AS maxID FROM Message WHERE userID = ? AND status = 'Received'",
            [$userID]
        );
        $watermarkID = (int)($row['maxID'] ?? 0);

        $start    = microtime(true);
        $deadline = $start + $timeout;
        $regex    = '/(\b\d{' . $minLen . ',' . $maxLen . '}\b)/u';
        $attempts = 0;

        while (microtime(true) < $deadline) {
            $attempts++;

            $sql = "SELECT ID, number, message, deviceID, simSlot, sentDate, deliveredDate
                    FROM Message
                    WHERE userID = ? AND status = 'Received' AND ID > ?";
            $params = [$userID, $watermarkID];
            if ($sender !== '') {
                $sql .= " AND number = ?";
                $params[] = $sender;
            }
            if ($devID > 0) {
                $sql .= " AND deviceID = ?";
                $params[] = $devID;
            }
            if ($simSlot >= 0) {
                $sql .= " AND simSlot = ?";
                $params[] = $simSlot;
            }
            $sql .= " ORDER BY ID ASC LIMIT 10";

            $db->rawQuery("SET NAMES latin1");
            $rows = $db->rawQuery($sql, $params) ?: [];
            $db->rawQuery("SET NAMES utf8mb4");

            foreach ($rows as $msg) {
                $body = (string)($msg['message'] ?? '');
                if ($containsLower !== '' && mb_strpos(mb_strtolower($body), $containsLower) === false) {
                    continue;
                }
                if (preg_match($regex, $body, $m)) {
                    return [
                        'success'         => true,
                        'verified'        => false, // we just READ the code; verification is a separate step
                        'code'            => $m[1],
                        'sender'          => $msg['number'],
                        'message'         => $body,
                        'received_at'     => $msg['deliveredDate'] ?? $msg['sentDate'],
                        'device_id'       => (int)$msg['deviceID'],
                        'sim_slot'        => (int)$msg['simSlot'],
                        'elapsed_seconds' => round(microtime(true) - $start, 2),
                        'polls'           => $attempts,
                    ];
                }
            }

            // Sleep 2 seconds between polls, but cut short if the deadline is closer.
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) break;
            usleep((int)(min(2.0, $remaining) * 1_000_000));
        }

        return [
            'success'         => false,
            'reason'          => 'timeout',
            'message'         => 'No OTP-shaped SMS arrived within ' . $timeout . ' seconds. The agent\'s downstream service may not have sent yet, or the SIM may be offline. Check list_devices.',
            'elapsed_seconds' => round(microtime(true) - $start, 2),
            'polls'           => $attempts,
        ];
    },
]);
