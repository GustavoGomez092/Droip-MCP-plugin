<?php
/**
 * Validates Droip symbol JSON before saving.
 *
 * Checks structural integrity: required fields, valid parent-child references,
 * style block references, and element type validity.
 *
 * @package DroipBridge\Validators
 */

declare(strict_types=1);

namespace DroipBridge\Validators;

class SymbolValidator
{
    private const VALID_ELEMENT_NAMES = [
        'div', 'section', 'heading', 'paragraph', 'button', 'link-block',
        'link-text', 'image', 'video', 'svg', 'svg-icon', 'form', 'input',
        'textarea', 'select', 'radio-button', 'radio-group', 'checkbox-element',
        'custom-code', 'symbol', 'collection', 'slider', 'slider_mask',
        'items', 'loading', 'map', 'file-upload', 'file-upload-inner',
        'file-upload-threshold-text',
    ];

    /**
     * Validate a symbol data structure.
     *
     * @param array $symbolData The symbol data (the inner 'symbolData' structure)
     * @return array{valid: bool, errors: string[], warnings: string[]}
     */
    public static function validate(array $symbolData): array
    {
        $errors = [];
        $warnings = [];

        // Required top-level fields
        foreach (['name', 'root', 'data', 'styleBlocks'] as $field) {
            if (!isset($symbolData[$field])) {
                $errors[] = "Missing required field: '{$field}'";
            }
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $root = $symbolData['root'];
        $data = $symbolData['data'];
        $styleBlocks = $symbolData['styleBlocks'];

        // Root element must exist in data
        if (!isset($data[$root])) {
            $errors[] = "Root element '{$root}' not found in data map";
        }

        // Root element should have null parentId
        if (isset($data[$root]) && $data[$root]['parentId'] !== null) {
            $warnings[] = "Root element should have parentId = null";
        }

        // Validate all elements
        $allIds = array_keys($data);
        $referencedAsChild = [];

        foreach ($data as $elementId => $element) {
            // Check element has required fields
            foreach (['name', 'properties', 'id'] as $field) {
                if (!isset($element[$field])) {
                    $errors[] = "Element '{$elementId}' missing required field: '{$field}'";
                }
            }

            // Validate element ID matches key
            if (isset($element['id']) && $element['id'] !== $elementId) {
                $errors[] = "Element key '{$elementId}' does not match element id '{$element['id']}'";
            }

            // Validate element type
            if (isset($element['name']) && !in_array($element['name'], self::VALID_ELEMENT_NAMES, true)) {
                $warnings[] = "Element '{$elementId}' has unknown type '{$element['name']}' — this may be a custom element";
            }

            // Validate parentId references
            if (isset($element['parentId']) && $element['parentId'] !== null) {
                if (!isset($data[$element['parentId']])) {
                    $errors[] = "Element '{$elementId}' references non-existent parent '{$element['parentId']}'";
                }
            }

            // Validate children references
            if (isset($element['children']) && is_array($element['children'])) {
                foreach ($element['children'] as $childId) {
                    if (!isset($data[$childId])) {
                        $errors[] = "Element '{$elementId}' references non-existent child '{$childId}'";
                    }
                    $referencedAsChild[$childId] = true;
                }
            }

            // Validate styleIds reference existing style blocks
            if (isset($element['styleIds']) && is_array($element['styleIds'])) {
                foreach ($element['styleIds'] as $styleId) {
                    if (!isset($styleBlocks[$styleId])) {
                        $warnings[] = "Element '{$elementId}' references non-existent style block '{$styleId}'";
                    }
                }
            }
        }

        // Check for orphaned elements (not root, not referenced as child)
        foreach ($allIds as $id) {
            if ($id !== $root && !isset($referencedAsChild[$id])) {
                $warnings[] = "Element '{$id}' is orphaned — not referenced as a child of any element";
            }
        }

        // Validate style blocks
        foreach ($styleBlocks as $sbId => $sb) {
            if (!isset($sb['id'])) {
                $errors[] = "Style block '{$sbId}' missing 'id' field";
            }
            if (!isset($sb['variant']) || !is_array($sb['variant'])) {
                $errors[] = "Style block '{$sbId}' missing or invalid 'variant' field";
            }
            if (isset($sb['variant']['md']) && !is_string($sb['variant']['md'])) {
                $errors[] = "Style block '{$sbId}' variant 'md' must be a CSS string";
            }
        }

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }
}
