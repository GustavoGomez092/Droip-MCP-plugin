#!/usr/bin/env php
<?php
/**
 * Droip Claude Bridge — MCP Server Entry Point
 *
 * Bootstraps WordPress + Droip, then runs an MCP server over stdio
 * that exposes tools for creating, reading, updating, and deleting
 * Droip symbols, plus documentation resources.
 *
 * Usage:
 *   php server.php
 *
 * Configure in Claude Code's .mcp.json:
 *   {
 *     "mcpServers": {
 *       "droip-bridge": {
 *         "command": "php",
 *         "args": ["/path/to/this/server.php"],
 *         "env": { "WP_ROOT_PATH": "/path/to/wordpress" }
 *       }
 *     }
 *   }
 *
 * @package DroipBridge
 */

declare(strict_types=1);

// Suppress all output to keep stdio clean
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../mcp-server-errors.log');
error_reporting(E_ALL);

// Load Composer autoloader
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    fwrite(STDERR, "ERROR: vendor/autoload.php not found. Run 'composer install' in the droip-claude-bridge directory.\n");
    exit(1);
}
require_once $autoloadPath;

// Bootstrap WordPress + Droip
require_once __DIR__ . '/bootstrap.php';

// Check if the MCP server is enabled in WP admin
$bridgeSettings = get_option('droip_claude_bridge_settings', ['enabled' => false]);
if (empty($bridgeSettings['enabled'])) {
    fwrite(STDERR, "MCP server is disabled. Enable it in WP Admin > Claude Bridge.\n");
    exit(1);
}

use Mcp\Server\Server;
use Mcp\Server\ServerRunner;
use Mcp\Types\Tool;
use Mcp\Types\ToolInputSchema;
use Mcp\Types\ToolInputProperties;
use Mcp\Types\TextContent;
use Mcp\Types\CallToolResult;
use Mcp\Types\ListToolsResult;
use Mcp\Types\CallToolRequestParams;
use Mcp\Types\Resource;
use Mcp\Types\ListResourcesResult;
use Mcp\Types\ReadResourceResult;
use Mcp\Types\TextResourceContents;

use DroipBridge\Tools\KnowledgeTools;
use DroipBridge\Tools\SymbolCrudTools;
use DroipBridge\Tools\PageDataTools;
use DroipBridge\Tools\BuilderTools;
use DroipBridge\Resources\DocsProvider;

// Create MCP server
$server = new Server('droip-bridge');

// ─── Collect all tools ──────────────────────────────────────────────
$allTools = array_merge(
    KnowledgeTools::getTools(),
    SymbolCrudTools::getTools(),
    PageDataTools::getTools(),
    BuilderTools::getTools(),
);

// ─── Register tools/list handler ────────────────────────────────────
$server->registerHandler('tools/list', function ($params) use ($allTools) {
    return new ListToolsResult($allTools);
});

// ─── Register tools/call handler ────────────────────────────────────
$server->registerHandler('tools/call', function (CallToolRequestParams $params) {
    $args = $params->arguments ?? [];

    try {
        $result = match (true) {
            // Knowledge tools
            $params->name === 'droip_get_element_schema'  => KnowledgeTools::handleGetElementSchema($args),
            $params->name === 'droip_get_symbol_guide'    => KnowledgeTools::handleGetSymbolGuide(),
            $params->name === 'droip_get_style_guide'     => KnowledgeTools::handleGetStyleGuide(),
            $params->name === 'droip_get_animation_guide' => KnowledgeTools::handleGetAnimationGuide(),
            $params->name === 'droip_get_example_symbols' => KnowledgeTools::handleGetExampleSymbols($args),

            // Symbol CRUD tools
            $params->name === 'droip_create_symbol'  => SymbolCrudTools::handleCreate($args),
            $params->name === 'droip_list_symbols'   => SymbolCrudTools::handleList($args),
            $params->name === 'droip_get_symbol'     => SymbolCrudTools::handleGet($args),
            $params->name === 'droip_update_symbol'  => SymbolCrudTools::handleUpdate($args),
            $params->name === 'droip_delete_symbol'  => SymbolCrudTools::handleDelete($args),

            // Page/Data tools
            $params->name === 'droip_list_pages'       => PageDataTools::handleListPages($args),
            $params->name === 'droip_get_page_data'    => PageDataTools::handleGetPageData($args),
            $params->name === 'droip_get_global_styles' => PageDataTools::handleGetGlobalStyles(),
            $params->name === 'droip_get_variables'    => PageDataTools::handleGetVariables(),

            // Builder/Utility tools
            $params->name === 'droip_validate_symbol'    => BuilderTools::handleValidate($args),
            $params->name === 'droip_generate_ids'       => BuilderTools::handleGenerateIds($args),
            $params->name === 'droip_add_symbol_to_page' => BuilderTools::handleAddSymbolToPage($args),

            default => new CallToolResult(
                [new TextContent(text: "Unknown tool: {$params->name}")],
                isError: true
            ),
        };
    } catch (\Throwable $e) {
        fwrite(STDERR, "Tool error [{$params->name}]: {$e->getMessage()}\n{$e->getTraceAsString()}\n");
        $result = new CallToolResult(
            [new TextContent(text: "Error: {$e->getMessage()}")],
            isError: true
        );
    }

    return $result;
});

// ─── Register resources ─────────────────────────────────────────────
$resources = DocsProvider::getResources();

$server->registerHandler('resources/list', function ($params) use ($resources) {
    return new ListResourcesResult($resources);
});

$server->registerHandler('resources/read', function ($params) {
    $uri = $params->uri ?? '';
    return DocsProvider::handleRead($uri);
});

// ─── Run the server ─────────────────────────────────────────────────
$initOptions = $server->createInitializationOptions();
$runner = new ServerRunner($server, $initOptions);

try {
    $runner->run();
} catch (\Throwable $e) {
    fwrite(STDERR, "MCP server fatal error: {$e->getMessage()}\n{$e->getTraceAsString()}\n");
    exit(1);
}
