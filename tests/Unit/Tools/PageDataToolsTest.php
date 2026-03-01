<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Tools;

use DroipBridge\Tools\PageDataTools;
use Droip\HelperFunctions;
use Mcp\Types\CallToolResult;
use PHPUnit\Framework\TestCase;

class PageDataToolsTest extends TestCase
{
    protected function setUp(): void
    {
        \WPMocks::reset();
        HelperFunctions::reset();
    }

    // ── getTools ───────────────────────────────────────────────────────

    public function testGetToolsReturns4Tools(): void
    {
        $tools = PageDataTools::getTools();
        $this->assertCount(4, $tools);
    }

    public function testGetToolsHasCorrectNames(): void
    {
        $tools = PageDataTools::getTools();
        $names = array_map(fn($t) => $t->name, $tools);
        $this->assertContains('droip_list_pages', $names);
        $this->assertContains('droip_get_page_data', $names);
        $this->assertContains('droip_get_global_styles', $names);
        $this->assertContains('droip_get_variables', $names);
    }

    // ── handleListPages ───────────────────────────────────────────────

    public function testListPagesReturnsEmptyArray(): void
    {
        \WPMocks::$postsList = [];
        $result = PageDataTools::handleListPages([]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function testListPagesReturnsPages(): void
    {
        \WPMocks::$postsList = [
            \WPMocks::createPost(10, 'page', ['post_title' => 'Home', 'post_name' => 'home']),
            \WPMocks::createPost(20, 'page', ['post_title' => 'About', 'post_name' => 'about']),
        ];
        \WPMocks::$postMeta[10][DROIP_APP_PREFIX] = ['some' => 'data'];
        \WPMocks::$postMeta[10][DROIP_META_NAME_FOR_POST_EDITOR_MODE] = 'droip';

        $result = PageDataTools::handleListPages([]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertCount(2, $data);

        $this->assertSame(10, $data[0]['id']);
        $this->assertSame('Home', $data[0]['title']);
        $this->assertSame('home', $data[0]['slug']);
        $this->assertSame('publish', $data[0]['status']);
        $this->assertTrue($data[0]['has_droip_data']);
        $this->assertSame('droip', $data[0]['editor_mode']);
    }

    public function testListPagesWithNoDroipData(): void
    {
        \WPMocks::$postsList = [
            \WPMocks::createPost(10, 'page'),
        ];

        $result = PageDataTools::handleListPages([]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertFalse($data[0]['has_droip_data']);
        $this->assertSame('none', $data[0]['editor_mode']);
    }

    public function testListPagesDefaultPostType(): void
    {
        \WPMocks::$postsList = [];
        $result = PageDataTools::handleListPages([]);
        // Just verify it doesn't crash with default
        $this->assertInstanceOf(CallToolResult::class, $result);
    }

    public function testListPagesCustomPostType(): void
    {
        \WPMocks::$postsList = [];
        $result = PageDataTools::handleListPages(['post_type' => 'post']);
        $this->assertInstanceOf(CallToolResult::class, $result);
    }

    // ── handleGetPageData ─────────────────────────────────────────────

    public function testGetPageDataInvalidId(): void
    {
        $result = PageDataTools::handleGetPageData(['page_id' => 0]);
        $this->assertTrue($result->isError);
    }

    public function testGetPageDataMissingId(): void
    {
        $result = PageDataTools::handleGetPageData([]);
        $this->assertTrue($result->isError);
    }

    public function testGetPageDataNotFound(): void
    {
        $result = PageDataTools::handleGetPageData(['page_id' => 999]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('not found', $result->content[0]->text);
    }

    public function testGetPageDataNoDroipData(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page', ['post_title' => 'Home']);
        // No droip meta

        $result = PageDataTools::handleGetPageData(['page_id' => 10]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertFalse($data['has_data']);
        $this->assertStringContainsString('no Droip data', $data['message']);
    }

    public function testGetPageDataWithData(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page', ['post_title' => 'Home']);
        \WPMocks::$postMeta[10][DROIP_APP_PREFIX] = [
            'dproot' => ['id' => 'dproot', 'name' => 'div'],
        ];
        HelperFunctions::$pageStyleBlocks[10] = [
            'sb1' => ['id' => 'sb1', 'variant' => ['md' => 'color:red;']],
        ];

        $result = PageDataTools::handleGetPageData(['page_id' => 10]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertSame(10, $data['page_id']);
        $this->assertSame('Home', $data['title']);
        $this->assertArrayHasKey('blocks', $data);
        $this->assertArrayHasKey('styleBlocks', $data);
    }

    public function testGetPageDataEmptyStyleBlocks(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page');
        \WPMocks::$postMeta[10][DROIP_APP_PREFIX] = ['dproot' => ['id' => 'dproot']];
        // No style blocks

        $result = PageDataTools::handleGetPageData(['page_id' => 10]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertSame([], $data['styleBlocks']);
    }

    // ── handleGetGlobalStyles ─────────────────────────────────────────

    public function testGetGlobalStylesEmpty(): void
    {
        $result = PageDataTools::handleGetGlobalStyles();
        $this->assertStringContainsString('No global style blocks', $result->content[0]->text);
    }

    public function testGetGlobalStylesWithData(): void
    {
        HelperFunctions::$globalData[DROIP_GLOBAL_STYLE_BLOCK_META_KEY] = [
            'sb1' => ['id' => 'sb1', 'variant' => ['md' => 'body{margin:0;}']],
        ];

        $result = PageDataTools::handleGetGlobalStyles();
        $data = json_decode($result->content[0]->text, true);
        $this->assertArrayHasKey('sb1', $data);
    }

    // ── handleGetVariables ────────────────────────────────────────────

    public function testGetVariablesEmpty(): void
    {
        $result = PageDataTools::handleGetVariables();
        $data = json_decode($result->content[0]->text, true);
        $this->assertSame([], $data['user_saved_data']);
        $this->assertArrayNotHasKey('custom_fonts', $data);
        $this->assertArrayNotHasKey('viewports', $data);
    }

    public function testGetVariablesWithSavedData(): void
    {
        HelperFunctions::$globalData[DROIP_USER_SAVED_DATA_META_KEY] = [
            'colors' => ['primary' => '#000'],
        ];

        $result = PageDataTools::handleGetVariables();
        $data = json_decode($result->content[0]->text, true);
        $this->assertSame(['colors' => ['primary' => '#000']], $data['user_saved_data']);
    }

    public function testGetVariablesWithFonts(): void
    {
        HelperFunctions::$globalData[DROIP_USER_CUSTOM_FONTS_META_KEY] = [
            'Inter' => ['family' => 'Inter'],
        ];

        $result = PageDataTools::handleGetVariables();
        $data = json_decode($result->content[0]->text, true);
        $this->assertArrayHasKey('custom_fonts', $data);
    }

    public function testGetVariablesWithViewports(): void
    {
        HelperFunctions::$globalData[DROIP_USER_CONTROLLER_META_KEY] = [
            'tablet' => 991, 'mobile' => 575,
        ];

        $result = PageDataTools::handleGetVariables();
        $data = json_decode($result->content[0]->text, true);
        $this->assertArrayHasKey('viewports', $data);
    }

    public function testGetVariablesWithAllData(): void
    {
        HelperFunctions::$globalData[DROIP_USER_SAVED_DATA_META_KEY] = ['colors' => []];
        HelperFunctions::$globalData[DROIP_USER_CUSTOM_FONTS_META_KEY] = ['Inter' => []];
        HelperFunctions::$globalData[DROIP_USER_CONTROLLER_META_KEY] = ['tablet' => 991];

        $result = PageDataTools::handleGetVariables();
        $data = json_decode($result->content[0]->text, true);
        $this->assertArrayHasKey('user_saved_data', $data);
        $this->assertArrayHasKey('custom_fonts', $data);
        $this->assertArrayHasKey('viewports', $data);
    }
}
