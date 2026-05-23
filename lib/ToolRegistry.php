<?php
/**
 * Tools register themselves here on load via ToolRegistry::register().
 * Each tool is a simple array: { name, description, inputSchema, handler }.
 *
 *   handler signature: function(array $args, User $user): array
 *
 * The registry takes care of:
 *   - exposing the JSON-Schema list via tools/list
 *   - validating that called tools exist
 *   - resolving auth (any tool other than `setup_sms8` requires a user)
 *   - wrapping handler results in the MCP "content" envelope
 */
class ToolRegistry
{
    private static array $tools = [];

    /** Tools that don't need an authenticated user (e.g. discovery). */
    private static array $publicTools = ['setup_sms8'];

    public static function register(array $tool): void
    {
        if (empty($tool['name'])) throw new InvalidArgumentException('Tool needs a name');
        self::$tools[$tool['name']] = $tool;
    }

    public static function schemas(): array
    {
        $out = [];
        foreach (self::$tools as $t) {
            $out[] = [
                'name'        => $t['name'],
                'description' => $t['description'] ?? '',
                'inputSchema' => $t['inputSchema'] ?? ['type' => 'object'],
            ];
        }
        return $out;
    }

    public static function call(string $name, array $args): array
    {
        if (!isset(self::$tools[$name])) {
            JsonRpc::error(-32601, "Unknown tool: $name");
        }
        $tool = self::$tools[$name];

        $user = null;
        if (!in_array($name, self::$publicTools, true)) {
            $user = Auth::user($args);
        }

        $result = ($tool['handler'])($args, $user);

        // MCP wraps tool results as content blocks. We default to a single
        // text block carrying the JSON payload — most clients render this fine
        // and AI agents can parse it directly.
        return [
            'content' => [
                ['type' => 'text', 'text' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)],
            ],
            'isError' => !empty($result['error']),
        ];
    }
}
