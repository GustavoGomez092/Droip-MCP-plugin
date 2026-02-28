<?php
/**
 * MCP tools that teach Claude Code about Droip's symbol system.
 *
 * These tools return documentation, schemas, guides, and examples
 * so the AI can learn how to build valid Droip symbols.
 *
 * @package DroipBridge\Tools
 */

declare(strict_types=1);

namespace DroipBridge\Tools;

use Mcp\Types\Tool;
use Mcp\Types\ToolInputSchema;
use Mcp\Types\ToolInputProperties;
use Mcp\Types\TextContent;
use Mcp\Types\CallToolResult;
use Mcp\Types\ListToolsResult;
use Mcp\Types\CallToolRequestParams;
use Droip\Ajax\Symbol;

class KnowledgeTools
{
    private const DOCS_DIR = __DIR__ . '/../Docs/';

    /**
     * Register all knowledge tools on the MCP server.
     */
    public static function register(\Mcp\Server\Server $server): void
    {
        // --- droip_get_element_schema ---
        $server->registerHandler('tools/list', function ($params) {
            // This will be merged with other list handlers; see server.php
            return null;
        });

        $server->registerHandler('tools/call', function (CallToolRequestParams $params) {
            return match ($params->name) {
                'droip_get_element_schema'    => self::handleGetElementSchema($params->arguments),
                'droip_get_symbol_guide'      => self::handleGetSymbolGuide(),
                'droip_get_style_guide'       => self::handleGetStyleGuide(),
                'droip_get_animation_guide'   => self::handleGetAnimationGuide(),
                'droip_get_example_symbols'   => self::handleGetExampleSymbols($params->arguments),
                default => null,
            };
        });
    }

    /**
     * Return tool definitions for knowledge tools.
     *
     * @return Tool[]
     */
    public static function getTools(): array
    {
        $tools = [];

        // droip_get_element_schema
        $props = new ToolInputProperties();
        $props->element_type = ['type' => 'string', 'description' => 'Optional: filter by element type (e.g., "heading", "button", "image")'];
        $tools[] = new Tool(
            name: 'droip_get_element_schema',
            inputSchema: new ToolInputSchema(properties: $props),
            description: 'Get the schema of Droip element types with their properties, tags, and child rules. Optionally filter by a specific element type.'
        );

        // droip_get_symbol_guide
        $tools[] = new Tool(
            name: 'droip_get_symbol_guide',
            inputSchema: new ToolInputSchema(),
            description: 'Get a step-by-step guide on building Droip symbols, including the full JSON structure, best practices, and workflow.'
        );

        // droip_get_style_guide
        $tools[] = new Tool(
            name: 'droip_get_style_guide',
            inputSchema: new ToolInputSchema(),
            description: 'Get documentation on how Droip\'s CSS style system works — style blocks, responsive breakpoints, CSS format, and variables.'
        );

        // droip_get_animation_guide
        $tools[] = new Tool(
            name: 'droip_get_animation_guide',
            inputSchema: new ToolInputSchema(),
            description: 'Get documentation on Droip\'s animation and interaction system — CSS transitions, hover/focus states, transforms, backdrop filters, and the JavaScript-based interaction system (scroll, click, hover, and load-triggered keyframe animations).'
        );

        // droip_get_example_symbols
        $props = new ToolInputProperties();
        $props->type = ['type' => 'string', 'description' => 'Optional: filter examples by type (e.g., "header", "hero", "card", "footer", "button")'];
        $tools[] = new Tool(
            name: 'droip_get_example_symbols',
            inputSchema: new ToolInputSchema(properties: $props),
            description: 'Get real example symbol JSON structures from the current site. Shows the data structure of existing symbols for reference.'
        );

        return $tools;
    }

    /**
     * Handle droip_get_element_schema tool call.
     */
    public static function handleGetElementSchema(?array $args): CallToolResult
    {
        $content = self::readDoc('element-types.md');

        $filter = $args['element_type'] ?? null;
        if ($filter) {
            $content = "Filtered for element type: {$filter}\n\n" . $content;
        }

        return new CallToolResult([new TextContent(text: $content)]);
    }

    /**
     * Handle droip_get_symbol_guide tool call.
     */
    public static function handleGetSymbolGuide(): CallToolResult
    {
        $schemaDoc = self::readDoc('symbol-schema.md');
        $quickStart = self::readDoc('quick-start.md');

        return new CallToolResult([new TextContent(text: $quickStart . "\n\n---\n\n" . $schemaDoc)]);
    }

    /**
     * Handle droip_get_style_guide tool call.
     */
    public static function handleGetStyleGuide(): CallToolResult
    {
        $content = self::readDoc('style-system.md');
        return new CallToolResult([new TextContent(text: $content)]);
    }

    /**
     * Handle droip_get_animation_guide tool call.
     */
    public static function handleGetAnimationGuide(): CallToolResult
    {
        $content = self::readDoc('animations-interactions.md');
        return new CallToolResult([new TextContent(text: $content)]);
    }

    /**
     * Handle droip_get_example_symbols tool call.
     */
    public static function handleGetExampleSymbols(?array $args): CallToolResult
    {
        $typeFilter = $args['type'] ?? null;

        $symbols = Symbol::fetch_list(true, false);

        if (empty($symbols)) {
            return new CallToolResult([new TextContent(
                text: "No symbols found on this site. Use droip_get_symbol_guide to learn how to create one."
            )]);
        }

        $filtered = $symbols;
        if ($typeFilter) {
            $typeFilter = strtolower($typeFilter);
            $filtered = array_filter($symbols, function ($s) use ($typeFilter) {
                $category = strtolower($s['type'] ?? $s['symbolData']['category'] ?? '');
                $name = strtolower($s['symbolData']['name'] ?? '');
                $setAs = strtolower($s['setAs'] ?? $s['symbolData']['setAs'] ?? '');
                return str_contains($category, $typeFilter)
                    || str_contains($name, $typeFilter)
                    || str_contains($setAs, $typeFilter);
            });
        }

        if (empty($filtered)) {
            return new CallToolResult([new TextContent(
                text: "No symbols matching type '{$typeFilter}' found. Available symbols: "
                    . implode(', ', array_map(fn($s) => $s['symbolData']['name'] ?? 'Unnamed', $symbols))
            )]);
        }

        // Limit to 3 examples to avoid overwhelming output
        $examples = array_slice(array_values($filtered), 0, 3);
        $output = "## Example Symbols from Current Site\n\n";

        foreach ($examples as $symbol) {
            $data = $symbol['symbolData'];
            $output .= "### {$data['name']} (ID: {$symbol['id']})\n";
            $output .= "- Category: {$data['category']}\n";
            $output .= "- Root: {$data['root']}\n";
            $output .= "- Elements: " . count($data['data']) . "\n";
            $output .= "- Style blocks: " . count($data['styleBlocks']) . "\n";
            $output .= "\n```json\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```\n\n";
        }

        return new CallToolResult([new TextContent(text: $output)]);
    }

    private static function readDoc(string $filename): string
    {
        $path = self::DOCS_DIR . $filename;
        if (!file_exists($path)) {
            return "Documentation file not found: {$filename}";
        }
        return file_get_contents($path);
    }
}
