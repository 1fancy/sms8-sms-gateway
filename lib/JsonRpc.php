<?php
/**
 * Tiny JSON-RPC 2.0 dispatcher that implements the subset of the Model
 * Context Protocol we need:
 *
 *   - initialize          → returns server info + protocol version
 *   - tools/list          → returns the list of registered tools + schemas
 *   - tools/call          → invokes a tool by name with arguments
 *
 * Anything else returns the standard JSON-RPC "method not found" error.
 *
 * Keeping this hand-rolled instead of pulling in a heavyweight MCP SDK
 * keeps the server tiny (no node, no extra runtime) and lets us re-use
 * the existing SMS8 PHP codebase directly.
 */
class JsonRpc
{
    public static function handle(): void
    {
        $raw = file_get_contents('php://input');
        $req = json_decode($raw, true);
        if (!is_array($req)) {
            self::respond(null, ['error' => ['code' => -32700, 'message' => 'Parse error']]);
            return;
        }

        // batch support — MCP rarely uses it but the spec allows it
        if (array_is_list($req) && !empty($req) && is_array($req[0])) {
            $out = [];
            foreach ($req as $one) $out[] = self::dispatch($one);
            echo json_encode(array_filter($out));
            return;
        }
        echo json_encode(self::dispatch($req));
    }

    private static function dispatch(array $req): ?array
    {
        $id     = $req['id']     ?? null;
        $method = $req['method'] ?? '';
        $params = $req['params'] ?? [];

        try {
            switch ($method) {
                case 'initialize':
                    $result = self::initialize($params);
                    break;
                case 'tools/list':
                    $result = ['tools' => ToolRegistry::schemas()];
                    break;
                case 'tools/call':
                    $name      = $params['name']      ?? '';
                    $arguments = $params['arguments'] ?? [];
                    $result    = ToolRegistry::call($name, $arguments);
                    break;
                case 'ping':
                    $result = ['pong' => true];
                    break;
                default:
                    return self::buildError($id, -32601, "Method not found: $method");
            }
            // Notifications (no id) → no response per spec
            if ($id === null) return null;
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
        } catch (JsonRpcException $e) {
            return self::buildError($id, $e->getCode(), $e->getMessage(), $e->data);
        } catch (Throwable $t) {
            error_log('MCP dispatch: ' . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine());
            return self::buildError($id, -32603, 'Internal error: ' . $t->getMessage());
        }
    }

    private static function initialize(array $params): array
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => [
                'tools' => new stdClass(),  // server provides tools
            ],
            'serverInfo' => [
                'name'    => 'sms8-mcp',
                'version' => '0.1.0',
                'vendor'  => 'SMS8.io',
                'website' => 'https://sms8.io',
                'docs'    => 'https://mcp.sms8.io',
            ],
            'instructions' => 'Call tools/list to discover available SMS8 tools. '
                . 'Authenticate by sending the user\'s SMS8 API key in an Authorization: Bearer header '
                . 'or by setting it once via the setup_sms8 tool.',
        ];
    }

    public static function error(int $code, string $message, $data = null): void
    {
        throw new JsonRpcException($message, $code, $data);
    }

    private static function buildError($id, int $code, string $msg, $data = null): array
    {
        $err = ['code' => $code, 'message' => $msg];
        if ($data !== null) $err['data'] = $data;
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => $err];
    }

    private static function respond($id, array $body): void
    {
        echo json_encode(array_merge(['jsonrpc' => '2.0', 'id' => $id], $body));
    }
}

class JsonRpcException extends Exception
{
    public $data;
    public function __construct(string $msg, int $code, $data = null) {
        parent::__construct($msg, $code);
        $this->data = $data;
    }
}
