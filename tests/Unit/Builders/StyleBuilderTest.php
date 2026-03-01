<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Builders;

use DroipBridge\Builders\StyleBuilder;
use PHPUnit\Framework\TestCase;

class StyleBuilderTest extends TestCase
{
    public function testCreateReturnsCorrectStructure(): void
    {
        $sb = StyleBuilder::create('mcpbr_dp1', 'mcpbr_dp2', 'display:flex;gap:12px;');
        $this->assertSame('mcpbr_dp1', $sb['id']);
        $this->assertSame('class', $sb['type']);
        $this->assertSame('mcpbr_dp2', $sb['name']);
        $this->assertSame('display:flex;gap:12px;', $sb['variant']['md']);
        $this->assertTrue($sb['isGlobal']);
        $this->assertTrue($sb['isSymbolStyle']);
    }

    public function testCreateHasOnlyMdVariant(): void
    {
        $sb = StyleBuilder::create('id1', 'name1', 'color:red;');
        $this->assertCount(1, $sb['variant']);
        $this->assertArrayHasKey('md', $sb['variant']);
    }

    public function testFromArrayConvertsToCss(): void
    {
        $sb = StyleBuilder::fromArray('id1', 'name1', [
            'display'        => 'flex',
            'flex-direction' => 'column',
            'gap'            => '16px',
        ]);
        $this->assertSame('display:flex;flex-direction:column;gap:16px;', $sb['variant']['md']);
    }

    public function testFromArrayEmptyProperties(): void
    {
        $sb = StyleBuilder::fromArray('id1', 'name1', []);
        $this->assertSame(';', $sb['variant']['md']);
    }

    public function testFromArrayMaintainsStructure(): void
    {
        $sb = StyleBuilder::fromArray('id1', 'name1', ['color' => 'blue']);
        $this->assertSame('id1', $sb['id']);
        $this->assertSame('class', $sb['type']);
        $this->assertTrue($sb['isGlobal']);
        $this->assertTrue($sb['isSymbolStyle']);
    }

    public function testResponsiveDesktopOnly(): void
    {
        $sb = StyleBuilder::responsive('id1', 'name1', ['display' => 'flex']);
        $this->assertCount(1, $sb['variant']);
        $this->assertSame('display:flex;', $sb['variant']['md']);
    }

    public function testResponsiveWithTablet(): void
    {
        $sb = StyleBuilder::responsive('id1', 'name1',
            ['display' => 'flex', 'gap' => '24px'],
            ['gap' => '16px'],
        );
        $this->assertSame('display:flex;gap:24px;', $sb['variant']['md']);
        $this->assertSame('gap:16px;', $sb['variant']['tablet']);
        $this->assertArrayNotHasKey('mobile', $sb['variant']);
    }

    public function testResponsiveWithAllBreakpoints(): void
    {
        $sb = StyleBuilder::responsive('id1', 'name1',
            ['padding' => '80px 24px'],
            ['padding' => '60px 20px'],
            ['padding' => '40px 16px'],
        );
        $this->assertSame('padding:80px 24px;', $sb['variant']['md']);
        $this->assertSame('padding:60px 20px;', $sb['variant']['tablet']);
        $this->assertSame('padding:40px 16px;', $sb['variant']['mobile']);
    }

    public function testResponsiveSkipsEmptyTablet(): void
    {
        $sb = StyleBuilder::responsive('id1', 'name1',
            ['display' => 'block'],
            [],
            ['display' => 'none'],
        );
        $this->assertArrayNotHasKey('tablet', $sb['variant']);
        $this->assertSame('display:none;', $sb['variant']['mobile']);
    }

    public function testWithHover(): void
    {
        $sb = StyleBuilder::withHover('id1', 'name1',
            ['background-color' => '#fff', 'transition' => 'all 0.3s'],
            ['background-color' => '#000'],
        );
        $this->assertSame('background-color:#fff;transition:all 0.3s;', $sb['variant']['md']);
        $this->assertSame('background-color:#000;', $sb['variant']['md_hover']);
    }

    public function testWithHoverHasCorrectStructure(): void
    {
        $sb = StyleBuilder::withHover('id1', 'name1', ['color' => 'red'], ['color' => 'blue']);
        $this->assertSame('id1', $sb['id']);
        $this->assertSame('class', $sb['type']);
        $this->assertTrue($sb['isGlobal']);
        $this->assertTrue($sb['isSymbolStyle']);
        $this->assertCount(2, $sb['variant']);
    }

    public function testWithHoverHasBothVariants(): void
    {
        $sb = StyleBuilder::withHover('id1', 'name1',
            ['opacity' => '1'],
            ['opacity' => '0.8'],
        );
        $this->assertArrayHasKey('md', $sb['variant']);
        $this->assertArrayHasKey('md_hover', $sb['variant']);
    }
}
