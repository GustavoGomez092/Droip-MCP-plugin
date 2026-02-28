<?php
/**
 * MCP tools for reading page data, global styles, and design variables.
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
use Droip\HelperFunctions;

class PageDataTools
{
    /**
     * Return tool definitions for page/data tools.
     *
     * @return Tool[]
     */
    public static function getTools(): array
    {
        $tools = [];

        // droip_list_pages
        $props = new ToolInputProperties();
        $props->post_type = ['type' => 'string', 'description' => 'WordPress post type to list. Default: "page"'];
        $tools[] = new Tool(
            name: 'droip_list_pages',
            inputSchema: new ToolInputSchema(properties: $props),
            description: 'List WordPress pages/posts that have Droip data. Returns ID, title, slug, status, and whether the page has Droip element data.'
        );

        // droip_get_page_data
        $props = new ToolInputProperties();
        $props->page_id = ['type' => 'integer', 'description' => 'WordPress post/page ID'];
        $tools[] = new Tool(
            name: 'droip_get_page_data',
            inputSchema: new ToolInputSchema(properties: $props, required: ['page_id']),
            description: 'Get Droip element tree and style blocks for a specific page.'
        );

        // droip_get_global_styles
        $tools[] = new Tool(
            name: 'droip_get_global_styles',
            inputSchema: new ToolInputSchema(),
            description: 'Get all global style blocks shared across the site.'
        );

        // droip_get_variables
        $tools[] = new Tool(
            name: 'droip_get_variables',
            inputSchema: new ToolInputSchema(),
            description: 'Get design system variables (CSS custom properties) — colors, spacing, typography tokens.'
        );

        return $tools;
    }

    /**
     * Handle droip_list_pages.
     */
    public static function handleListPages(array $args): CallToolResult
    {
        $postType = $args['post_type'] ?? 'page';

        $posts = get_posts([
            'post_type'   => $postType,
            'post_status' => ['publish', 'draft', 'private'],
            'numberposts' => 100,
        ]);

        $output = [];
        foreach ($posts as $post) {
            $hasDroipData = !empty(get_post_meta($post->ID, DROIP_APP_PREFIX, true));
            $editorMode = get_post_meta($post->ID, DROIP_META_NAME_FOR_POST_EDITOR_MODE, true);

            $output[] = [
                'id'             => $post->ID,
                'title'          => $post->post_title,
                'slug'           => $post->post_name,
                'status'         => $post->post_status,
                'has_droip_data' => $hasDroipData,
                'editor_mode'    => $editorMode ?: 'none',
            ];
        }

        return new CallToolResult([new TextContent(
            text: json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        )]);
    }

    /**
     * Handle droip_get_page_data.
     */
    public static function handleGetPageData(array $args): CallToolResult
    {
        $pageId = (int) ($args['page_id'] ?? 0);
        if ($pageId <= 0) {
            return new CallToolResult(
                [new TextContent(text: 'Error: "page_id" is required')],
                isError: true
            );
        }

        $post = get_post($pageId);
        if (!$post) {
            return new CallToolResult(
                [new TextContent(text: "Error: Post with ID {$pageId} not found")],
                isError: true
            );
        }

        $blocks = get_post_meta($pageId, DROIP_APP_PREFIX, true);
        $styleBlocks = HelperFunctions::get_page_styleblocks($pageId);

        if (empty($blocks)) {
            return new CallToolResult([new TextContent(
                text: json_encode([
                    'page_id'     => $pageId,
                    'title'       => $post->post_title,
                    'has_data'    => false,
                    'message'     => 'This page has no Droip data.',
                ], JSON_PRETTY_PRINT)
            )]);
        }

        return new CallToolResult([new TextContent(
            text: json_encode([
                'page_id'     => $pageId,
                'title'       => $post->post_title,
                'blocks'      => $blocks,
                'styleBlocks' => $styleBlocks ?: [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        )]);
    }

    /**
     * Handle droip_get_global_styles.
     */
    public static function handleGetGlobalStyles(): CallToolResult
    {
        $globalStyles = HelperFunctions::get_global_data_using_key(DROIP_GLOBAL_STYLE_BLOCK_META_KEY);

        if (empty($globalStyles)) {
            return new CallToolResult([new TextContent(text: 'No global style blocks found.')]);
        }

        return new CallToolResult([new TextContent(
            text: json_encode($globalStyles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        )]);
    }

    /**
     * Handle droip_get_variables.
     */
    public static function handleGetVariables(): CallToolResult
    {
        // Droip stores CSS variables/tokens in global data
        $userSavedData = HelperFunctions::get_global_data_using_key(DROIP_USER_SAVED_DATA_META_KEY);

        $output = [
            'user_saved_data' => $userSavedData ?: [],
        ];

        // Also fetch custom fonts
        $customFonts = HelperFunctions::get_global_data_using_key(DROIP_USER_CUSTOM_FONTS_META_KEY);
        if ($customFonts) {
            $output['custom_fonts'] = $customFonts;
        }

        // Fetch viewport/controller data
        $controllerData = HelperFunctions::get_global_data_using_key(DROIP_USER_CONTROLLER_META_KEY);
        if ($controllerData) {
            $output['viewports'] = $controllerData;
        }

        return new CallToolResult([new TextContent(
            text: json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        )]);
    }
}
