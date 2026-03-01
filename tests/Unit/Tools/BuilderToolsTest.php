<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Tools;

use DroipBridge\Tools\BuilderTools;
use Mcp\Types\CallToolResult;
use PHPUnit\Framework\TestCase;

class BuilderToolsTest extends TestCase
{
    protected function setUp(): void
    {
        \WPMocks::reset();
    }

    // ── getTools ───────────────────────────────────────────────────────

    public function testGetToolsReturns3Tools(): void
    {
        $tools = BuilderTools::getTools();
        $this->assertCount(3, $tools);
    }

    public function testGetToolsHasCorrectNames(): void
    {
        $tools = BuilderTools::getTools();
        $names = array_map(fn($t) => $t->name, $tools);
        $this->assertContains('droip_validate_symbol', $names);
        $this->assertContains('droip_generate_ids', $names);
        $this->assertContains('droip_add_symbol_to_page', $names);
    }

    // ── handleValidate ────────────────────────────────────────────────

    public function testValidateSuccess(): void
    {
        $symbolData = [
            'name' => 'Test', 'root' => 'dp1',
            'data' => [
                'dp1' => [
                    'id' => 'dp1', 'name' => 'section', 'parentId' => null,
                    'properties' => ['tag' => 'section'], 'styleIds' => [],
                ],
            ],
            'styleBlocks' => [],
        ];

        $result = BuilderTools::handleValidate(['symbol_data' => $symbolData]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertTrue($data['valid']);
    }

    public function testValidateFailure(): void
    {
        $symbolData = ['name' => 'Test']; // Missing required fields

        $result = BuilderTools::handleValidate(['symbol_data' => $symbolData]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertFalse($data['valid']);
        $this->assertNotEmpty($data['errors']);
    }

    public function testValidateMissingArgs(): void
    {
        $result = BuilderTools::handleValidate([]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('symbol_data', $result->content[0]->text);
    }

    public function testValidateEmptySymbolData(): void
    {
        $result = BuilderTools::handleValidate(['symbol_data' => []]);
        $this->assertTrue($result->isError);
    }

    // ── handleGenerateIds ─────────────────────────────────────────────

    public function testGenerateIdsDefaultElement(): void
    {
        $result = BuilderTools::handleGenerateIds([]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertCount(1, $data['ids']);
        $this->assertSame('element', $data['type']);
        $this->assertStringStartsWith('dp', $data['ids'][0]);
    }

    public function testGenerateIdsMultiple(): void
    {
        $result = BuilderTools::handleGenerateIds(['count' => 5]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertCount(5, $data['ids']);
    }

    public function testGenerateIdsStyleType(): void
    {
        $result = BuilderTools::handleGenerateIds(['type' => 'style', 'count' => 3]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertSame('style', $data['type']);
        $this->assertCount(3, $data['ids']);
        foreach ($data['ids'] as $id) {
            $this->assertStringStartsWith('mcpbr_dp', $id);
        }
    }

    public function testGenerateIdsMaxLimit(): void
    {
        $result = BuilderTools::handleGenerateIds(['count' => 101]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('Maximum 100', $result->content[0]->text);
    }

    public function testGenerateIdsMinimumOne(): void
    {
        $result = BuilderTools::handleGenerateIds(['count' => 0]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertCount(1, $data['ids']); // min(1, 0) = 1
    }

    public function testGenerateIdsNegativeCount(): void
    {
        $result = BuilderTools::handleGenerateIds(['count' => -5]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertCount(1, $data['ids']); // max(1, -5) = 1
    }

    // ── handleAddSymbolToPage ─────────────────────────────────────────

    public function testAddSymbolToPageSuccess(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page');
        \WPMocks::$posts[42] = \WPMocks::createPost(42, DROIP_SYMBOL_TYPE);
        \WPMocks::$postMeta[10][DROIP_APP_PREFIX] = [
            'dproot' => [
                'id' => 'dproot', 'name' => 'div', 'parentId' => null,
                'children' => [],
            ],
        ];

        $result = BuilderTools::handleAddSymbolToPage([
            'page_id'           => 10,
            'symbol_id'         => 42,
            'parent_element_id' => 'dproot',
        ]);

        $data = json_decode($result->content[0]->text, true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['element_id']);

        // Verify the page data was updated
        $updatedBlocks = \WPMocks::$postMeta[10][DROIP_APP_PREFIX];
        $this->assertArrayHasKey($data['element_id'], $updatedBlocks);
        $this->assertContains($data['element_id'], $updatedBlocks['dproot']['children']);
    }

    public function testAddSymbolToPageWithPosition(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page');
        \WPMocks::$posts[42] = \WPMocks::createPost(42, DROIP_SYMBOL_TYPE);
        \WPMocks::$postMeta[10][DROIP_APP_PREFIX] = [
            'dproot' => [
                'id' => 'dproot', 'name' => 'div', 'parentId' => null,
                'children' => ['dpexisting1', 'dpexisting2'],
            ],
        ];

        $result = BuilderTools::handleAddSymbolToPage([
            'page_id'           => 10,
            'symbol_id'         => 42,
            'parent_element_id' => 'dproot',
            'position'          => 1,
        ]);

        $updatedBlocks = \WPMocks::$postMeta[10][DROIP_APP_PREFIX];
        $children = $updatedBlocks['dproot']['children'];
        $this->assertSame('dpexisting1', $children[0]);
        // New element at position 1
        $this->assertStringStartsWith('dp', $children[1]);
        $this->assertSame('dpexisting2', $children[2]);
    }

    public function testAddSymbolToPageMissingArgs(): void
    {
        $result = BuilderTools::handleAddSymbolToPage([]);
        $this->assertTrue($result->isError);
    }

    public function testAddSymbolToPageInvalidPageId(): void
    {
        $result = BuilderTools::handleAddSymbolToPage([
            'page_id' => 0, 'symbol_id' => 42, 'parent_element_id' => 'dproot',
        ]);
        $this->assertTrue($result->isError);
    }

    public function testAddSymbolToPageNotFound(): void
    {
        $result = BuilderTools::handleAddSymbolToPage([
            'page_id' => 999, 'symbol_id' => 42, 'parent_element_id' => 'dproot',
        ]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('Page', $result->content[0]->text);
    }

    public function testAddSymbolToPageSymbolNotFound(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page');
        // Symbol doesn't exist

        $result = BuilderTools::handleAddSymbolToPage([
            'page_id' => 10, 'symbol_id' => 999, 'parent_element_id' => 'dproot',
        ]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('Symbol', $result->content[0]->text);
    }

    public function testAddSymbolToPageWrongPostType(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page');
        \WPMocks::$posts[42] = \WPMocks::createPost(42, 'page'); // Not a symbol

        $result = BuilderTools::handleAddSymbolToPage([
            'page_id' => 10, 'symbol_id' => 42, 'parent_element_id' => 'dproot',
        ]);
        $this->assertTrue($result->isError);
    }

    public function testAddSymbolToPageNoDroipData(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page');
        \WPMocks::$posts[42] = \WPMocks::createPost(42, DROIP_SYMBOL_TYPE);
        // No droip meta for page

        $result = BuilderTools::handleAddSymbolToPage([
            'page_id' => 10, 'symbol_id' => 42, 'parent_element_id' => 'dproot',
        ]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('no Droip data', $result->content[0]->text);
    }

    public function testAddSymbolToPageParentNotFound(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page');
        \WPMocks::$posts[42] = \WPMocks::createPost(42, DROIP_SYMBOL_TYPE);
        \WPMocks::$postMeta[10][DROIP_APP_PREFIX] = [
            'dproot' => ['id' => 'dproot', 'children' => []],
        ];

        $result = BuilderTools::handleAddSymbolToPage([
            'page_id' => 10, 'symbol_id' => 42, 'parent_element_id' => 'nonexistent',
        ]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('Parent element', $result->content[0]->text);
    }

    public function testAddSymbolToPageCreatesSymbolInstance(): void
    {
        \WPMocks::$posts[10] = \WPMocks::createPost(10, 'page');
        \WPMocks::$posts[42] = \WPMocks::createPost(42, DROIP_SYMBOL_TYPE);
        \WPMocks::$postMeta[10][DROIP_APP_PREFIX] = [
            'dproot' => ['id' => 'dproot', 'children' => []],
        ];

        $result = BuilderTools::handleAddSymbolToPage([
            'page_id' => 10, 'symbol_id' => 42, 'parent_element_id' => 'dproot',
        ]);

        $data = json_decode($result->content[0]->text, true);
        $elementId = $data['element_id'];
        $updatedBlocks = \WPMocks::$postMeta[10][DROIP_APP_PREFIX];
        $instanceEl = $updatedBlocks[$elementId];

        $this->assertSame('symbol', $instanceEl['name']);
        $this->assertSame(42, $instanceEl['properties']['symbolId']);
        $this->assertSame('dproot', $instanceEl['parentId']);
    }
}
