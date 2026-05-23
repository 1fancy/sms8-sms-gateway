<?php
/**
 * get_messages — recent inbox / sent items for the authenticated user.
 *
 * Lightweight reflection of the existing Message read paths so the AI can
 * show the user "here are your last N messages" without a separate tool.
 */
ToolRegistry::register([
    'name' => 'get_messages',
    'description' => 'Fetch recent SMS messages on the user\'s account. Filter by direction (received|sent|all) and limit. Useful for showing inbox state, debugging delivery, or building reply flows.',
    'inputSchema' => [
        'type' => 'object',
        'properties' => [
            'direction' => [
                'type' => 'string',
                'enum' => ['all', 'received', 'sent'],
                'description' => 'Filter direction. Default "all".',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Max rows (1-100). Default 25.',
            ],
            'phone' => [
                'type' => 'string',
                'description' => 'Optional — only messages to/from this phone (E.164).',
            ],
        ],
    ],
    'handler' => function(array $args, User $user): array {
        $direction = in_array(($args['direction'] ?? 'all'), ['all','received','sent'], true)
            ? ($args['direction'] ?? 'all')
            : 'all';
        $limit     = max(1, min(100, (int)($args['limit'] ?? 25)));
        $phone     = isset($args['phone']) ? (string)$args['phone'] : '';

        // Parameterised query — bind every user-controlled value
        $sql    = "SELECT ID, number, message, status, sentDate, deliveredDate, type
                   FROM Message
                   WHERE userID = ?";
        $params = [(int)$user->getID()];

        if ($direction === 'received') {
            $sql .= " AND status = ?";
            $params[] = 'Received';
        } elseif ($direction === 'sent') {
            $sql .= " AND status IN (?, ?, ?, ?)";
            array_push($params, 'Sent', 'Delivered', 'Queued', 'Pending');
        }

        if ($phone !== '') {
            $clean = cleanPhoneNumber($phone);
            if ($clean !== '') {
                $sql .= " AND number = ?";
                $params[] = $clean;
            }
        }
        // $limit is an int already; safe to interpolate
        $sql .= " ORDER BY ID DESC LIMIT " . $limit;

        // Read on latin1 connection — Message.message stores raw UTF-8 bytes
        // in a latin1 column, same trick used everywhere else in the app.
        $db = MysqliDb::getInstance();
        $db->rawQuery("SET NAMES latin1");
        $rows = $db->rawQuery($sql, $params) ?: [];
        $db->rawQuery("SET NAMES utf8mb4");

        return [
            'success'  => true,
            'count'    => count($rows),
            'messages' => $rows,
        ];
    },
]);
