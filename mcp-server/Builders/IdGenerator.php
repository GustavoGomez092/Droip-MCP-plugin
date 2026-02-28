<?php
/**
 * Unique ID generation matching Droip's ID patterns.
 *
 * Droip uses short alphanumeric IDs like "dp7azz8i" (8 chars, prefixed with "dp").
 * Style block IDs follow the pattern "planzo_dp..." or similar prefix + ID.
 *
 * @package DroipBridge\Builders
 */

declare(strict_types=1);

namespace DroipBridge\Builders;

class IdGenerator
{
    private const CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Generate a Droip-style element ID (e.g., "dp7azz8i").
     */
    public static function elementId(int $length = 6): string
    {
        return 'dp' . self::randomString($length);
    }

    /**
     * Generate a Droip-style style block ID (e.g., "planzo_dp3vqhil").
     */
    public static function styleId(string $prefix = 'mcpbr', int $length = 6): string
    {
        return $prefix . '_dp' . self::randomString($length);
    }

    /**
     * Generate a symbolElPropId (e.g., "sepj9r513").
     */
    public static function symbolElPropId(int $length = 7): string
    {
        return 'sep' . self::randomString($length);
    }

    /**
     * Generate a batch of element IDs.
     *
     * @return string[]
     */
    public static function elementBatch(int $count, int $length = 6): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = self::elementId($length);
        }
        return $ids;
    }

    /**
     * Generate a batch of style IDs.
     *
     * @return string[]
     */
    public static function styleBatch(int $count, string $prefix = 'mcpbr', int $length = 6): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = self::styleId($prefix, $length);
        }
        return $ids;
    }

    private static function randomString(int $length): string
    {
        $result = '';
        $max = strlen(self::CHARS) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= self::CHARS[random_int(0, $max)];
        }
        return $result;
    }
}
