<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Validators;

use DroipBridge\Validators\SymbolValidator;
use PHPUnit\Framework\TestCase;

class SymbolValidatorTest extends TestCase
{
    private function validSymbolData(): array
    {
        return [
            'name'        => 'Test Symbol',
            'root'        => 'dproot1',
            'data'        => [
                'dproot1' => [
                    'id'         => 'dproot1',
                    'name'       => 'section',
                    'parentId'   => null,
                    'children'   => ['dpchild1'],
                    'properties' => ['tag' => 'section'],
                    'styleIds'   => ['style1'],
                ],
                'dpchild1' => [
                    'id'         => 'dpchild1',
                    'name'       => 'heading',
                    'parentId'   => 'dproot1',
                    'properties' => ['tag' => 'h2', 'contents' => ['Hello']],
                    'styleIds'   => [],
                ],
            ],
            'styleBlocks' => [
                'style1' => [
                    'id'      => 'style1',
                    'type'    => 'class',
                    'name'    => 'style1',
                    'variant' => ['md' => 'display:flex;'],
                ],
            ],
        ];
    }

    public function testValidSymbolPasses(): void
    {
        $result = SymbolValidator::validate($this->validSymbolData());
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testMissingName(): void
    {
        $data = $this->validSymbolData();
        unset($data['name']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Missing required field: 'name'", $result['errors']);
    }

    public function testMissingRoot(): void
    {
        $data = $this->validSymbolData();
        unset($data['root']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Missing required field: 'root'", $result['errors']);
    }

    public function testMissingData(): void
    {
        $data = $this->validSymbolData();
        unset($data['data']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Missing required field: 'data'", $result['errors']);
    }

    public function testMissingStyleBlocks(): void
    {
        $data = $this->validSymbolData();
        unset($data['styleBlocks']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Missing required field: 'styleBlocks'", $result['errors']);
    }

    public function testMultipleMissingFields(): void
    {
        $data = $this->validSymbolData();
        unset($data['name'], $data['root']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']);
    }

    public function testMissingFieldsReturnsEarlyBeforeValidation(): void
    {
        $result = SymbolValidator::validate([]);
        $this->assertFalse($result['valid']);
        $this->assertCount(4, $result['errors']);
    }

    public function testRootElementNotInData(): void
    {
        $data = $this->validSymbolData();
        $data['root'] = 'nonexistent';
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Root element 'nonexistent' not found in data map", $result['errors']);
    }

    public function testRootElementNonNullParentId(): void
    {
        $data = $this->validSymbolData();
        $data['data']['dproot1']['parentId'] = 'body';
        $result = SymbolValidator::validate($data);
        $this->assertContains('Root element should have parentId = null', $result['warnings']);
    }

    public function testElementMissingRequiredFields(): void
    {
        $data = $this->validSymbolData();
        unset($data['data']['dpchild1']['name']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $hasError = false;
        foreach ($result['errors'] as $error) {
            if (str_contains($error, "dpchild1") && str_contains($error, "name")) {
                $hasError = true;
                break;
            }
        }
        $this->assertTrue($hasError);
    }

    public function testElementMissingProperties(): void
    {
        $data = $this->validSymbolData();
        unset($data['data']['dpchild1']['properties']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
    }

    public function testElementMissingId(): void
    {
        $data = $this->validSymbolData();
        unset($data['data']['dpchild1']['id']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
    }

    public function testElementIdMismatch(): void
    {
        $data = $this->validSymbolData();
        $data['data']['dpchild1']['id'] = 'dpwrong';
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Element key 'dpchild1' does not match element id 'dpwrong'", $result['errors']);
    }

    public function testUnknownElementTypeWarning(): void
    {
        $data = $this->validSymbolData();
        $data['data']['dpchild1']['name'] = 'unknown-type';
        $result = SymbolValidator::validate($data);
        $foundWarning = false;
        foreach ($result['warnings'] as $w) {
            if (str_contains($w, 'unknown-type') && str_contains($w, 'unknown type')) {
                $foundWarning = true;
                break;
            }
        }
        $this->assertTrue($foundWarning);
    }

    public function testValidElementTypesNoWarning(): void
    {
        $validTypes = ['div', 'section', 'heading', 'paragraph', 'button', 'image'];
        foreach ($validTypes as $type) {
            $data = $this->validSymbolData();
            $data['data']['dpchild1']['name'] = $type;
            $result = SymbolValidator::validate($data);
            $typeWarnings = array_filter($result['warnings'], fn($w) => str_contains($w, 'unknown type'));
            $this->assertEmpty($typeWarnings, "Type '{$type}' should not trigger unknown type warning");
        }
    }

    public function testInvalidParentReference(): void
    {
        $data = $this->validSymbolData();
        $data['data']['dpchild1']['parentId'] = 'nonexistent';
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Element 'dpchild1' references non-existent parent 'nonexistent'", $result['errors']);
    }

    public function testInvalidChildReference(): void
    {
        $data = $this->validSymbolData();
        $data['data']['dproot1']['children'][] = 'nonexistent';
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Element 'dproot1' references non-existent child 'nonexistent'", $result['errors']);
    }

    public function testMissingStyleBlockReference(): void
    {
        $data = $this->validSymbolData();
        $data['data']['dpchild1']['styleIds'] = ['nonexistent_style'];
        $result = SymbolValidator::validate($data);
        $foundWarning = false;
        foreach ($result['warnings'] as $w) {
            if (str_contains($w, 'nonexistent_style')) {
                $foundWarning = true;
                break;
            }
        }
        $this->assertTrue($foundWarning);
    }

    public function testOrphanedElementWarning(): void
    {
        $data = $this->validSymbolData();
        // Add an element that is not in any parent's children
        $data['data']['dporphan'] = [
            'id'         => 'dporphan',
            'name'       => 'div',
            'parentId'   => 'dproot1',
            'properties' => ['tag' => 'div'],
            'styleIds'   => [],
        ];
        $result = SymbolValidator::validate($data);
        $foundWarning = false;
        foreach ($result['warnings'] as $w) {
            if (str_contains($w, 'dporphan') && str_contains($w, 'orphaned')) {
                $foundWarning = true;
                break;
            }
        }
        $this->assertTrue($foundWarning);
    }

    public function testRootIsNotOrphaned(): void
    {
        $data = $this->validSymbolData();
        $result = SymbolValidator::validate($data);
        $rootOrphanWarnings = array_filter($result['warnings'], fn($w) => str_contains($w, 'dproot1') && str_contains($w, 'orphaned'));
        $this->assertEmpty($rootOrphanWarnings);
    }

    public function testStyleBlockMissingId(): void
    {
        $data = $this->validSymbolData();
        unset($data['styleBlocks']['style1']['id']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Style block 'style1' missing 'id' field", $result['errors']);
    }

    public function testStyleBlockMissingVariant(): void
    {
        $data = $this->validSymbolData();
        unset($data['styleBlocks']['style1']['variant']);
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Style block 'style1' missing or invalid 'variant' field", $result['errors']);
    }

    public function testStyleBlockVariantNotArray(): void
    {
        $data = $this->validSymbolData();
        $data['styleBlocks']['style1']['variant'] = 'not-array';
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
    }

    public function testStyleBlockMdNotString(): void
    {
        $data = $this->validSymbolData();
        $data['styleBlocks']['style1']['variant']['md'] = 123;
        $result = SymbolValidator::validate($data);
        $this->assertFalse($result['valid']);
        $this->assertContains("Style block 'style1' variant 'md' must be a CSS string", $result['errors']);
    }

    public function testValidSymbolWithNoChildren(): void
    {
        $data = [
            'name'        => 'Simple',
            'root'        => 'dproot1',
            'data'        => [
                'dproot1' => [
                    'id'         => 'dproot1',
                    'name'       => 'section',
                    'parentId'   => null,
                    'properties' => ['tag' => 'section'],
                    'styleIds'   => [],
                ],
            ],
            'styleBlocks' => [],
        ];
        $result = SymbolValidator::validate($data);
        $this->assertTrue($result['valid']);
    }

    public function testNullParentIdDoesNotTriggerError(): void
    {
        $data = $this->validSymbolData();
        $data['data']['dpchild1']['parentId'] = null;
        $result = SymbolValidator::validate($data);
        // Should not have parent reference error
        $parentErrors = array_filter($result['errors'], fn($e) => str_contains($e, 'non-existent parent'));
        $this->assertEmpty($parentErrors);
    }

    public function testElementWithoutStyleIdsDoesNotError(): void
    {
        $data = $this->validSymbolData();
        unset($data['data']['dpchild1']['styleIds']);
        $result = SymbolValidator::validate($data);
        // Missing styleIds should not cause an error (it's optional for elements)
        $this->assertTrue($result['valid']);
    }

    public function testElementWithoutChildrenDoesNotError(): void
    {
        $data = $this->validSymbolData();
        unset($data['data']['dproot1']['children']);
        $result = SymbolValidator::validate($data);
        // dpchild1 will be orphaned but shouldn't error
        $this->assertTrue($result['valid']);
    }
}
