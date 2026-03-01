<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class AdminPageTest extends TestCase
{
    // ── droip_bridge_extract_version ───────────────────────────────────

    public function testExtractVersionFromPath(): void
    {
        $path = '/Library/Application Support/Local/lightning-services/php-8.4.10+0/bin/darwin-arm64/bin/php';
        $this->assertSame('8.4.10', droip_bridge_extract_version($path));
    }

    public function testExtractVersionDifferentVersion(): void
    {
        $path = '/some/path/php-7.4.33/bin/php';
        $this->assertSame('7.4.33', droip_bridge_extract_version($path));
    }

    public function testExtractVersionNoMatch(): void
    {
        $path = '/usr/bin/php';
        $this->assertSame('0.0.0', droip_bridge_extract_version($path));
    }

    public function testExtractVersionEmptyString(): void
    {
        $this->assertSame('0.0.0', droip_bridge_extract_version(''));
    }

    // ── droip_bridge_generate_mcp_config ──────────────────────────────

    public function testGenerateMcpConfigStructure(): void
    {
        $config = droip_bridge_generate_mcp_config();

        $this->assertArrayHasKey('mcpServers', $config);
        $this->assertArrayHasKey('droip-bridge', $config['mcpServers']);

        $server = $config['mcpServers']['droip-bridge'];
        $this->assertArrayHasKey('command', $server);
        $this->assertArrayHasKey('args', $server);
        $this->assertArrayHasKey('env', $server);
    }

    public function testGenerateMcpConfigHasServerPath(): void
    {
        $config = droip_bridge_generate_mcp_config();
        $args = $config['mcpServers']['droip-bridge']['args'];

        // Last arg should be the server.php path
        $lastArg = end($args);
        $this->assertStringContainsString('server.php', $lastArg);
    }

    public function testGenerateMcpConfigHasWpRoot(): void
    {
        $config = droip_bridge_generate_mcp_config();
        $env = $config['mcpServers']['droip-bridge']['env'];
        $this->assertArrayHasKey('WP_ROOT_PATH', $env);
    }

    public function testGenerateMcpConfigHasCommand(): void
    {
        $config = droip_bridge_generate_mcp_config();
        $command = $config['mcpServers']['droip-bridge']['command'];
        $this->assertNotEmpty($command);
    }

    // ── droip_bridge_detect_php_binary ─────────────────────────────────

    public function testDetectPhpBinaryReturnsString(): void
    {
        $result = droip_bridge_detect_php_binary();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ── droip_bridge_detect_mysql_socket ───────────────────────────────

    public function testDetectMysqlSocketReturnsString(): void
    {
        $result = droip_bridge_detect_mysql_socket();
        $this->assertIsString($result);
    }
}
