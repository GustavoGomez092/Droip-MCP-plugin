<?php
/**
 * WordPress function stubs for unit testing.
 *
 * Provides mock implementations of WordPress functions that the plugin uses.
 * Tests configure return values via WPMocks static properties.
 */

/**
 * Central mock store for WordPress function return values.
 */
class WPMocks
{
    /** @var array<string, mixed> Options store (key => value) */
    public static array $options = [];

    /** @var array<int, array<string, mixed>> Post meta store (post_id => [key => value]) */
    public static array $postMeta = [];

    /** @var array<int, object|null> Posts store (post_id => WP_Post-like object) */
    public static array $posts = [];

    /** @var array Post list for get_posts() */
    public static array $postsList = [];

    /** @var array<int, bool> wp_delete_post results */
    public static array $deleteResults = [];

    /** @var bool is_plugin_active return value */
    public static bool $pluginActive = true;

    /** @var bool update_post_meta return value */
    public static bool $updateMetaResult = true;

    /** @var array Tracks all add_action calls */
    public static array $actions = [];

    /** @var array Tracks all enqueued scripts */
    public static array $enqueuedScripts = [];

    /** @var array Tracks all localized scripts */
    public static array $localizedScripts = [];

    public static function reset(): void
    {
        self::$options = [];
        self::$postMeta = [];
        self::$posts = [];
        self::$postsList = [];
        self::$deleteResults = [];
        self::$pluginActive = true;
        self::$updateMetaResult = true;
        self::$actions = [];
        self::$enqueuedScripts = [];
        self::$localizedScripts = [];
    }

    /**
     * Create a mock WP_Post-like object.
     */
    public static function createPost(int $id, string $type = 'page', array $data = []): object
    {
        return (object) array_merge([
            'ID'          => $id,
            'post_type'   => $type,
            'post_title'  => "Post {$id}",
            'post_name'   => "post-{$id}",
            'post_status' => 'publish',
        ], $data);
    }
}

// ── WordPress Function Stubs ───────────────────────────────────────────

if (!function_exists('get_option')) {
    function get_option(string $key, mixed $default = false): mixed
    {
        return array_key_exists($key, WPMocks::$options)
            ? WPMocks::$options[$key]
            : $default;
    }
}

if (!function_exists('add_option')) {
    function add_option(string $key, mixed $value = ''): bool
    {
        WPMocks::$options[$key] = $value;
        return true;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $key, mixed $value): bool
    {
        WPMocks::$options[$key] = $value;
        return true;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $postId, string $key = '', bool $single = false): mixed
    {
        if ($key === '') {
            return WPMocks::$postMeta[$postId] ?? [];
        }
        $value = WPMocks::$postMeta[$postId][$key] ?? null;
        if ($single) {
            return $value ?? '';
        }
        return $value !== null ? [$value] : [];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $postId, string $key, mixed $value): bool
    {
        if (!isset(WPMocks::$postMeta[$postId])) {
            WPMocks::$postMeta[$postId] = [];
        }
        WPMocks::$postMeta[$postId][$key] = $value;
        return WPMocks::$updateMetaResult;
    }
}

if (!function_exists('get_post')) {
    function get_post(int $postId): ?object
    {
        return WPMocks::$posts[$postId] ?? null;
    }
}

if (!function_exists('get_posts')) {
    function get_posts(array $args = []): array
    {
        return WPMocks::$postsList;
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post(int $postId, bool $forceDelete = false): mixed
    {
        if (isset(WPMocks::$deleteResults[$postId])) {
            return WPMocks::$deleteResults[$postId]
                ? WPMocks::$posts[$postId] ?? (object) ['ID' => $postId]
                : false;
        }
        return WPMocks::$posts[$postId] ?? false;
    }
}

if (!function_exists('is_plugin_active')) {
    function is_plugin_active(string $plugin): bool
    {
        return WPMocks::$pluginActive;
    }
}

if (!function_exists('deactivate_plugins')) {
    function deactivate_plugins(string $plugin): void
    {
        // no-op
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename(string $file): string
    {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('wp_die')) {
    function wp_die(string $message = '', string $title = '', array $args = []): void
    {
        throw new \RuntimeException($message);
    }
}

if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        WPMocks::$actions[] = ['tag' => $tag, 'callback' => $callback, 'priority' => $priority];
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, bool $inFooter = false): void
    {
        WPMocks::$enqueuedScripts[] = compact('handle', 'src', 'deps', 'ver', 'inFooter');
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $objectName, array $data): bool
    {
        WPMocks::$localizedScripts[] = compact('handle', 'objectName', 'data');
        return true;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(string $file, callable $callback): void
    {
        // no-op
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return false;
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string
    {
        return trailingslashit(dirname($file));
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url(string $file): string
    {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $value): string
    {
        return rtrim($value, '/\\') . '/';
    }
}
