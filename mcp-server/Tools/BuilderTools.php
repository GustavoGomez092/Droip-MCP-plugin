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

        // Get page data — Droip stores it as { blocks: {...}, rootId: '...' }
        $pageMeta = get_post_meta($pageId, DROIP_APP_PREFIX, true);

        // Extract the blocks map from the wrapper structure
        if (!empty($pageMeta) && isset($pageMeta['blocks'])) {
            $blocks = &$pageMeta['blocks'];
        } elseif (!empty($pageMeta)) {
            // Legacy or flat structure — treat entire meta as blocks
            $pageMeta = ['blocks' => $pageMeta, 'rootId' => 'root'];
            $blocks = &$pageMeta['blocks'];
        } else {
            // No existing data — initialize with root + body scaffold
            $pageMeta = self::createEmptyPageData();
            $blocks = &$pageMeta['blocks'];
        }

        // Ensure root and body virtual elements exist (Droip editor requires them)
        self::ensureRootBodyElements($blocks, $pageMeta);

        // Verify parent element exists
        if (!isset($blocks[$parentElementId])) {
            $availableIds = array_keys($blocks);
            return new CallToolResult(
                [new TextContent(text: "Error: Parent element '{$parentElementId}' not found in page data. Available element IDs: " . implode(', ', $availableIds))],
                isError: true
            );
        }

        // Get symbol name for the title
        $symbolData = get_post_meta($symbolId, DROIP_APP_PREFIX, true);
        $symbolName = $symbolData['name'] ?? 'Symbol';

        // Create symbol instance element
        $instanceId = IdGenerator::elementId();
        $instanceElement = ElementFactory::symbolInstance($instanceId, $parentElementId, $symbolId);
        $instanceElement['title'] = $symbolName;
        $blocks[$instanceId] = $instanceElement;

        // Add to parent's children
        if (!isset($blocks[$parentElementId]['children'])) {
            $blocks[$parentElementId]['children'] = [];
        }
        if ($position !== null && $position >= 0) {
            array_splice($blocks[$parentElementId]['children'], $position, 0, [$instanceId]);
        } else {
            $blocks[$parentElementId]['children'][] = $instanceId;
        }

        // Save updated page data (preserving the wrapper structure)
        update_post_meta($pageId, DROIP_APP_PREFIX, $pageMeta);

        return new CallToolResult([new TextContent(
            text: json_encode([
                'success'    => true,
                'element_id' => $instanceId,
                'message'    => "Symbol {$symbolId} ({$symbolName}) added to page {$pageId} as element '{$instanceId}' under parent '{$parentElementId}'.",
            ], JSON_PRETTY_PRINT)
        )]);
    }

    /**
     * Create empty page data with root and body virtual elements.
     *
     * Droip's editor Redux store expects every page to have a virtual `root`
     * element (name="root") with a `body` child (name="body"). Without these,
     * the editor crashes at `loadBlockStyleData` trying to access
     * `data.root.children[0]`.
     *
     * @return array Page meta structure with blocks and rootId
     */
    private static function createEmptyPageData(): array
    {
        $stylePanels = [
            'typography' => true, 'composition' => true, 'size' => true,
            'background' => true, 'stroke' => true, 'shadow' => true,
            'effects' => true, 'position' => true, 'transform' => true,
            'interaction' => true, 'animation' => true,
        ];

        return [
            'blocks' => [
                'root' => [
                    'children'    => ['body'],
                    'id'          => 'root',
                    'name'        => 'root',
                    'accept'      => '*',
                    'title'       => 'Body',
                    'styleIds'    => [],
                    'stylePanels' => $stylePanels,
                ],
                'body' => [
                    'visibility'  => true,
                    'collapse'    => false,
                    'name'        => 'body',
                    'title'       => 'Body',
                    'properties'  => ['tag' => 'div'],
                    'styleIds'    => [],
                    'className'   => 'droip-body',
                    'children'    => [],
                    'id'          => 'body',
                    'parentId'    => 'root',
                    'stylePanels' => $stylePanels,
                ],
            ],
            'rootId' => 'root',
        ];
    }

    /**
     * Ensure root and body virtual elements exist in the blocks map.
     *
     * If the blocks map is missing `root` or `body`, this method adds them and
     * re-parents the current top-level element under body.
     *
     * @param array &$blocks  Reference to the blocks map
     * @param array &$pageMeta Reference to the full page meta (to update rootId)
     */
    private static function ensureRootBodyElements(array &$blocks, array &$pageMeta): void
    {
        if (isset($blocks['root']) && isset($blocks['body'])) {
            return; // Already has the required structure
        }

        $stylePanels = [
            'typography' => true, 'composition' => true, 'size' => true,
            'background' => true, 'stroke' => true, 'shadow' => true,
            'effects' => true, 'position' => true, 'transform' => true,
            'interaction' => true, 'animation' => true,
        ];

        // Find the current top-level element (parentId = null or missing)
        $topLevelId = null;
        foreach ($blocks as $eid => $el) {
            if (!isset($el['parentId']) || $el['parentId'] === null) {
                $topLevelId = $eid;
                break;
            }
        }

        // Build body children — either wrap the existing top-level element or start empty
        $bodyChildren = $topLevelId ? [$topLevelId] : [];

        if (!isset($blocks['root'])) {
            $blocks = ['root' => [
                'children'    => ['body'],
                'id'          => 'root',
                'name'        => 'root',
                'accept'      => '*',
                'title'       => 'Body',
                'styleIds'    => [],
                'stylePanels' => $stylePanels,
            ]] + $blocks;
        }

        if (!isset($blocks['body'])) {
            // Insert body right after root
            $newBlocks = [];
            foreach ($blocks as $k => $v) {
                $newBlocks[$k] = $v;
                if ($k === 'root') {
                    $newBlocks['body'] = [
                        'visibility'  => true,
                        'collapse'    => false,
                        'name'        => 'body',
                        'title'       => 'Body',
                        'properties'  => ['tag' => 'div'],
                        'styleIds'    => [],
                        'className'   => 'droip-body',
                        'children'    => $bodyChildren,
                        'id'          => 'body',
                        'parentId'    => 'root',
                        'stylePanels' => $stylePanels,
                    ];
                }
            }
            $blocks = $newBlocks;
        }

        // Re-parent the top-level element under body
        if ($topLevelId && isset($blocks[$topLevelId])) {
            $blocks[$topLevelId]['parentId'] = 'body';
        }

        // Update rootId
        $pageMeta['rootId'] = 'root';
    }
}
