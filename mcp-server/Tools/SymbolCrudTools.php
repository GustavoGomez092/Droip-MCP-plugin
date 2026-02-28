<?php
/**
 * MCP tools for creating, listing, getting, updating, and deleting Droip symbols.
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
use Droip\Ajax\Symbol;
use DroipBridge\Validators\SymbolValidator;

class SymbolCrudTools
{
    /**
     * Leaf elements that render text via `contents` and must NOT have a `children`
     * key when they have no actual children. Droip's renderer checks
     * isset($data['children']) to decide whether to render text content or iterate
     * child elements — an empty children array causes contents to never render.
     *
     * NOTE: `button` is NOT in this list. Droip's button renderer ignores `contents`
     * and only renders children. Buttons MUST have a `text` child element.
     */
    private const CONTENT_ELEMENTS = [
        'heading', 'paragraph', 'link-text', 'text',
        'image', 'video', 'svg', 'svg-icon',
        'input', 'textarea', 'select',
        'custom-code', 'symbol', 'pagination-number',
    ];

    /**
     * Clean element data before saving to ensure compatibility with Droip's renderer.
     *
     * Fixes:
     * - Removes empty `children` arrays from content/leaf elements (prevents text rendering)
     * - Ensures buttons have a `text` child element (button renderer ignores `contents`)
     * - Ensures root element has parentId = null
     */
    private static function cleanElementData(array &$symbolData): void
    {
        $rootId = $symbolData['root'] ?? null;
        $buttonsNeedingText = [];

        foreach ($symbolData['data'] as $elId => &$element) {
            $name = $element['name'] ?? '';

            // Remove empty children array from content elements so Droip
            // renders their contents[] text instead of iterating 0 children
            if (in_array($name, self::CONTENT_ELEMENTS, true)) {
                if (isset($element['children']) && empty($element['children'])) {
                    unset($element['children']);
                }
            }

            // Track buttons that need text children
            if ($name === 'button') {
                $hasTextChild = false;
                if (isset($element['children']) && !empty($element['children'])) {
                    foreach ($element['children'] as $cid) {
                        if (isset($symbolData['data'][$cid]) && ($symbolData['data'][$cid]['name'] ?? '') === 'text') {
                            $hasTextChild = true;
                            break;
                        }
                    }
                }
                if (!$hasTextChild) {
                    $buttonsNeedingText[] = $elId;
                }
            }

            // Ensure root element has parentId = null (not "body" or other values)
            if ($elId === $rootId && isset($element['parentId']) && $element['parentId'] !== null) {
                $element['parentId'] = null;
            }
        }
        unset($element);

        // Add text children to buttons that need them
        foreach ($buttonsNeedingText as $btnId) {
            $btn = &$symbolData['data'][$btnId];
            $text = $btn['properties']['contents'][0] ?? 'Button';
            $textId = \DroipBridge\Builders\IdGenerator::elementId();

            $symbolData['data'][$textId] = [
                'visibility'  => true,
                'collapse'    => false,
                'name'        => 'text',
                'title'       => 'Text',
                'properties'  => [
                    'tag'            => 'span',
                    'contents'       => [$text],
                    'symbolElPropId' => \DroipBridge\Builders\IdGenerator::symbolElPropId(),
                ],
                'styleIds'    => [],
                'className'   => '',
                'source'      => 'droip',
                'id'          => $textId,
                'parentId'    => $btnId,
                'stylePanels' => [
                    'typography' => true, 'composition' => true, 'size' => true,
                    'background' => true, 'stroke' => true, 'shadow' => true,
                    'effects' => true, 'position' => true, 'transform' => true,
                    'interaction' => true, 'animation' => true,
                ],
            ];

            if (!isset($btn['children'])) {
                $btn['children'] = [];
            }
            $btn['children'][] = $textId;
            unset($btn);
        }
    }

    /**
     * Return tool definitions for symbol CRUD tools.
     *
     * @return Tool[]
     */
    public static function getTools(): array
    {
        $tools = [];

        // droip_create_symbol
        $props = new ToolInputProperties();
        $props->name = ['type' => 'string', 'description' => 'Symbol display name'];
        $props->category = ['type' => 'string', 'description' => 'Category (e.g., "Sections", "Buttons", "other"). Default: "other"'];
        $props->setAs = ['type' => 'string', 'description' => 'Symbol role: "" (default), "header", or "footer"'];
        $props->rootTag = ['type' => 'string', 'description' => 'Root element HTML tag. Default: "section"'];
        $props->data = ['type' => 'object', 'description' => 'Flat element map keyed by element ID. Each value is a complete element node.'];
        $props->styleBlocks = ['type' => 'object', 'description' => 'Style blocks map keyed by style block ID.'];
        $props->customFonts = ['type' => 'object', 'description' => 'Optional: Custom font definitions used by this symbol.'];
        $tools[] = new Tool(
            name: 'droip_create_symbol',
            inputSchema: new ToolInputSchema(properties: $props, required: ['name', 'data', 'styleBlocks']),
            description: 'Create a new Droip symbol. Provide the element data map and style blocks. The symbol will be validated before saving. Use droip_get_symbol_guide to learn the expected structure.'
        );

        // droip_list_symbols
        $props = new ToolInputProperties();
        $props->include_data = ['type' => 'boolean', 'description' => 'Include full element data and style blocks. Default: false'];
        $tools[] = new Tool(
            name: 'droip_list_symbols',
            inputSchema: new ToolInputSchema(properties: $props),
            description: 'List all Droip symbols on the site with their IDs, names, categories, and roles.'
        );

        // droip_get_symbol
        $props = new ToolInputProperties();
        $props->symbol_id = ['type' => 'integer', 'description' => 'WordPress post ID of the symbol'];
        $tools[] = new Tool(
            name: 'droip_get_symbol',
            inputSchema: new ToolInputSchema(properties: $props, required: ['symbol_id']),
            description: 'Get the full data of a specific Droip symbol, including all elements and style blocks.'
        );

        // droip_update_symbol
        $props = new ToolInputProperties();
        $props->symbol_id = ['type' => 'integer', 'description' => 'WordPress post ID of the symbol to update'];
        $props->name = ['type' => 'string', 'description' => 'New display name'];
        $props->category = ['type' => 'string', 'description' => 'New category'];
        $props->setAs = ['type' => 'string', 'description' => 'New role: "", "header", or "footer"'];
        $props->data = ['type' => 'object', 'description' => 'New element data map (replaces entire data)'];
        $props->styleBlocks = ['type' => 'object', 'description' => 'New style blocks (replaces entire styleBlocks)'];
        $tools[] = new Tool(
            name: 'droip_update_symbol',
            inputSchema: new ToolInputSchema(properties: $props, required: ['symbol_id']),
            description: 'Update an existing Droip symbol. Only provided fields are updated.'
        );

        // droip_delete_symbol
        $props = new ToolInputProperties();
        $props->symbol_id = ['type' => 'integer', 'description' => 'WordPress post ID of the symbol to delete'];
        $tools[] = new Tool(
            name: 'droip_delete_symbol',
            inputSchema: new ToolInputSchema(properties: $props, required: ['symbol_id']),
            description: 'Delete a Droip symbol permanently.'
        );

        return $tools;
    }

    /**
     * Handle droip_create_symbol.
     */
    public static function handleCreate(array $args): CallToolResult
    {
        $name = $args['name'] ?? '';
        if (empty($name)) {
            return new CallToolResult(
                [new TextContent(text: 'Error: "name" is required')],
                isError: true
            );
        }

        $data = $args['data'] ?? null;
        $styleBlocks = $args['styleBlocks'] ?? [];

        if (empty($data)) {
            return new CallToolResult(
                [new TextContent(text: 'Error: "data" (element map) is required')],
                isError: true
            );
        }

        // Find the root element (the one with parentId = null)
        $rootId = null;
        foreach ($data as $elId => $el) {
            if (!isset($el['parentId']) || $el['parentId'] === null) {
                $rootId = $elId;
                break;
            }
        }

        if ($rootId === null) {
            return new CallToolResult(
                [new TextContent(text: 'Error: No root element found (an element with parentId = null)')],
                isError: true
            );
        }

        $symbolData = [
            'name'        => $name,
            'category'    => $args['category'] ?? 'other',
            'root'        => $rootId,
            'setAs'       => $args['setAs'] ?? '',
            'customFonts' => $args['customFonts'] ?? [],
            'data'        => $data,
            'styleBlocks' => $styleBlocks,
        ];

        // Clean element data for Droip renderer compatibility
        self::cleanElementData($symbolData);

        // Validate
        $validation = SymbolValidator::validate($symbolData);
        if (!$validation['valid']) {
            $msg = "Validation failed:\n" . implode("\n", array_map(fn($e) => "- ERROR: {$e}", $validation['errors']));
            if (!empty($validation['warnings'])) {
                $msg .= "\n" . implode("\n", array_map(fn($w) => "- WARNING: {$w}", $validation['warnings']));
            }
            return new CallToolResult([new TextContent(text: $msg)], isError: true);
        }

        // Save via Droip's API
        $payload = ['symbolData' => $symbolData];
        $result = Symbol::save_to_db($payload);

        if ($result === null) {
            return new CallToolResult(
                [new TextContent(text: 'Error: Failed to save symbol to database')],
                isError: true
            );
        }

        $warnings = '';
        if (!empty($validation['warnings'])) {
            $warnings = "\nWarnings:\n" . implode("\n", array_map(fn($w) => "- {$w}", $validation['warnings']));
        }

        return new CallToolResult([new TextContent(
            text: json_encode([
                'success'  => true,
                'id'       => $result['id'],
                'name'     => $name,
                'category' => $symbolData['category'],
                'message'  => "Symbol '{$name}' created successfully with ID {$result['id']}." . $warnings,
            ], JSON_PRETTY_PRINT)
        )]);
    }

    /**
     * Handle droip_list_symbols.
     */
    public static function handleList(array $args): CallToolResult
    {
        $includeData = $args['include_data'] ?? false;
        $symbols = Symbol::fetch_list(true, false);

        if (empty($symbols)) {
            return new CallToolResult([new TextContent(text: 'No symbols found.')]);
        }

        $output = [];
        foreach ($symbols as $symbol) {
            $entry = [
                'id'       => $symbol['id'],
                'name'     => $symbol['symbolData']['name'] ?? 'Unnamed',
                'category' => $symbol['symbolData']['category'] ?? 'other',
                'setAs'    => $symbol['symbolData']['setAs'] ?? '',
            ];

            if ($includeData) {
                $entry['data'] = $symbol['symbolData']['data'] ?? [];
                $entry['styleBlocks'] = $symbol['symbolData']['styleBlocks'] ?? [];
            }

            $output[] = $entry;
        }

        return new CallToolResult([new TextContent(
            text: json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        )]);
    }

    /**
     * Handle droip_get_symbol.
     */
    public static function handleGet(array $args): CallToolResult
    {
        $symbolId = (int) ($args['symbol_id'] ?? 0);
        if ($symbolId <= 0) {
            return new CallToolResult(
                [new TextContent(text: 'Error: "symbol_id" is required and must be a positive integer')],
                isError: true
            );
        }

        $symbol = Symbol::get_single_symbol($symbolId, true, false);

        if ($symbol === null) {
            return new CallToolResult(
                [new TextContent(text: "Error: Symbol with ID {$symbolId} not found")],
                isError: true
            );
        }

        return new CallToolResult([new TextContent(
            text: json_encode($symbol, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        )]);
    }

    /**
     * Handle droip_update_symbol.
     */
    public static function handleUpdate(array $args): CallToolResult
    {
        $symbolId = (int) ($args['symbol_id'] ?? 0);
        if ($symbolId <= 0) {
            return new CallToolResult(
                [new TextContent(text: 'Error: "symbol_id" is required and must be a positive integer')],
                isError: true
            );
        }

        $symbolData = get_post_meta($symbolId, DROIP_APP_PREFIX, true);
        if (empty($symbolData)) {
            return new CallToolResult(
                [new TextContent(text: "Error: Symbol with ID {$symbolId} not found")],
                isError: true
            );
        }

        // Apply updates
        $updated = false;
        foreach (['name', 'category', 'setAs'] as $field) {
            if (isset($args[$field])) {
                $symbolData[$field] = $args[$field];
                $updated = true;
            }
        }
        if (isset($args['data'])) {
            $symbolData['data'] = $args['data'];
            $updated = true;
        }
        if (isset($args['styleBlocks'])) {
            $symbolData['styleBlocks'] = $args['styleBlocks'];
            $updated = true;
        }

        if (!$updated) {
            return new CallToolResult([new TextContent(text: 'No updates provided.')]);
        }

        // Clean element data for Droip renderer compatibility
        if (isset($args['data'])) {
            self::cleanElementData($symbolData);
        }

        // Validate if data or styleBlocks changed
        if (isset($args['data']) || isset($args['styleBlocks'])) {
            $validation = SymbolValidator::validate($symbolData);
            if (!$validation['valid']) {
                $msg = "Validation failed:\n" . implode("\n", array_map(fn($e) => "- ERROR: {$e}", $validation['errors']));
                return new CallToolResult([new TextContent(text: $msg)], isError: true);
            }
        }

        // Handle setAs uniqueness (only one header/footer at a time)
        if (isset($args['setAs']) && $args['setAs'] !== '') {
            $allSymbols = Symbol::fetch_list(true);
            foreach ($allSymbols as $s) {
                if ($s['id'] != $symbolId
                    && isset($s['symbolData']['setAs'])
                    && $s['symbolData']['setAs'] === $args['setAs']
                ) {
                    $s['symbolData']['setAs'] = '';
                    update_post_meta($s['id'], DROIP_APP_PREFIX, $s['symbolData']);
                }
            }
        }

        $success = update_post_meta($symbolId, DROIP_APP_PREFIX, $symbolData);

        return new CallToolResult([new TextContent(
            text: json_encode([
                'success' => $success !== false,
                'id'      => $symbolId,
                'message' => "Symbol {$symbolId} updated successfully.",
            ], JSON_PRETTY_PRINT)
        )]);
    }

    /**
     * Handle droip_delete_symbol.
     */
    public static function handleDelete(array $args): CallToolResult
    {
        $symbolId = (int) ($args['symbol_id'] ?? 0);
        if ($symbolId <= 0) {
            return new CallToolResult(
                [new TextContent(text: 'Error: "symbol_id" is required and must be a positive integer')],
                isError: true
            );
        }

        $post = get_post($symbolId);
        if (!$post || $post->post_type !== DROIP_SYMBOL_TYPE) {
            return new CallToolResult(
                [new TextContent(text: "Error: Symbol with ID {$symbolId} not found")],
                isError: true
            );
        }

        $result = wp_delete_post($symbolId, true);

        return new CallToolResult([new TextContent(
            text: json_encode([
                'success' => $result !== false && $result !== null,
                'message' => $result ? "Symbol {$symbolId} deleted." : "Failed to delete symbol {$symbolId}.",
            ], JSON_PRETTY_PRINT)
        )]);
    }
}
