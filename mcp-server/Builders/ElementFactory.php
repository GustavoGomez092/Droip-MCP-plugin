<?php
/**
 * Creates properly-structured Droip element nodes.
 *
 * Every element in Droip's data map follows the same base structure.
 * This factory ensures consistency and correctness.
 *
 * @package DroipBridge\Builders
 */

declare(strict_types=1);

namespace DroipBridge\Builders;

class ElementFactory
{
    private const DEFAULT_STYLE_PANELS = [
        'typography'  => true,
        'composition' => true,
        'size'        => true,
        'background'  => true,
        'stroke'      => true,
        'shadow'      => true,
        'effects'     => true,
        'position'    => true,
        'transform'   => true,
        'interaction' => true,
        'animation'   => true,
    ];

    // ── Container Elements ───────────────────────────────────────────

    /**
     * Generic div/frame container.
     */
    public static function frame(string $id, ?string $parentId, array $opts = []): array
    {
        return self::baseElement($id, 'div', $parentId, [
            'tag' => $opts['tag'] ?? 'div',
        ], $opts);
    }

    /**
     * Section container (semantic <section>).
     */
    public static function section(string $id, ?string $parentId, string $title = 'Section', array $opts = []): array
    {
        $el = self::baseElement($id, 'section', $parentId, [
            'tag' => 'section',
        ], $opts);
        $el['title'] = $title;
        return $el;
    }

    // ── Text Elements ────────────────────────────────────────────────

    /**
     * Heading element (h1–h6).
     */
    public static function heading(string $id, ?string $parentId, string $content, string $tag = 'h2', array $opts = []): array
    {
        return self::baseElement($id, 'heading', $parentId, [
            'tag'      => $tag,
            'contents' => [$content],
        ], $opts);
    }

    /**
     * Paragraph element.
     */
    public static function paragraph(string $id, ?string $parentId, string $content, array $opts = []): array
    {
        return self::baseElement($id, 'paragraph', $parentId, [
            'tag'      => 'p',
            'contents' => [$content],
        ], $opts);
    }

    /**
     * Text link element.
     */
    public static function linkText(string $id, ?string $parentId, string $text, string $href, array $opts = []): array
    {
        return self::baseElement($id, 'link-text', $parentId, [
            'tag'        => 'a',
            'contents'   => [$text],
            'type'       => 'href',
            'isActive'   => false,
            'preload'    => 'default',
            'attributes' => [
                'href'   => $href,
                'target' => $opts['target'] ?? '',
            ],
        ], $opts);
    }

    // ── Interactive Elements ─────────────────────────────────────────

    /**
     * Button element.
     *
     * Droip's button renderer ignores `contents` and only renders children.
     * This method automatically creates a `text` child element to hold the
     * button label. The returned array includes a `_extra_elements` key with
     * the text element that must be added to the symbol data map.
     */
    public static function button(string $id, ?string $parentId, string $text, array $opts = []): array
    {
        $textId = $opts['textId'] ?? IdGenerator::elementId();

        $props = [
            'tag'      => 'button',
            'contents' => [$text],
        ];

        if (isset($opts['href'])) {
            $props['type'] = 'href';
            $props['attributes'] = [
                'href'   => $opts['href'],
                'target' => $opts['target'] ?? '',
            ];
        }

        // Button must have a text child for Droip's renderer
        $opts['children'] = array_merge($opts['children'] ?? [], [$textId]);

        $button = self::baseElement($id, 'button', $parentId, $props, $opts);

        // Create the text child element
        $textElement = self::baseElement($textId, 'text', $id, [
            'tag'      => 'span',
            'contents' => [$text],
        ]);

        // Attach extra elements for the caller to add to the data map
        $button['_extra_elements'] = [$textId => $textElement];

        return $button;
    }

    /**
     * Link block (clickable container).
     *
     * @param array $opts Options: 'linkType' ("href"|"page"), 'target', 'preload',
     *                     'dynamicContent' (array for dynamic links)
     */
    public static function linkBlock(string $id, ?string $parentId, string $href, array $opts = []): array
    {
        $props = [
            'tag'        => 'a',
            'type'       => $opts['linkType'] ?? 'href',
            'isActive'   => false,
            'preload'    => $opts['preload'] ?? 'default',
            'attributes' => [
                'href'   => $href,
                'target' => $opts['target'] ?? '',
            ],
        ];

        if (isset($opts['dynamicContent'])) {
            $props['dynamicContent'] = $opts['dynamicContent'];
        }

        return self::baseElement($id, 'link-block', $parentId, $props, $opts);
    }

    // ── Media Elements ───────────────────────────────────────────────

    /**
     * Image element.
     */
    public static function image(string $id, ?string $parentId, string $src, string $alt = '', array $opts = []): array
    {
        $props = [
            'tag'         => 'img',
            'noEndTag'    => true,
            'type'        => 'href',
            'load'        => $opts['load'] ?? 'lazy',
            'hiDPIStatus' => $opts['hiDPIStatus'] ?? false,
            'width'       => $opts['width'] ?? ['value' => '', 'unit' => 'auto'],
            'height'      => $opts['height'] ?? ['value' => '', 'unit' => 'auto'],
            'attributes'  => [
                'src'    => $src,
                'alt'    => $alt,
                'href'   => $opts['href'] ?? '',
                'target' => $opts['target'] ?? '',
            ],
        ];

        if (isset($opts['wp_attachment_id'])) {
            $props['wp_attachment_id'] = $opts['wp_attachment_id'];
        }

        return self::baseElement($id, 'image', $parentId, $props, $opts);
    }

    /**
     * Video element.
     */
    public static function video(string $id, ?string $parentId, string $src, array $opts = []): array
    {
        return self::baseElement($id, 'video', $parentId, [
            'tag'        => 'video',
            'attributes' => [
                'src'      => $src,
                'controls' => $opts['controls'] ?? true,
                'autoplay' => $opts['autoplay'] ?? false,
                'loop'     => $opts['loop'] ?? false,
                'muted'    => $opts['muted'] ?? false,
            ],
        ], $opts);
    }

    /**
     * SVG element (inline SVG).
     */
    public static function svg(string $id, ?string $parentId, string $svgOuterHtml, array $opts = []): array
    {
        return self::baseElement($id, 'svg', $parentId, [
            'tag'          => 'svg',
            'svgOuterHtml' => $svgOuterHtml,
        ], $opts);
    }

    /**
     * Icon element.
     */
    public static function icon(string $id, ?string $parentId, string $iconClass, array $opts = []): array
    {
        return self::baseElement($id, 'svg-icon', $parentId, [
            'tag'       => 'i',
            'iconClass' => $iconClass,
        ], $opts);
    }

    // ── Form Elements ────────────────────────────────────────────────

    /**
     * Form container.
     */
    public static function form(string $id, ?string $parentId, array $opts = []): array
    {
        return self::baseElement($id, 'form', $parentId, [
            'tag' => 'form',
        ], $opts);
    }

    /**
     * Input element.
     */
    public static function input(string $id, ?string $parentId, string $type, string $name, array $opts = []): array
    {
        return self::baseElement($id, 'input', $parentId, [
            'tag'        => 'input',
            'attributes' => [
                'type'        => $type,
                'name'        => $name,
                'placeholder' => $opts['placeholder'] ?? '',
            ],
        ], $opts);
    }

    /**
     * Textarea element.
     */
    public static function textarea(string $id, ?string $parentId, string $name, array $opts = []): array
    {
        return self::baseElement($id, 'textarea', $parentId, [
            'tag'        => 'textarea',
            'attributes' => [
                'name'        => $name,
                'placeholder' => $opts['placeholder'] ?? '',
            ],
        ], $opts);
    }

    /**
     * Select dropdown.
     */
    public static function select(string $id, ?string $parentId, string $name, array $options = [], array $opts = []): array
    {
        return self::baseElement($id, 'select', $parentId, [
            'tag'        => 'select',
            'attributes' => ['name' => $name],
            'options'    => $options,
        ], $opts);
    }

    // ── Advanced Elements ────────────────────────────────────────────

    /**
     * Custom HTML/code block.
     */
    public static function customCode(string $id, ?string $parentId, string $html, array $opts = []): array
    {
        return self::baseElement($id, 'custom-code', $parentId, [
            'tag'       => 'div',
            'content'   => $html,
            'data-type' => 'code',
        ], $opts);
    }

    /**
     * Symbol instance (reference to another symbol).
     */
    public static function symbolInstance(string $id, ?string $parentId, int $symbolId, array $opts = []): array
    {
        return self::baseElement($id, 'symbol', $parentId, [
            'tag'      => 'div',
            'symbolId' => $symbolId,
        ], $opts);
    }

    // ── Collection Elements ───────────────────────────────────────────

    /**
     * Collection — dynamic content repeater.
     *
     * @param array $dynamicContent Collection config: collectionType, items, pagination, etc.
     */
    public static function collection(string $id, ?string $parentId, array $dynamicContent, array $opts = []): array
    {
        $defaults = [
            'collectionType' => 'post',
            'items'          => '6',
            'pagination'     => true,
            'filters'        => [],
            'sorting'        => ['orderby' => 'date', 'order' => 'DESC'],
            'offset'         => '0',
            'taxonomy'       => new \stdClass(),
            'inherit'        => false,
        ];

        $props = [
            'tag'            => 'div',
            'dynamicContent' => array_merge($defaults, $dynamicContent),
            'uiState'        => $opts['uiState'] ?? ['open' => true],
        ];

        $opts['template_mounted'] = true;
        return self::baseElement($id, 'collection', $parentId, $props, $opts);
    }

    /**
     * Collection items wrapper (direct child of collection).
     */
    public static function collectionItems(string $id, ?string $parentId, array $opts = []): array
    {
        $opts['template_mounted'] = true;
        return self::baseElement($id, 'items', $parentId, [
            'tag' => 'div',
        ], $opts);
    }

    /**
     * Single collection item template (direct child of items).
     */
    public static function collectionItem(string $id, ?string $parentId, array $opts = []): array
    {
        $opts['template_mounted'] = true;
        return self::baseElement($id, 'item', $parentId, [
            'tag' => 'div',
        ], $opts);
    }

    /**
     * Pagination container (direct child of collection).
     */
    public static function pagination(string $id, ?string $parentId, array $opts = []): array
    {
        $opts['template_mounted'] = true;
        return self::baseElement($id, 'pagination', $parentId, [
            'tag'              => 'div',
            'componentType'    => 'pagination',
            'customAttributes' => ['data-droip-pagination' => ''],
        ], $opts);
    }

    /**
     * Pagination item wrapper (direct child of pagination).
     */
    public static function paginationItem(string $id, ?string $parentId, array $opts = []): array
    {
        $opts['template_mounted'] = true;
        return self::baseElement($id, 'pagination-item', $parentId, [
            'tag' => 'div',
        ], $opts);
    }

    /**
     * Pagination number display (direct child of pagination-item).
     */
    public static function paginationNumber(string $id, ?string $parentId, array $opts = []): array
    {
        $opts['template_mounted'] = true;
        return self::baseElement($id, 'pagination-number', $parentId, [
            'tag' => 'div',
        ], $opts);
    }

    /**
     * Empty state container — shown when collection has no results (direct child of collection).
     */
    public static function emptyState(string $id, ?string $parentId, array $opts = []): array
    {
        $opts['template_mounted'] = true;
        return self::baseElement($id, 'empty', $parentId, [
            'tag' => 'div',
        ], $opts);
    }

    // ── Dynamic Content Helper ────────────────────────────────────────

    /**
     * Attach dynamic content to an existing element array.
     *
     * @param array  $element The element array (from any factory method)
     * @param string $type    Dynamic content type: "post" or "author"
     * @param string $value   Field name: "post_title", "featured_image", etc.
     * @return array The element with dynamicContent added to properties
     */
    public static function withDynamicContent(array $element, string $type, string $value): array
    {
        $element['properties']['dynamicContent'] = [
            'type'  => $type,
            'value' => $value,
        ];
        $element['template_mounted'] = true;
        return $element;
    }

    // ── Base Builder ─────────────────────────────────────────────────

    /**
     * Build the base element structure that every Droip element shares.
     */
    private static function baseElement(
        string $id,
        string $name,
        ?string $parentId,
        array $properties = [],
        array $opts = []
    ): array {
        // Auto-generate symbolElPropId
        $properties['symbolElPropId'] = $opts['symbolElPropId'] ?? IdGenerator::symbolElPropId();

        // Merge any extra properties from opts
        if (isset($opts['properties'])) {
            $properties = array_merge($properties, $opts['properties']);
        }

        $element = [
            'visibility'  => $opts['visibility'] ?? true,
            'collapse'    => false,
            'name'        => $name,
            'title'       => $opts['title'] ?? ucfirst(str_replace('-', ' ', $name)),
            'properties'  => $properties,
            'styleIds'    => $opts['styleIds'] ?? [],
            'className'   => $opts['className'] ?? '',
            'source'      => 'droip',
            'id'          => $id,
            'parentId'    => $parentId,
            'stylePanels' => self::DEFAULT_STYLE_PANELS,
        ];

        // Only include 'children' when there are actual child IDs.
        // Droip's renderer uses isset($data['children']) to decide whether to
        // render text content (contents[]) or iterate child elements. An empty
        // children array on a text element prevents its contents from rendering.
        $children = $opts['children'] ?? [];
        if (!empty($children)) {
            $element['children'] = $children;
        }

        if (isset($opts['hide'])) {
            $element['hide'] = $opts['hide'];
        }

        if (isset($opts['template_mounted']) && $opts['template_mounted']) {
            $element['template_mounted'] = true;
        }

        return $element;
    }
}
