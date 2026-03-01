<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Tools;

use DroipBridge\Tools\KnowledgeTools;
use Droip\Ajax\Symbol;
use Mcp\Types\CallToolResult;
use PHPUnit\Framework\TestCase;

class KnowledgeToolsTest extends TestCase
{
    protected function setUp(): void
    {
        Symbol::reset();
    }

    // ── getTools ───────────────────────────────────────────────────────

    public function testGetToolsReturns5Tools(): void
    {
        $tools = KnowledgeTools::getTools();
        $this->assertCount(5, $tools);
    }

    public function testGetToolsHasCorrectNames(): void
    {
        $tools = KnowledgeTools::getTools();
        $names = array_map(fn($t) => $t->name, $tools);

        $this->assertContains('droip_get_element_schema', $names);
        $this->assertContains('droip_get_symbol_guide', $names);
        $this->assertContains('droip_get_style_guide', $names);
        $this->assertContains('droip_get_animation_guide', $names);
        $this->assertContains('droip_get_example_symbols', $names);
    }

    public function testGetToolsReturnToolInstances(): void
    {
        $tools = KnowledgeTools::getTools();
        foreach ($tools as $tool) {
            $this->assertInstanceOf(\Mcp\Types\Tool::class, $tool);
        }
    }

    // ── handleGetElementSchema ─────────────────────────────────────────

    public function testHandleGetElementSchemaReturnsContent(): void
    {
        $result = KnowledgeTools::handleGetElementSchema(null);
        $this->assertInstanceOf(CallToolResult::class, $result);
        $text = $result->content[0]->text;
        $this->assertNotEmpty($text);
    }

    public function testHandleGetElementSchemaWithNullArgs(): void
    {
        $result = KnowledgeTools::handleGetElementSchema(null);
        $text = $result->content[0]->text;
        $this->assertStringNotContainsString('Filtered for element type:', $text);
    }

    public function testHandleGetElementSchemaWithFilter(): void
    {
        $result = KnowledgeTools::handleGetElementSchema(['element_type' => 'heading']);
        $text = $result->content[0]->text;
        $this->assertStringContainsString('Filtered for element type: heading', $text);
    }

    public function testHandleGetElementSchemaWithEmptyArgs(): void
    {
        $result = KnowledgeTools::handleGetElementSchema([]);
        $text = $result->content[0]->text;
        $this->assertStringNotContainsString('Filtered for element type:', $text);
    }

    // ── handleGetSymbolGuide ───────────────────────────────────────────

    public function testHandleGetSymbolGuideReturnsContent(): void
    {
        $result = KnowledgeTools::handleGetSymbolGuide();
        $this->assertInstanceOf(CallToolResult::class, $result);
        $text = $result->content[0]->text;
        $this->assertNotEmpty($text);
    }

    public function testHandleGetSymbolGuideCombinesDocs(): void
    {
        $result = KnowledgeTools::handleGetSymbolGuide();
        $text = $result->content[0]->text;
        // Should contain separator between two docs
        $this->assertStringContainsString('---', $text);
    }

    // ── handleGetStyleGuide ────────────────────────────────────────────

    public function testHandleGetStyleGuideReturnsContent(): void
    {
        $result = KnowledgeTools::handleGetStyleGuide();
        $this->assertInstanceOf(CallToolResult::class, $result);
        $text = $result->content[0]->text;
        $this->assertNotEmpty($text);
    }

    // ── handleGetAnimationGuide ────────────────────────────────────────

    public function testHandleGetAnimationGuideReturnsContent(): void
    {
        $result = KnowledgeTools::handleGetAnimationGuide();
        $this->assertInstanceOf(CallToolResult::class, $result);
        $text = $result->content[0]->text;
        $this->assertNotEmpty($text);
    }

    // ── handleGetExampleSymbols ────────────────────────────────────────

    public function testHandleGetExampleSymbolsEmpty(): void
    {
        Symbol::$fetchListReturn = [];
        $result = KnowledgeTools::handleGetExampleSymbols(null);
        $text = $result->content[0]->text;
        $this->assertStringContainsString('No symbols found', $text);
    }

    public function testHandleGetExampleSymbolsWithData(): void
    {
        Symbol::$fetchListReturn = [
            [
                'id'   => 1,
                'type' => 'Sections',
                'symbolData' => [
                    'name'        => 'Hero Section',
                    'category'    => 'Sections',
                    'root'        => 'dproot1',
                    'setAs'       => '',
                    'data'        => ['dproot1' => ['id' => 'dproot1']],
                    'styleBlocks' => ['sb1' => ['id' => 'sb1']],
                ],
            ],
        ];

        $result = KnowledgeTools::handleGetExampleSymbols(null);
        $text = $result->content[0]->text;
        $this->assertStringContainsString('Hero Section', $text);
        $this->assertStringContainsString('Example Symbols', $text);
    }

    public function testHandleGetExampleSymbolsFilterByType(): void
    {
        Symbol::$fetchListReturn = [
            [
                'id'   => 1,
                'type' => 'Sections',
                'symbolData' => [
                    'name' => 'Hero Section', 'category' => 'Sections', 'root' => 'dp1', 'setAs' => '',
                    'data' => ['dp1' => ['id' => 'dp1']], 'styleBlocks' => [],
                ],
            ],
            [
                'id'   => 2,
                'type' => 'Buttons',
                'symbolData' => [
                    'name' => 'Primary Button', 'category' => 'Buttons', 'root' => 'dp2', 'setAs' => '',
                    'data' => ['dp2' => ['id' => 'dp2']], 'styleBlocks' => [],
                ],
            ],
        ];

        $result = KnowledgeTools::handleGetExampleSymbols(['type' => 'button']);
        $text = $result->content[0]->text;
        $this->assertStringContainsString('Primary Button', $text);
        $this->assertStringNotContainsString('Hero Section', $text);
    }

    public function testHandleGetExampleSymbolsFilterNoMatch(): void
    {
        Symbol::$fetchListReturn = [
            [
                'id'   => 1,
                'type' => 'Sections',
                'symbolData' => [
                    'name' => 'Hero', 'category' => 'Sections', 'root' => 'dp1', 'setAs' => '',
                    'data' => ['dp1' => ['id' => 'dp1']], 'styleBlocks' => [],
                ],
            ],
        ];

        $result = KnowledgeTools::handleGetExampleSymbols(['type' => 'footer']);
        $text = $result->content[0]->text;
        $this->assertStringContainsString('No symbols matching', $text);
        $this->assertStringContainsString('Hero', $text); // lists available symbols
    }

    public function testHandleGetExampleSymbolsLimitsTo3(): void
    {
        $symbols = [];
        for ($i = 1; $i <= 5; $i++) {
            $symbols[] = [
                'id'   => $i,
                'type' => 'Sections',
                'symbolData' => [
                    'name' => "Symbol {$i}", 'category' => 'Sections', 'root' => "dp{$i}", 'setAs' => '',
                    'data' => ["dp{$i}" => ['id' => "dp{$i}"]], 'styleBlocks' => [],
                ],
            ];
        }
        Symbol::$fetchListReturn = $symbols;

        $result = KnowledgeTools::handleGetExampleSymbols(null);
        $text = $result->content[0]->text;
        $this->assertStringContainsString('Symbol 1', $text);
        $this->assertStringContainsString('Symbol 2', $text);
        $this->assertStringContainsString('Symbol 3', $text);
        $this->assertStringNotContainsString('Symbol 4', $text);
        $this->assertStringNotContainsString('Symbol 5', $text);
    }

    public function testHandleGetExampleSymbolsFilterBySetAs(): void
    {
        Symbol::$fetchListReturn = [
            [
                'id'   => 1,
                'type' => 'other',
                'symbolData' => [
                    'name' => 'Site Header', 'category' => 'other', 'root' => 'dp1', 'setAs' => 'header',
                    'data' => ['dp1' => ['id' => 'dp1']], 'styleBlocks' => [],
                ],
            ],
        ];

        $result = KnowledgeTools::handleGetExampleSymbols(['type' => 'header']);
        $text = $result->content[0]->text;
        $this->assertStringContainsString('Site Header', $text);
    }

    public function testHandleGetExampleSymbolsFilterByName(): void
    {
        Symbol::$fetchListReturn = [
            [
                'id'   => 1,
                'type' => 'other',
                'symbolData' => [
                    'name' => 'Footer Nav', 'category' => 'other', 'root' => 'dp1', 'setAs' => '',
                    'data' => ['dp1' => ['id' => 'dp1']], 'styleBlocks' => [],
                ],
            ],
        ];

        $result = KnowledgeTools::handleGetExampleSymbols(['type' => 'footer']);
        $text = $result->content[0]->text;
        $this->assertStringContainsString('Footer Nav', $text);
    }
}
