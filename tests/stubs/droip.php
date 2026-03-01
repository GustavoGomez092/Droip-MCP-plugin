<?php
/**
 * Droip class and constant stubs for unit testing.
 *
 * Provides mock implementations of Droip classes and defines
 * required Droip constants.
 */

// ── Droip Constants (global namespace) ─────────────────────────────────

namespace {
    if (!defined('DROIP_APP_PREFIX')) {
        define('DROIP_APP_PREFIX', 'droip');
    }

    if (!defined('DROIP_SYMBOL_TYPE')) {
        define('DROIP_SYMBOL_TYPE', 'droip_symbol');
    }

    if (!defined('DROIP_VERSION')) {
        define('DROIP_VERSION', '2.0.0');
    }

    if (!defined('DROIP_META_NAME_FOR_POST_EDITOR_MODE')) {
        define('DROIP_META_NAME_FOR_POST_EDITOR_MODE', 'droip_editor_mode');
    }

    if (!defined('DROIP_GLOBAL_STYLE_BLOCK_META_KEY')) {
        define('DROIP_GLOBAL_STYLE_BLOCK_META_KEY', 'droip_global_style_block');
    }

    if (!defined('DROIP_USER_SAVED_DATA_META_KEY')) {
        define('DROIP_USER_SAVED_DATA_META_KEY', 'droip_user_saved_data');
    }

    if (!defined('DROIP_USER_CUSTOM_FONTS_META_KEY')) {
        define('DROIP_USER_CUSTOM_FONTS_META_KEY', 'droip_user_custom_fonts');
    }

    if (!defined('DROIP_USER_CONTROLLER_META_KEY')) {
        define('DROIP_USER_CONTROLLER_META_KEY', 'droip_user_controller');
    }
}

// ── Droip\Ajax\Symbol stub ─────────────────────────────────────────────

namespace Droip\Ajax {
    class Symbol
    {
        /** @var array|null Return value for fetch_list() */
        public static ?array $fetchListReturn = null;

        /** @var array|null Return value for get_single_symbol() */
        public static ?array $getSingleReturn = null;

        /** @var array|null Return value for save_to_db() */
        public static ?array $saveReturn = null;

        /** @var array Tracks save_to_db() calls */
        public static array $saveCalls = [];

        public static function reset(): void
        {
            self::$fetchListReturn = null;
            self::$getSingleReturn = null;
            self::$saveReturn = null;
            self::$saveCalls = [];
        }

        public static function fetch_list(bool $includeData = true, bool $includeHtml = false): array
        {
            return self::$fetchListReturn ?? [];
        }

        public static function get_single_symbol(int $id, bool $includeData = true, bool $includeHtml = false): ?array
        {
            return self::$getSingleReturn;
        }

        public static function save_to_db(array $payload): ?array
        {
            self::$saveCalls[] = $payload;
            return self::$saveReturn;
        }
    }
}

// ── Droip\HelperFunctions stub ─────────────────────────────────────────

namespace Droip {
    class HelperFunctions
    {
        /** @var array<string, mixed> Return values by key */
        public static array $globalData = [];

        /** @var array<int, mixed> Page style blocks by page ID */
        public static array $pageStyleBlocks = [];

        public static function reset(): void
        {
            self::$globalData = [];
            self::$pageStyleBlocks = [];
        }

        public static function get_global_data_using_key(string $key): mixed
        {
            return self::$globalData[$key] ?? null;
        }

        public static function update_global_data_using_key(string $key, mixed $value): void
        {
            self::$globalData[$key] = $value;
        }

        public static function get_page_styleblocks(int $pageId): mixed
        {
            return self::$pageStyleBlocks[$pageId] ?? null;
        }

        public static function save_droip_data_to_db(int $postId, array $data): void
        {
            // no-op
        }
    }
}
