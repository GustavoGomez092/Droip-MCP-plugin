<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Tools;

use DroipBridge\Tools\SymbolCrudTools;
use Droip\Ajax\Symbol;
use Mcp\Types\CallToolResult;
use PHPUnit\Framework\TestCase;

class SymbolCrudToolsTest extends TestCase
{
    protected function setUp(): void
    {
        Symbol::reset();
        \WPMocks::reset();
    }

    private function validCreateArgs(): array
    {
        return [
            'name'        => 'Test Symbol',
            'category'    => 'Sections',
            'data'        => [
                'dproot1' => [
                    'id'         => 'dproot1',
                    'name'       => 'section',
                    'parentId'   => null,
                    'children'   => ['dpchild1'],
                    'properties' => ['tag' => 'section', 'symbolElPropId' => 'sep1234567'],
                    'styleIds'   => ['style1'],
                    'className'  => '',
                    'source'     => 'droip',
                    'visibility' => true,
                    'collapse'   => false,
                    'stylePanels' => ['typography' => true],
                ],
                'dpchild1' => [
                    'id'         => 'dpchild1',
                    'name'       => 'heading',
                    'parentId'   => 'dproot1',
                    'properties' => ['tag' => 'h2', 'contents' => ['Hello'], 'symbolElPropId' => 'sep7654321'],
                    'styleIds'   => [],
                    'className'  => '',
                    'source'     => 'droip',
                    'visibility' => true,
                    'collapse'   => false,
                    'stylePanels' => ['typography' => true],
                ],
            ],
            'styleBlocks' => [
                'style1' => [
                    'id'      => 'style1',
                    'type'    => 'class',
                    'name'    => 'style1',
                    'variant' => ['md' => 'display:flex;'],
                    'isGlobal'      => true,
                    'isSymbolStyle' => true,
                ],
            ],
        ];
    }

    // ── getTools ───────────────────────────────────────────────────────

    public function testGetToolsReturns5Tools(): void
    {
        $tools = SymbolCrudTools::getTools();
        $this->assertCount(5, $tools);
    }

    public function testGetToolsHasCorrectNames(): void
    {
        $tools = SymbolCrudTools::getTools();
        $names = array_map(fn($t) => $t->name, $tools);
        $this->assertContains('droip_create_symbol', $names);
        $this->assertContains('droip_list_symbols', $names);
        $this->assertContains('droip_get_symbol', $names);
        $this->assertContains('droip_update_symbol', $names);
        $this->assertContains('droip_delete_symbol', $names);
    }

    // ── handleCreate ──────────────────────────────────────────────────

    public function testCreateSuccess(): void
    {
        Symbol::$saveReturn = ['id' => 42, 'symbolData' => [], 'type' => 'droip_symbol', 'html' => ''];

        $result = SymbolCrudTools::handleCreate($this->validCreateArgs());
        $this->assertInstanceOf(CallToolResult::class, $result);

        $data = json_decode($result->content[0]->text, true);
        $this->assertTrue($data['success']);
        $this->assertSame(42, $data['id']);
        $this->assertSame('Test Symbol', $data['name']);
    }

    public function testCreateMissingName(): void
    {
        $args = $this->validCreateArgs();
        unset($args['name']);
        $result = SymbolCrudTools::handleCreate($args);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('name', $result->content[0]->text);
    }

    public function testCreateEmptyName(): void
    {
        $args = $this->validCreateArgs();
        $args['name'] = '';
        $result = SymbolCrudTools::handleCreate($args);
        $this->assertTrue($result->isError);
    }

    public function testCreateMissingData(): void
    {
        $result = SymbolCrudTools::handleCreate(['name' => 'Test']);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('data', $result->content[0]->text);
    }

    public function testCreateEmptyData(): void
    {
        $result = SymbolCrudTools::handleCreate(['name' => 'Test', 'data' => []]);
        $this->assertTrue($result->isError);
    }

    public function testCreateNoRootElement(): void
    {
        $args = [
            'name' => 'Test',
            'data' => [
                'dp1' => [
                    'id' => 'dp1', 'name' => 'div', 'parentId' => 'dpother',
                    'properties' => ['tag' => 'div'], 'styleIds' => [],
                ],
            ],
            'styleBlocks' => [],
        ];
        $result = SymbolCrudTools::handleCreate($args);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('root element', strtolower($result->content[0]->text));
    }

    public function testCreateValidationFailure(): void
    {
        $args = [
            'name' => 'Test',
            'data' => [
                'dproot' => [
                    'id' => 'dproot', 'name' => 'section', 'parentId' => null,
                    'children' => ['nonexistent'],
                    'properties' => ['tag' => 'section'], 'styleIds' => [],
                ],
            ],
            'styleBlocks' => [],
        ];
        $result = SymbolCrudTools::handleCreate($args);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('Validation failed', $result->content[0]->text);
    }

    public function testCreateSaveFailure(): void
    {
        Symbol::$saveReturn = null;

        $result = SymbolCrudTools::handleCreate($this->validCreateArgs());
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('Failed to save', $result->content[0]->text);
    }

    public function testCreateDefaultCategory(): void
    {
        Symbol::$saveReturn = ['id' => 1, 'symbolData' => [], 'type' => '', 'html' => ''];

        $args = $this->validCreateArgs();
        unset($args['category']);
        $result = SymbolCrudTools::handleCreate($args);

        $data = json_decode($result->content[0]->text, true);
        $this->assertSame('other', $data['category']);
    }

    public function testCreateCleansEmptyChildrenOnContentElements(): void
    {
        Symbol::$saveReturn = ['id' => 1, 'symbolData' => [], 'type' => '', 'html' => ''];

        $args = $this->validCreateArgs();
        // Add empty children to a heading (content element)
        $args['data']['dpchild1']['children'] = [];
        SymbolCrudTools::handleCreate($args);

        // Check the saved payload — cleanElementData should have removed empty children
        $saved = Symbol::$saveCalls[0]['symbolData'];
        $this->assertArrayNotHasKey('children', $saved['data']['dpchild1']);
    }

    public function testCreateAutoAddsTextChildToButton(): void
    {
        Symbol::$saveReturn = ['id' => 1, 'symbolData' => [], 'type' => '', 'html' => ''];

        $args = [
            'name' => 'Button Test',
            'data' => [
                'dproot' => [
                    'id' => 'dproot', 'name' => 'section', 'parentId' => null,
                    'children' => ['dpbtn'],
                    'properties' => ['tag' => 'section', 'symbolElPropId' => 'sep0000001'],
                    'styleIds' => [], 'className' => '', 'source' => 'droip',
                    'visibility' => true, 'collapse' => false, 'stylePanels' => ['typography' => true],
                ],
                'dpbtn' => [
                    'id' => 'dpbtn', 'name' => 'button', 'parentId' => 'dproot',
                    'properties' => ['tag' => 'button', 'contents' => ['Click Me'], 'symbolElPropId' => 'sep0000002'],
                    'styleIds' => [], 'className' => '', 'source' => 'droip',
                    'visibility' => true, 'collapse' => false, 'stylePanels' => ['typography' => true],
                ],
            ],
            'styleBlocks' => [],
        ];

        SymbolCrudTools::handleCreate($args);
        $saved = Symbol::$saveCalls[0]['symbolData'];

        // Button should now have a children array with a text element
        $this->assertNotEmpty($saved['data']['dpbtn']['children']);
        $textId = $saved['data']['dpbtn']['children'][0];
        $this->assertSame('text', $saved['data'][$textId]['name']);
        $this->assertSame('span', $saved['data'][$textId]['properties']['tag']);
        $this->assertSame(['Click Me'], $saved['data'][$textId]['properties']['contents']);
    }

    public function testUpdateFixesRootParentId(): void
    {
        // The root parentId fix in cleanElementData is reached via update,
        // where the existing symbolData already has a 'root' key.
        \WPMocks::$postMeta[42][DROIP_APP_PREFIX] = [
            'name' => 'Test', 'root' => 'dproot1', 'category' => 'other', 'setAs' => '',
            'data' => [
                'dproot1' => [
                    'id' => 'dproot1', 'name' => 'section', 'parentId' => null,
                    'properties' => ['tag' => 'section', 'symbolElPropId' => 'sep1234567'],
                    'styleIds' => [], 'className' => '', 'source' => 'droip',
                    'visibility' => true, 'collapse' => false, 'stylePanels' => ['typography' => true],
                ],
            ],
            'styleBlocks' => [],
        ];

        // Update with data where root has wrong parentId
        $newData = [
            'dproot1' => [
                'id' => 'dproot1', 'name' => 'section', 'parentId' => 'body',
                'properties' => ['tag' => 'section', 'symbolElPropId' => 'sep1234567'],
                'styleIds' => [], 'className' => '', 'source' => 'droip',
                'visibility' => true, 'collapse' => false, 'stylePanels' => ['typography' => true],
            ],
        ];
        SymbolCrudTools::handleUpdate(['symbol_id' => 42, 'data' => $newData]);

        // cleanElementData should have fixed parentId to null
        $this->assertNull(\WPMocks::$postMeta[42][DROIP_APP_PREFIX]['data']['dproot1']['parentId']);
    }

    public function testCreateWithWarnings(): void
    {
        Symbol::$saveReturn = ['id' => 1, 'symbolData' => [], 'type' => '', 'html' => ''];

        // Add an orphaned element to trigger a warning
        $args = $this->validCreateArgs();
        $args['data']['dporphan'] = [
            'id' => 'dporphan', 'name' => 'div', 'parentId' => 'dproot1',
            'properties' => ['tag' => 'div', 'symbolElPropId' => 'sep9999999'],
            'styleIds' => [], 'className' => '', 'source' => 'droip',
            'visibility' => true, 'collapse' => false, 'stylePanels' => ['typography' => true],
        ];

        $result = SymbolCrudTools::handleCreate($args);
        $data = json_decode($result->content[0]->text, true);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('orphaned', $data['message']);
    }

    // ── handleList ────────────────────────────────────────────────────

    public function testListEmpty(): void
    {
        Symbol::$fetchListReturn = [];
        $result = SymbolCrudTools::handleList([]);
        $this->assertStringContainsString('No symbols found', $result->content[0]->text);
    }

    public function testListWithSymbols(): void
    {
        Symbol::$fetchListReturn = [
            [
                'id' => 1,
                'symbolData' => ['name' => 'Hero', 'category' => 'Sections', 'setAs' => ''],
            ],
            [
                'id' => 2,
                'symbolData' => ['name' => 'Footer', 'category' => 'other', 'setAs' => 'footer'],
            ],
        ];

        $result = SymbolCrudTools::handleList([]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertCount(2, $data);
        $this->assertSame('Hero', $data[0]['name']);
        $this->assertSame(1, $data[0]['id']);
    }

    public function testListWithoutData(): void
    {
        Symbol::$fetchListReturn = [
            ['id' => 1, 'symbolData' => ['name' => 'Test', 'category' => 'other', 'setAs' => '']],
        ];

        $result = SymbolCrudTools::handleList(['include_data' => false]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertArrayNotHasKey('data', $data[0]);
        $this->assertArrayNotHasKey('styleBlocks', $data[0]);
    }

    public function testListWithData(): void
    {
        Symbol::$fetchListReturn = [
            [
                'id' => 1,
                'symbolData' => [
                    'name' => 'Test', 'category' => 'other', 'setAs' => '',
                    'data' => ['dp1' => ['id' => 'dp1']],
                    'styleBlocks' => ['sb1' => ['id' => 'sb1']],
                ],
            ],
        ];

        $result = SymbolCrudTools::handleList(['include_data' => true]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertArrayHasKey('data', $data[0]);
        $this->assertArrayHasKey('styleBlocks', $data[0]);
    }

    // ── handleGet ─────────────────────────────────────────────────────

    public function testGetSuccess(): void
    {
        Symbol::$getSingleReturn = [
            'id' => 42,
            'symbolData' => ['name' => 'Test', 'root' => 'dp1', 'data' => [], 'styleBlocks' => []],
        ];

        $result = SymbolCrudTools::handleGet(['symbol_id' => 42]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertSame(42, $data['id']);
    }

    public function testGetInvalidId(): void
    {
        $result = SymbolCrudTools::handleGet(['symbol_id' => 0]);
        $this->assertTrue($result->isError);
    }

    public function testGetMissingId(): void
    {
        $result = SymbolCrudTools::handleGet([]);
        $this->assertTrue($result->isError);
    }

    public function testGetNegativeId(): void
    {
        $result = SymbolCrudTools::handleGet(['symbol_id' => -1]);
        $this->assertTrue($result->isError);
    }

    public function testGetNotFound(): void
    {
        Symbol::$getSingleReturn = null;
        $result = SymbolCrudTools::handleGet(['symbol_id' => 999]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('not found', $result->content[0]->text);
    }

    // ── handleUpdate ──────────────────────────────────────────────────

    public function testUpdateName(): void
    {
        \WPMocks::$postMeta[42][DROIP_APP_PREFIX] = [
            'name' => 'Old', 'root' => 'dp1', 'category' => 'other', 'setAs' => '',
            'data' => ['dp1' => ['id' => 'dp1', 'name' => 'section', 'parentId' => null, 'properties' => ['tag' => 'section']]],
            'styleBlocks' => [],
        ];

        $result = SymbolCrudTools::handleUpdate(['symbol_id' => 42, 'name' => 'New Name']);
        $data = json_decode($result->content[0]->text, true);
        $this->assertTrue($data['success']);

        // Check the meta was updated
        $this->assertSame('New Name', \WPMocks::$postMeta[42][DROIP_APP_PREFIX]['name']);
    }

    public function testUpdateCategory(): void
    {
        \WPMocks::$postMeta[42][DROIP_APP_PREFIX] = [
            'name' => 'Test', 'root' => 'dp1', 'category' => 'other', 'setAs' => '',
            'data' => ['dp1' => ['id' => 'dp1', 'name' => 'section', 'parentId' => null, 'properties' => ['tag' => 'section']]],
            'styleBlocks' => [],
        ];

        SymbolCrudTools::handleUpdate(['symbol_id' => 42, 'category' => 'Buttons']);
        $this->assertSame('Buttons', \WPMocks::$postMeta[42][DROIP_APP_PREFIX]['category']);
    }

    public function testUpdateNoChanges(): void
    {
        \WPMocks::$postMeta[42][DROIP_APP_PREFIX] = ['name' => 'Test'];
        $result = SymbolCrudTools::handleUpdate(['symbol_id' => 42]);
        $this->assertStringContainsString('No updates', $result->content[0]->text);
    }

    public function testUpdateInvalidId(): void
    {
        $result = SymbolCrudTools::handleUpdate(['symbol_id' => 0]);
        $this->assertTrue($result->isError);
    }

    public function testUpdateNotFound(): void
    {
        $result = SymbolCrudTools::handleUpdate(['symbol_id' => 999, 'name' => 'New']);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('not found', $result->content[0]->text);
    }

    public function testUpdateValidationFailure(): void
    {
        \WPMocks::$postMeta[42][DROIP_APP_PREFIX] = [
            'name' => 'Test', 'root' => 'dproot', 'category' => 'other', 'setAs' => '',
            'data' => [
                'dproot' => ['id' => 'dproot', 'name' => 'section', 'parentId' => null, 'properties' => ['tag' => 'section'], 'children' => ['dpchild']],
            ],
            'styleBlocks' => [],
        ];

        // Update with data referencing non-existent child
        $result = SymbolCrudTools::handleUpdate([
            'symbol_id' => 42,
            'data' => [
                'dproot' => [
                    'id' => 'dproot', 'name' => 'section', 'parentId' => null,
                    'children' => ['nonexistent'], 'properties' => ['tag' => 'section'],
                ],
            ],
        ]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('Validation failed', $result->content[0]->text);
    }

    public function testUpdateSetAsUniqueness(): void
    {
        // Another symbol currently has header
        Symbol::$fetchListReturn = [
            [
                'id' => 10,
                'symbolData' => ['name' => 'Old Header', 'setAs' => 'header'],
            ],
        ];

        \WPMocks::$postMeta[42][DROIP_APP_PREFIX] = [
            'name' => 'New Header', 'root' => 'dp1', 'category' => 'other', 'setAs' => '',
            'data' => ['dp1' => ['id' => 'dp1', 'name' => 'section', 'parentId' => null, 'properties' => ['tag' => 'section']]],
            'styleBlocks' => [],
        ];

        SymbolCrudTools::handleUpdate(['symbol_id' => 42, 'setAs' => 'header']);

        // The old header's setAs should have been cleared
        $this->assertSame('', \WPMocks::$postMeta[10][DROIP_APP_PREFIX]['setAs']);
    }

    public function testUpdateData(): void
    {
        \WPMocks::$postMeta[42][DROIP_APP_PREFIX] = [
            'name' => 'Test', 'root' => 'dproot', 'category' => 'other', 'setAs' => '',
            'data' => ['dproot' => ['id' => 'dproot', 'name' => 'section', 'parentId' => null, 'properties' => ['tag' => 'section']]],
            'styleBlocks' => [],
        ];

        $newData = [
            'dproot' => [
                'id' => 'dproot', 'name' => 'section', 'parentId' => null,
                'properties' => ['tag' => 'section', 'symbolElPropId' => 'sep1234567'],
                'styleIds' => [], 'className' => '', 'source' => 'droip',
                'visibility' => true, 'collapse' => false, 'stylePanels' => ['typography' => true],
            ],
        ];
        $result = SymbolCrudTools::handleUpdate(['symbol_id' => 42, 'data' => $newData]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertTrue($data['success']);
    }

    public function testUpdateStyleBlocks(): void
    {
        \WPMocks::$postMeta[42][DROIP_APP_PREFIX] = [
            'name' => 'Test', 'root' => 'dproot', 'category' => 'other', 'setAs' => '',
            'data' => ['dproot' => ['id' => 'dproot', 'name' => 'section', 'parentId' => null, 'properties' => ['tag' => 'section']]],
            'styleBlocks' => [],
        ];

        $result = SymbolCrudTools::handleUpdate([
            'symbol_id'   => 42,
            'styleBlocks' => [
                'sb1' => ['id' => 'sb1', 'type' => 'class', 'name' => 'sb1', 'variant' => ['md' => 'color:red;']],
            ],
        ]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertTrue($data['success']);
    }

    // ── handleDelete ──────────────────────────────────────────────────

    public function testDeleteSuccess(): void
    {
        \WPMocks::$posts[42] = \WPMocks::createPost(42, DROIP_SYMBOL_TYPE);
        \WPMocks::$deleteResults[42] = true;

        $result = SymbolCrudTools::handleDelete(['symbol_id' => 42]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('deleted', $data['message']);
    }

    public function testDeleteInvalidId(): void
    {
        $result = SymbolCrudTools::handleDelete(['symbol_id' => 0]);
        $this->assertTrue($result->isError);
    }

    public function testDeleteMissingId(): void
    {
        $result = SymbolCrudTools::handleDelete([]);
        $this->assertTrue($result->isError);
    }

    public function testDeleteNotFound(): void
    {
        $result = SymbolCrudTools::handleDelete(['symbol_id' => 999]);
        $this->assertTrue($result->isError);
        $this->assertStringContainsString('not found', $result->content[0]->text);
    }

    public function testDeleteWrongPostType(): void
    {
        \WPMocks::$posts[42] = \WPMocks::createPost(42, 'page'); // Not a symbol
        $result = SymbolCrudTools::handleDelete(['symbol_id' => 42]);
        $this->assertTrue($result->isError);
    }

    public function testDeleteFailure(): void
    {
        \WPMocks::$posts[42] = \WPMocks::createPost(42, DROIP_SYMBOL_TYPE);
        \WPMocks::$deleteResults[42] = false;

        $result = SymbolCrudTools::handleDelete(['symbol_id' => 42]);
        $data = json_decode($result->content[0]->text, true);
        $this->assertFalse($data['success']);
    }
}
