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
        $direction = $args['direction'] ?? 'all';
        $limit     = max(1, min(100, (int)($args['limit'] ?? 25)));
        $phone     = $args['phone']     ?? null;

        $where = ['userID = ' . (int)$user->getID()];
        if ($direction === 'received') $where[] = "status = 'Received'";
        if ($direction === 'sent')     $where[] = "status IN ('Sent','Delivered','Queued','Pending')";
        if ($phone !== '' && $phone !== null) {
            $clean = cleanPhoneNumber($phone);
            $where[] = "number = '" . addslashes($clean) . "'";
        }
        $sql = "SELECT ID, number, message, status, sentDate, deliveredDate, type
                FROM Message
                WHERE " . implode(' AND ', $where) . "
                ORDER BY ID DESC
                LIMIT " . $limit;

        // Read on latin1 connection — Message.message stores raw UTF-8 bytes
        // in a latin1 column, same trick used everywhere else in the app.
        $db = MysqliDb::getInstance();
        $db->rawQuery("SET NAMES latin1");
        $rows = $db->rawQuery($sql) ?: [];
        $db->rawQuery("SET NAMES utf8mb4");

        return [
            'success'  => true,
            'count'    => count($rows),
            'messages' => $rows,
        ];
    },
]);
