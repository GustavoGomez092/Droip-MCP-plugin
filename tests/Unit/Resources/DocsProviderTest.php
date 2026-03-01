<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Resources;

use DroipBridge\Resources\DocsProvider;
use PHPUnit\Framework\TestCase;

class DocsProviderTest extends TestCase
{
    public function testGetResourcesReturns5Resources(): void
    {
        $resources = DocsProvider::getResources();
        $this->assertCount(5, $resources);
    }

    public function testGetResourcesReturnsResourceInstances(): void
    {
        $resources = DocsProvider::getResources();
        foreach ($resources as $resource) {
            $this->assertInstanceOf(\Mcp\Types\Resource::class, $resource);
        }
    }

    public function testGetResourcesHasCorrectUris(): void
    {
        $resources = DocsProvider::getResources();
        $uris = array_map(fn($r) => $r->uri, $resources);

        $expected = [
            'droip://docs/quick-start',
            'droip://docs/symbol-schema',
            'droip://docs/element-types',
            'droip://docs/style-system',
            'droip://docs/animations-interactions',
        ];
        foreach ($expected as $uri) {
            $this->assertContains($uri, $uris);
        }
    }

    public function testGetResourcesHaveMimeType(): void
    {
        $resources = DocsProvider::getResources();
        foreach ($resources as $resource) {
            $this->assertSame('text/markdown', $resource->mimeType);
        }
    }

    public function testGetResourcesHaveNames(): void
    {
        $resources = DocsProvider::getResources();
        foreach ($resources as $resource) {
            $this->assertNotEmpty($resource->name);
        }
    }

    public function testGetResourcesHaveDescriptions(): void
    {
        $resources = DocsProvider::getResources();
        foreach ($resources as $resource) {
            $this->assertNotEmpty($resource->description);
        }
    }

    public function testHandleReadValidUri(): void
    {
        $result = DocsProvider::handleRead('droip://docs/quick-start');
        $this->assertInstanceOf(\Mcp\Types\ReadResourceResult::class, $result);
    }

    public function testHandleReadReturnsMarkdownContent(): void
    {
        $result = DocsProvider::handleRead('droip://docs/quick-start');
        // The result contains TextResourceContents
        $contents = $result->contents;
        $this->assertNotEmpty($contents);
        $this->assertSame('text/markdown', $contents[0]->mimeType);
    }

    public function testHandleReadQuickStartHasContent(): void
    {
        $result = DocsProvider::handleRead('droip://docs/quick-start');
        $text = $result->contents[0]->text;
        $this->assertNotEmpty($text);
        $this->assertStringContainsString('symbol', strtolower($text));
    }

    public function testHandleReadElementTypes(): void
    {
        $result = DocsProvider::handleRead('droip://docs/element-types');
        $text = $result->contents[0]->text;
        $this->assertNotEmpty($text);
    }

    public function testHandleReadSymbolSchema(): void
    {
        $result = DocsProvider::handleRead('droip://docs/symbol-schema');
        $text = $result->contents[0]->text;
        $this->assertNotEmpty($text);
    }

    public function testHandleReadStyleSystem(): void
    {
        $result = DocsProvider::handleRead('droip://docs/style-system');
        $text = $result->contents[0]->text;
        $this->assertNotEmpty($text);
    }

    public function testHandleReadAnimationsInteractions(): void
    {
        $result = DocsProvider::handleRead('droip://docs/animations-interactions');
        $text = $result->contents[0]->text;
        $this->assertNotEmpty($text);
    }

    public function testHandleReadInvalidUri(): void
    {
        $result = DocsProvider::handleRead('droip://docs/nonexistent');
        $text = $result->contents[0]->text;
        $this->assertStringContainsString('Resource not found', $text);
        $this->assertSame('text/plain', $result->contents[0]->mimeType);
    }

    public function testHandleReadCompletelyInvalidUri(): void
    {
        $result = DocsProvider::handleRead('invalid://uri');
        $text = $result->contents[0]->text;
        $this->assertStringContainsString('Resource not found', $text);
    }
}
