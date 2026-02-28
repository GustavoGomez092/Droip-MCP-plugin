<?php
/**
 * MCP utility tools for validation, ID generation, and adding symbols to pages.
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
use DroipBridge\Builders\IdGenerator;
use DroipBridge\Builders\ElementFactory;
use DroipBridge\Validators\SymbolValidator;
use Droip\HelperFunctions;

class BuilderTools
{
    /**
     * Return tool definitions for builder/utility tools.
     *
     * @return Tool[]
     */
    public static function getTools(): array
    {
        $tools = [];

        // droip_validate_symbol
        $props = new ToolInputProperties();
        $props->symbol_data = ['type' => 'object', 'description' => 'The symbol data structure to validate (the inner symbolData object with name, root, data, styleBlocks)'];
        $tools[] = new Tool(
            name: 'droip_validate_symbol',
            inputSchema: new ToolInputSchema(properties: $props, required: ['symbol_data']),
            description: 'Validate a Droip symbol data structure before saving. Returns errors and warnings.'
        );

        // droip_generate_ids
        $props = new ToolInputProperties();
        $props->count = ['type' => 'integer', 'description' => 'Number of IDs to generate. Default: 1'];
        $props->type = ['type' => 'string', 'description' => '"element" or "style". Default: "element"'];
        $tools[] = new Tool(
            name: 'droip_generate_ids',
            inputSchema: new ToolInputSchema(properties: $props),
            description: 'Generate unique Droip-compatible IDs for elements or style blocks. Returns an array of IDs.'
        );

        // droip_add_symbol_to_page
        $props = new ToolInputProperties();
        $props->page_id = ['type' => 'integer', 'description' => 'WordPress page/post ID'];
        $props->symbol_id = ['type' => 'integer', 'description' => 'Droip symbol post ID to add'];
        $props->parent_element_id = ['type' => 'string', 'description' => 'ID of the parent element in the page\'s element tree where the symbol instance will be added'];
        $props->position = ['type' => 'integer', 'description' => 'Position index in the parent\'s children array. Default: append at end'];
        $tools[] = new Tool(
            name: 'droip_add_symbol_to_page',
            inputSchema: new ToolInputSchema(properties: $props, required: ['page_id', 'symbol_id', 'parent_element_id']),
            description: 'Add a symbol instance to a page\'s element tree. Creates a symbol reference element as a child of the specified parent.'
        );

        return $tools;
    }

    /**
     * Handle droip_validate_symbol.
     */
    public static function handleValidate(array $args): CallToolResult
    {
        $symbolData = $args['symbol_data'] ?? null;
        if (empty($symbolData)) {
            return new CallToolResult(
                [new TextContent(text: 'Error: "symbol_data" is required')],
                isError: true
            );
        }

        $result = SymbolValidator::validate($symbolData);

        return new CallToolResult([new TextContent(
            text: json_encode($result, JSON_PRETTY_PRINT)
        )]);
    }

    /**
     * Handle droip_generate_ids.
     */
    public static function handleGenerateIds(array $args): CallToolResult
    {
        $count = max(1, (int) ($args['count'] ?? 1));
        $type = $args['type'] ?? 'element';

        if ($count > 100) {
            return new CallToolResult(
                [new TextContent(text: 'Error: Maximum 100 IDs per request')],
                isError: true
            );
        }

        $ids = match ($type) {
            'style' => IdGenerator::styleBatch($count),
            default => IdGenerator::elementBatch($count),
        };

        return new CallToolResult([new TextContent(
            text: json_encode(['ids' => $ids, 'type' => $type], JSON_PRETTY_PRINT)
        )]);
    }

    /**
     * Handle droip_add_symbol_to_page.
     */
    public static function handleAddSymbolToPage(array $args): CallToolResult
    {
        $pageId = (int) ($args['page_id'] ?? 0);
        $symbolId = (int) ($args['symbol_id'] ?? 0);
        $parentElementId = $args['parent_element_id'] ?? '';
        $position = isset($args['position']) ? (int) $args['position'] : null;

        if ($pageId <= 0 || $symbolId <= 0 || empty($parentElementId)) {
            return new CallToolResult(
                [new TextContent(text: 'Error: page_id, symbol_id, and parent_element_id are required')],
                isError: true
            );
        }

        // Verify page exists
        $post = get_post($pageId);
        if (!$post) {
            return new CallToolResult(
                [new TextContent(text: "Error: Page with ID {$pageId} not found")],
                isError: true
            );
        }

        // Verify symbol exists
        $symbolPost = get_post($symbolId);
        if (!$symbolPost || $symbolPost->post_type !== DROIP_SYMBOL_TYPE) {
            return new CallToolResult(
                [new TextContent(text: "Error: Symbol with ID {$symbolId} not found")],
                isError: true
            );
        }

        // Get page data
        $blocks = get_post_meta($pageId, DROIP_APP_PREFIX, true);
        if (empty($blocks)) {
            return new CallToolResult(
                [new TextContent(text: "Error: Page {$pageId} has no Droip data")],
                isError: true
            );
        }

        // Verify parent element exists
        if (!isset($blocks[$parentElementId])) {
            return new CallToolResult(
                [new TextContent(text: "Error: Parent element '{$parentElementId}' not found in page data")],
                isError: true
            );
        }

        // Create symbol instance element
        $instanceId = IdGenerator::elementId();
        $instanceElement = ElementFactory::symbolInstance($instanceId, $parentElementId, $symbolId);
        $blocks[$instanceId] = $instanceElement;

        // Add to parent's children
        if ($position !== null && $position >= 0) {
            array_splice($blocks[$parentElementId]['children'], $position, 0, [$instanceId]);
        } else {
            $blocks[$parentElementId]['children'][] = $instanceId;
        }

        // Save updated page data
        update_post_meta($pageId, DROIP_APP_PREFIX, $blocks);

        return new CallToolResult([new TextContent(
            text: json_encode([
                'success'    => true,
                'element_id' => $instanceId,
                'message'    => "Symbol {$symbolId} added to page {$pageId} as element '{$instanceId}'.",
            ], JSON_PRETTY_PRINT)
        )]);
    }
}
