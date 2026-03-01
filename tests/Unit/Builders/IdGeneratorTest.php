<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Builders;

use DroipBridge\Builders\IdGenerator;
use PHPUnit\Framework\TestCase;

class IdGeneratorTest extends TestCase
{
    public function testElementIdHasCorrectPrefix(): void
    {
        $id = IdGenerator::elementId();
        $this->assertStringStartsWith('dp', $id);
    }

    public function testElementIdHasCorrectDefaultLength(): void
    {
        $id = IdGenerator::elementId();
        // 'dp' prefix + 6 chars = 8 total
        $this->assertSame(8, strlen($id));
    }

    public function testElementIdRespectsCustomLength(): void
    {
        $id = IdGenerator::elementId(10);
        // 'dp' prefix + 10 chars = 12 total
        $this->assertSame(12, strlen($id));
    }

    public function testElementIdContainsOnlyValidChars(): void
    {
        $id = IdGenerator::elementId();
        $suffix = substr($id, 2);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $suffix);
    }

    public function testStyleIdHasCorrectDefaultPrefix(): void
    {
        $id = IdGenerator::styleId();
        $this->assertStringStartsWith('mcpbr_dp', $id);
    }

    public function testStyleIdHasCorrectDefaultLength(): void
    {
        $id = IdGenerator::styleId();
        // 'mcpbr_dp' (8) + 6 chars = 14 total
        $this->assertSame(14, strlen($id));
    }

    public function testStyleIdRespectsCustomPrefix(): void
    {
        $id = IdGenerator::styleId('custom');
        $this->assertStringStartsWith('custom_dp', $id);
    }

    public function testStyleIdRespectsCustomLength(): void
    {
        $id = IdGenerator::styleId('mcpbr', 10);
        // 'mcpbr_dp' (8) + 10 chars = 18 total
        $this->assertSame(18, strlen($id));
    }

    public function testSymbolElPropIdHasCorrectPrefix(): void
    {
        $id = IdGenerator::symbolElPropId();
        $this->assertStringStartsWith('sep', $id);
    }

    public function testSymbolElPropIdHasCorrectDefaultLength(): void
    {
        $id = IdGenerator::symbolElPropId();
        // 'sep' prefix + 7 chars = 10 total
        $this->assertSame(10, strlen($id));
    }

    public function testSymbolElPropIdRespectsCustomLength(): void
    {
        $id = IdGenerator::symbolElPropId(5);
        // 'sep' prefix + 5 chars = 8 total
        $this->assertSame(8, strlen($id));
    }

    public function testElementBatchReturnsCorrectCount(): void
    {
        $ids = IdGenerator::elementBatch(5);
        $this->assertCount(5, $ids);
    }

    public function testElementBatchAllHaveCorrectFormat(): void
    {
        $ids = IdGenerator::elementBatch(3);
        foreach ($ids as $id) {
            $this->assertStringStartsWith('dp', $id);
            $this->assertSame(8, strlen($id));
        }
    }

    public function testElementBatchGeneratesUniqueIds(): void
    {
        $ids = IdGenerator::elementBatch(50);
        $this->assertCount(50, array_unique($ids));
    }

    public function testStyleBatchReturnsCorrectCount(): void
    {
        $ids = IdGenerator::styleBatch(5);
        $this->assertCount(5, $ids);
    }

    public function testStyleBatchAllHaveCorrectFormat(): void
    {
        $ids = IdGenerator::styleBatch(3);
        foreach ($ids as $id) {
            $this->assertStringStartsWith('mcpbr_dp', $id);
            $this->assertSame(14, strlen($id));
        }
    }

    public function testStyleBatchRespectsCustomPrefix(): void
    {
        $ids = IdGenerator::styleBatch(3, 'planzo');
        foreach ($ids as $id) {
            $this->assertStringStartsWith('planzo_dp', $id);
        }
    }

    public function testStyleBatchGeneratesUniqueIds(): void
    {
        $ids = IdGenerator::styleBatch(50);
        $this->assertCount(50, array_unique($ids));
    }

    public function testSingleElementBatch(): void
    {
        $ids = IdGenerator::elementBatch(1);
        $this->assertCount(1, $ids);
        $this->assertStringStartsWith('dp', $ids[0]);
    }

    public function testZeroElementBatch(): void
    {
        $ids = IdGenerator::elementBatch(0);
        $this->assertCount(0, $ids);
    }
}
