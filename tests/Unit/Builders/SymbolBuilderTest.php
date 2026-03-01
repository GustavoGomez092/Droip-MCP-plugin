<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Builders;

use DroipBridge\Builders\SymbolBuilder;
use DroipBridge\Builders\ElementFactory;
use DroipBridge\Builders\StyleBuilder;
use DroipBridge\Builders\IdGenerator;
use Droip\Ajax\Symbol;
use PHPUnit\Framework\TestCase;

class SymbolBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        Symbol::reset();
    }

    public function testConstructorCreatesRootElement(): void
    {
        $builder = new SymbolBuilder('Test Symbol');
        $result = $builder->build();
        $rootId = $builder->getRootId();

        $this->assertStringStartsWith('dp', $rootId);
        $this->assertArrayHasKey($rootId, $result['symbolData']['data']);
    }

    public function testConstructorSetsName(): void
    {
        $builder = new SymbolBuilder('Hero Section');
        $result = $builder->build();
        $this->assertSame('Hero Section', $result['symbolData']['name']);
    }

    public function testConstructorDefaultCategory(): void
    {
        $builder = new SymbolBuilder('Test');
        $result = $builder->build();
        $this->assertSame('other', $result['symbolData']['category']);
    }

    public function testConstructorCustomCategory(): void
    {
        $builder = new SymbolBuilder('Test', 'Sections');
        $result = $builder->build();
        $this->assertSame('Sections', $result['symbolData']['category']);
    }

    public function testGetRootId(): void
    {
        $builder = new SymbolBuilder('Test');
        $rootId = $builder->getRootId();
        $this->assertStringStartsWith('dp', $rootId);
        $this->assertSame(8, strlen($rootId));
    }

    public function testSetAs(): void
    {
        $builder = new SymbolBuilder('Header');
        $result = $builder->setAs('header')->build();
        $this->assertSame('header', $result['symbolData']['setAs']);
    }

    public function testSetAsReturnsSelf(): void
    {
        $builder = new SymbolBuilder('Test');
        $this->assertSame($builder, $builder->setAs('footer'));
    }

    public function testSetRootTag(): void
    {
        $builder = new SymbolBuilder('Header');
        $builder->setRootTag('header');
        $result = $builder->build();
        $rootId = $builder->getRootId();
        $this->assertSame('header', $result['symbolData']['data'][$rootId]['properties']['tag']);
    }

    public function testSetRootTagUpdatesName(): void
    {
        $builder = new SymbolBuilder('Test');
        $rootId = $builder->getRootId();

        $builder->setRootTag('header');
        $result = $builder->build();
        $this->assertSame('section', $result['symbolData']['data'][$rootId]['name']);

        $builder->setRootTag('nav');
        $result = $builder->build();
        $this->assertSame('div', $result['symbolData']['data'][$rootId]['name']);

        $builder->setRootTag('footer');
        $result = $builder->build();
        $this->assertSame('section', $result['symbolData']['data'][$rootId]['name']);

        $builder->setRootTag('section');
        $result = $builder->build();
        $this->assertSame('section', $result['symbolData']['data'][$rootId]['name']);

        $builder->setRootTag('div');
        $result = $builder->build();
        $this->assertSame('div', $result['symbolData']['data'][$rootId]['name']);
    }

    public function testSetRootTagReturnsSelf(): void
    {
        $builder = new SymbolBuilder('Test');
        $this->assertSame($builder, $builder->setRootTag('nav'));
    }

    public function testAddElement(): void
    {
        $builder = new SymbolBuilder('Test');
        $el = ElementFactory::heading('dpchild1', $builder->getRootId(), 'Hello');
        $builder->addElement($el);

        $result = $builder->build();
        $this->assertArrayHasKey('dpchild1', $result['symbolData']['data']);
        $this->assertSame('heading', $result['symbolData']['data']['dpchild1']['name']);
    }

    public function testAddElementReturnsSelf(): void
    {
        $builder = new SymbolBuilder('Test');
        $el = ElementFactory::frame('dpf1', null);
        $this->assertSame($builder, $builder->addElement($el));
    }

    public function testAddChild(): void
    {
        $builder = new SymbolBuilder('Test');
        $rootId = $builder->getRootId();

        $el = ElementFactory::heading('dpchild1', $rootId, 'Title');
        $builder->addElement($el);
        $builder->addChild($rootId, 'dpchild1');

        $result = $builder->build();
        $this->assertContains('dpchild1', $result['symbolData']['data'][$rootId]['children']);
    }

    public function testAddChildPreventsDeduplication(): void
    {
        $builder = new SymbolBuilder('Test');
        $rootId = $builder->getRootId();

        $el = ElementFactory::heading('dpchild1', $rootId, 'Title');
        $builder->addElement($el);
        $builder->addChild($rootId, 'dpchild1');
        $builder->addChild($rootId, 'dpchild1'); // duplicate

        $result = $builder->build();
        $children = $result['symbolData']['data'][$rootId]['children'];
        $this->assertCount(1, array_keys($children, 'dpchild1', true));
    }

    public function testAddChildIgnoresNonExistentParent(): void
    {
        $builder = new SymbolBuilder('Test');
        $builder->addChild('nonexistent', 'dpchild1');
        // Should not throw, just silently skip
        $this->assertTrue(true);
    }

    public function testAddChildReturnsSelf(): void
    {
        $builder = new SymbolBuilder('Test');
        $this->assertSame($builder, $builder->addChild($builder->getRootId(), 'dpchild1'));
    }

    public function testAddStyleBlock(): void
    {
        $builder = new SymbolBuilder('Test');
        $sb = StyleBuilder::create('mcpbr_dp1', 'mcpbr_dp2', 'color:red;');
        $builder->addStyleBlock($sb);

        $result = $builder->build();
        $this->assertArrayHasKey('mcpbr_dp1', $result['symbolData']['styleBlocks']);
    }

    public function testAddStyleBlockReturnsSelf(): void
    {
        $builder = new SymbolBuilder('Test');
        $sb = StyleBuilder::create('id1', 'name1', 'color:red;');
        $this->assertSame($builder, $builder->addStyleBlock($sb));
    }

    public function testSetRootStyleIds(): void
    {
        $builder = new SymbolBuilder('Test');
        $builder->setRootStyleIds(['mcpbr_dp1', 'mcpbr_dp2']);

        $result = $builder->build();
        $rootId = $builder->getRootId();
        $this->assertSame(['mcpbr_dp1', 'mcpbr_dp2'], $result['symbolData']['data'][$rootId]['styleIds']);
    }

    public function testSetRootStyleIdsReturnsSelf(): void
    {
        $builder = new SymbolBuilder('Test');
        $this->assertSame($builder, $builder->setRootStyleIds([]));
    }

    public function testAddCustomFont(): void
    {
        $builder = new SymbolBuilder('Test');
        $builder->addCustomFont('Inter', 'https://fonts.com/inter.woff2', ['400', '700']);

        $result = $builder->build();
        $fonts = $result['symbolData']['customFonts'];
        $this->assertArrayHasKey('Inter', $fonts);
        $this->assertSame('Inter', $fonts['Inter']['family']);
        $this->assertSame('https://fonts.com/inter.woff2', $fonts['Inter']['fontUrl']);
        $this->assertSame(['400', '700'], $fonts['Inter']['variants']);
    }

    public function testAddCustomFontReturnsSelf(): void
    {
        $builder = new SymbolBuilder('Test');
        $this->assertSame($builder, $builder->addCustomFont('Arial', '', []));
    }

    public function testBuildReturnsCompleteStructure(): void
    {
        $builder = new SymbolBuilder('My Symbol', 'Sections');
        $builder->setAs('header');

        $result = $builder->build();

        $this->assertArrayHasKey('symbolData', $result);
        $sd = $result['symbolData'];
        $this->assertSame('My Symbol', $sd['name']);
        $this->assertSame('Sections', $sd['category']);
        $this->assertSame('header', $sd['setAs']);
        $this->assertStringStartsWith('dp', $sd['root']);
        $this->assertIsArray($sd['data']);
        $this->assertIsArray($sd['styleBlocks']);
        $this->assertIsArray($sd['customFonts']);
    }

    public function testBuildRootElementHasNullParent(): void
    {
        $builder = new SymbolBuilder('Test');
        $result = $builder->build();
        $rootId = $builder->getRootId();
        $this->assertNull($result['symbolData']['data'][$rootId]['parentId']);
    }

    public function testSaveCallsDroipApi(): void
    {
        Symbol::$saveReturn = ['id' => 123, 'symbolData' => [], 'type' => 'droip_symbol', 'html' => ''];

        $builder = new SymbolBuilder('Test');
        $result = $builder->save();

        $this->assertNotNull($result);
        $this->assertSame(123, $result['id']);
        $this->assertCount(1, Symbol::$saveCalls);
    }

    public function testSavePassesCorrectPayload(): void
    {
        Symbol::$saveReturn = ['id' => 1, 'symbolData' => [], 'type' => '', 'html' => ''];

        $builder = new SymbolBuilder('Test Symbol');
        $builder->save();

        $payload = Symbol::$saveCalls[0];
        $this->assertArrayHasKey('symbolData', $payload);
        $this->assertSame('Test Symbol', $payload['symbolData']['name']);
    }

    public function testSaveReturnsNullOnFailure(): void
    {
        Symbol::$saveReturn = null;

        $builder = new SymbolBuilder('Test');
        $result = $builder->save();
        $this->assertNull($result);
    }

    public function testFullBuildWorkflow(): void
    {
        $builder = new SymbolBuilder('Hero Section', 'Sections');
        $rootId = $builder->getRootId();

        // Add a heading
        $headingId = IdGenerator::elementId();
        $heading = ElementFactory::heading($headingId, $rootId, 'Welcome');
        $builder->addElement($heading);
        $builder->addChild($rootId, $headingId);

        // Add a style block
        $styleId = IdGenerator::styleId();
        $sb = StyleBuilder::responsive($styleId, $styleId, ['padding' => '80px'], ['padding' => '40px']);
        $builder->addStyleBlock($sb);
        $builder->setRootStyleIds([$styleId]);

        $result = $builder->build();
        $sd = $result['symbolData'];

        $this->assertSame('Hero Section', $sd['name']);
        $this->assertSame($rootId, $sd['root']);
        $this->assertCount(2, $sd['data']); // root + heading
        $this->assertContains($headingId, $sd['data'][$rootId]['children']);
        $this->assertArrayHasKey($styleId, $sd['styleBlocks']);
        $this->assertContains($styleId, $sd['data'][$rootId]['styleIds']);
    }
}
