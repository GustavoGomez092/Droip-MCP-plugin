<?php
/**
 * MCP resource provider for Droip documentation.
 *
 * Serves markdown documentation files as MCP resources, making them
 * available to Claude Code as reference materials.
 *
 * @package DroipBridge\Resources
 */

declare(strict_types=1);

namespace DroipBridge\Resources;

use Mcp\Types\Resource;
use Mcp\Types\ListResourcesResult;
use Mcp\Types\ReadResourceResult;
use Mcp\Types\TextResourceContents;

class DocsProvider
{
    private const DOCS_DIR = __DIR__ . '/../Docs/';

    private const RESOURCES = [
        'droip://docs/quick-start' => [
            'file'        => 'quick-start.md',
            'name'        => 'Droip Quick Start Guide',
            'description' => 'Step-by-step guide to creating your first Droip symbol, including workflow, tips, and a complete hero section example.',
        ],
        'droip://docs/symbol-schema' => [
            'file'        => 'symbol-schema.md',
            'name'        => 'Droip Symbol JSON Schema',
            'description' => 'Complete JSON schema reference for Droip symbols — top-level structure, element nodes, properties, style blocks, fonts, and validation rules.',
        ],
        'droip://docs/element-types' => [
            'file'        => 'element-types.md',
            'name'        => 'Droip Element Types Reference',
            'description' => 'All Droip element types (div, heading, paragraph, button, image, video, svg, form, etc.) with their tags, properties, and child rules.',
        ],
        'droip://docs/style-system' => [
            'file'        => 'style-system.md',
            'name'        => 'Droip Style System',
            'description' => 'How Droip\'s CSS style blocks work — responsive breakpoints, CSS format, variables, and common patterns for layout, typography, and spacing.',
        ],
        'droip://docs/animations-interactions' => [
            'file'        => 'animations-interactions.md',
            'name'        => 'Droip Animations, Transitions & Interactions',
            'description' => 'Complete guide to CSS transitions (hover/focus/active states), CSS transforms, backdrop filters, and the JavaScript interaction system (scroll, click, hover, and load-triggered keyframe animations).',
        ],
    ];

    /**
     * Get all resource definitions.
     *
     * @return Resource[]
     */
    public static function getResources(): array
    {
        $resources = [];
        foreach (self::RESOURCES as $uri => $meta) {
            $resources[] = new Resource(
                name: $meta['name'],
                uri: $uri,
                description: $meta['description'],
                mimeType: 'text/markdown',
            );
        }
        return $resources;
    }

    /**
     * Handle a resource read request.
     */
    public static function handleRead(string $uri): ReadResourceResult
    {
        if (!isset(self::RESOURCES[$uri])) {
            return new ReadResourceResult([
                new TextResourceContents(
                    text: "Resource not found: {$uri}",
                    uri: $uri,
                    mimeType: 'text/plain',
                ),
            ]);
        }

        $meta = self::RESOURCES[$uri];
        $filePath = self::DOCS_DIR . $meta['file'];

        if (!file_exists($filePath)) {
            return new ReadResourceResult([
                new TextResourceContents(
                    text: "Documentation file not found: {$meta['file']}",
                    uri: $uri,
                    mimeType: 'text/plain',
                ),
            ]);
        }

        $content = file_get_contents($filePath);

        return new ReadResourceResult([
            new TextResourceContents(
                text: $content,
                uri: $uri,
                mimeType: 'text/markdown',
            ),
        ]);
    }
}
