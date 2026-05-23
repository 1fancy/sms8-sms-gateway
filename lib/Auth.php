<?php
/**
 * Authenticates an MCP request against the existing SMS8 API key.
 *
 * The client provides their key in one of three places (priority order):
 *   1. Header:  Authorization: Bearer <api_key>
 *   2. Header:  X-Api-Key: <api_key>
 *   3. Body:    { "params": { "_auth": { "api_key": "<api_key>" } } }
 *
 * The matched User object is cached so multiple tool calls in the same
 * request don't re-hit the DB.
 */
class Auth
{
    private static ?User $cached = null;

    public static function user(array $params = []): User
    {
        if (self::$cached) return self::$cached;

        $key = self::extractKey($params);
        if (!$key) {
            JsonRpc::error(-32001, 'Missing api_key — pass via Authorization: Bearer, X-Api-Key, or _auth.api_key');
        }

        $user = User::where('apiKey', $key)->read();
        if (!$user) {
            JsonRpc::error(-32002, 'Invalid api_key');
        }

        self::$cached = $user;
        return $user;
    }

    private static function extractKey(array $params): ?string
    {
        $hdrs = function_exists('getallheaders') ? getallheaders() : [];
        foreach ($hdrs as $k => $v) {
            $lk = strtolower($k);
            if ($lk === 'authorization' && stripos($v, 'bearer ') === 0) {
                return trim(substr($v, 7));
            }
            if ($lk === 'x-api-key') {
                return trim($v);
            }
        }
        if (!empty($params['_auth']['api_key'])) {
            return trim($params['_auth']['api_key']);
        }
        // last-ditch fallback for browser tests
        if (!empty($_REQUEST['api_key'])) {
            return trim($_REQUEST['api_key']);
        }
        return null;
    }
}
