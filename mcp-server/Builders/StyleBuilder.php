<?php
/**
 * Creates Droip style block structures.
 *
 * Style blocks in Droip are keyed by ID and contain CSS rules per viewport
 * (responsive breakpoints). Each block has a "variant" map with keys like
 * "md" (desktop), "tablet", "mobile", "md_hover", etc.
 *
 * @package DroipBridge\Builders
 */

declare(strict_types=1);

namespace DroipBridge\Builders;

class StyleBuilder
{
    /**
     * Create a style block with raw CSS strings per variant.
     *
     * @param string $id   Style block ID (e.g., "mcpbr_dp3vqhil")
     * @param string $name Class name for the style block (e.g., "mcpbr_dplsdzbj")
     * @param string $css  Desktop CSS string (e.g., "display:flex;gap:12px;")
     */
    public static function create(string $id, string $name, string $css): array
    {
        return [
            'id'             => $id,
            'type'           => 'class',
            'name'           => $name,
            'variant'        => ['md' => $css],
            'isGlobal'       => true,
            'isSymbolStyle'  => true,
        ];
    }

    /**
     * Create a style block from an associative array of CSS properties.
     *
     * @param string $id         Style block ID
     * @param string $name       Class name
     * @param array  $properties Associative array (e.g., ['display' => 'flex', 'gap' => '12px'])
     */
    public static function fromArray(string $id, string $name, array $properties): array
    {
        $css = self::arrayToCss($properties);
        return self::create($id, $name, $css);
    }

    /**
     * Create a responsive style block with per-viewport CSS.
     *
     * @param string $id      Style block ID
     * @param string $name    Class name
     * @param array  $desktop Desktop CSS properties
     * @param array  $tablet  Tablet CSS properties (≤991px)
     * @param array  $mobile  Mobile CSS properties (≤575px)
     */
    public static function responsive(
        string $id,
        string $name,
        array $desktop,
        array $tablet = [],
        array $mobile = []
    ): array {
        $variant = ['md' => self::arrayToCss($desktop)];

        if (!empty($tablet)) {
            $variant['tablet'] = self::arrayToCss($tablet);
        }
        if (!empty($mobile)) {
            $variant['mobile'] = self::arrayToCss($mobile);
        }

        return [
            'id'             => $id,
            'type'           => 'class',
            'name'           => $name,
            'variant'        => $variant,
            'isGlobal'       => true,
            'isSymbolStyle'  => true,
        ];
    }

    /**
     * Create a style block with hover state.
     *
     * @param string $id      Style block ID
     * @param string $name    Class name
     * @param array  $normal  Normal state CSS properties
     * @param array  $hover   Hover state CSS properties
     */
    public static function withHover(
        string $id,
        string $name,
        array $normal,
        array $hover
    ): array {
        return [
            'id'             => $id,
            'type'           => 'class',
            'name'           => $name,
            'variant'        => [
                'md'       => self::arrayToCss($normal),
                'md_hover' => self::arrayToCss($hover),
            ],
            'isGlobal'       => true,
            'isSymbolStyle'  => true,
        ];
    }

    /**
     * Convert an associative array of CSS properties to a CSS string.
     */
    private static function arrayToCss(array $properties): string
    {
        $parts = [];
        foreach ($properties as $property => $value) {
            $parts[] = "{$property}:{$value}";
        }
        return implode(';', $parts) . ';';
    }
}
