<?php

declare(strict_types=1);

namespace DroipBridge\Tests\Unit\Builders;

use DroipBridge\Builders\ElementFactory;
use PHPUnit\Framework\TestCase;

class ElementFactoryTest extends TestCase
{
    // ── Base Element Structure ─────────────────────────────────────────

    public function testBaseElementHasAllRequiredFields(): void
    {
        $el = ElementFactory::frame('dp123456', 'dpparent');
        $this->assertSame('dp123456', $el['id']);
        $this->assertSame('dpparent', $el['parentId']);
        $this->assertSame('div', $el['name']);
        $this->assertSame('droip', $el['source']);
        $this->assertTrue($el['visibility']);
        $this->assertFalse($el['collapse']);
        $this->assertIsArray($el['properties']);
        $this->assertIsArray($el['styleIds']);
        $this->assertSame('', $el['className']);
        $this->assertIsArray($el['stylePanels']);
    }

    public function testBaseElementStylePanelsHasAllPanels(): void
    {
        $el = ElementFactory::frame('dp123456', null);
        $expected = [
            'typography', 'composition', 'size', 'background',
            'stroke', 'shadow', 'effects', 'position',
            'transform', 'interaction', 'animation',
        ];
        foreach ($expected as $panel) {
            $this->assertTrue($el['stylePanels'][$panel]);
        }
    }

    public function testBaseElementExcludesChildrenWhenEmpty(): void
    {
        $el = ElementFactory::heading('dp123456', null, 'Hello');
        $this->assertArrayNotHasKey('children', $el);
    }

    public function testBaseElementIncludesChildrenWhenProvided(): void
    {
        $el = ElementFactory::frame('dp123456', null, ['children' => ['dpchild1']]);
        $this->assertSame(['dpchild1'], $el['children']);
    }

    public function testBaseElementHasSymbolElPropId(): void
    {
        $el = ElementFactory::frame('dp123456', null);
        $this->assertArrayHasKey('symbolElPropId', $el['properties']);
        $this->assertStringStartsWith('sep', $el['properties']['symbolElPropId']);
    }

    public function testBaseElementRespectsCustomSymbolElPropId(): void
    {
        $el = ElementFactory::frame('dp123456', null, ['symbolElPropId' => 'sepcustom']);
        $this->assertSame('sepcustom', $el['properties']['symbolElPropId']);
    }

    public function testBaseElementRespectsStyleIds(): void
    {
        $el = ElementFactory::frame('dp123456', null, ['styleIds' => ['mcpbr_dp1', 'mcpbr_dp2']]);
        $this->assertSame(['mcpbr_dp1', 'mcpbr_dp2'], $el['styleIds']);
    }

    public function testBaseElementRespectsClassName(): void
    {
        $el = ElementFactory::frame('dp123456', null, ['className' => 'my-class']);
        $this->assertSame('my-class', $el['className']);
    }

    public function testBaseElementRespectsVisibility(): void
    {
        $el = ElementFactory::frame('dp123456', null, ['visibility' => false]);
        $this->assertFalse($el['visibility']);
    }

    public function testBaseElementRespectsTitle(): void
    {
        $el = ElementFactory::frame('dp123456', null, ['title' => 'Custom Title']);
        $this->assertSame('Custom Title', $el['title']);
    }

    public function testBaseElementDefaultTitleFromName(): void
    {
        $el = ElementFactory::linkBlock('dp123456', null, '#');
        $this->assertSame('Link block', $el['title']);
    }

    public function testBaseElementRespectsHide(): void
    {
        $el = ElementFactory::frame('dp123456', null, ['hide' => ['mobile' => true]]);
        $this->assertSame(['mobile' => true], $el['hide']);
    }

    public function testBaseElementWithExtraProperties(): void
    {
        $el = ElementFactory::frame('dp123456', null, [
            'properties' => ['customProp' => 'value'],
        ]);
        $this->assertSame('value', $el['properties']['customProp']);
    }

    public function testNullParentId(): void
    {
        $el = ElementFactory::frame('dp123456', null);
        $this->assertNull($el['parentId']);
    }

    // ── Container Elements ─────────────────────────────────────────────

    public function testFrame(): void
    {
        $el = ElementFactory::frame('dp123456', 'dpparent');
        $this->assertSame('div', $el['name']);
        $this->assertSame('div', $el['properties']['tag']);
    }

    public function testFrameCustomTag(): void
    {
        $el = ElementFactory::frame('dp123456', null, ['tag' => 'nav']);
        $this->assertSame('nav', $el['properties']['tag']);
    }

    public function testSection(): void
    {
        $el = ElementFactory::section('dp123456', null, 'My Section');
        $this->assertSame('section', $el['name']);
        $this->assertSame('section', $el['properties']['tag']);
        $this->assertSame('My Section', $el['title']);
    }

    public function testSectionDefaultTitle(): void
    {
        $el = ElementFactory::section('dp123456', null);
        $this->assertSame('Section', $el['title']);
    }

    // ── Text Elements ──────────────────────────────────────────────────

    public function testHeading(): void
    {
        $el = ElementFactory::heading('dp123456', 'dpparent', 'Hello World');
        $this->assertSame('heading', $el['name']);
        $this->assertSame('h2', $el['properties']['tag']);
        $this->assertSame(['Hello World'], $el['properties']['contents']);
        $this->assertArrayNotHasKey('children', $el);
    }

    public function testHeadingCustomTag(): void
    {
        $el = ElementFactory::heading('dp123456', null, 'Title', 'h1');
        $this->assertSame('h1', $el['properties']['tag']);
    }

    public function testParagraph(): void
    {
        $el = ElementFactory::paragraph('dp123456', 'dpparent', 'Some text');
        $this->assertSame('paragraph', $el['name']);
        $this->assertSame('p', $el['properties']['tag']);
        $this->assertSame(['Some text'], $el['properties']['contents']);
        $this->assertArrayNotHasKey('children', $el);
    }

    public function testLinkText(): void
    {
        $el = ElementFactory::linkText('dp123456', 'dpparent', 'Click me', 'https://example.com');
        $this->assertSame('link-text', $el['name']);
        $this->assertSame('a', $el['properties']['tag']);
        $this->assertSame(['Click me'], $el['properties']['contents']);
        $this->assertSame('https://example.com', $el['properties']['attributes']['href']);
        $this->assertSame('href', $el['properties']['type']);
        $this->assertFalse($el['properties']['isActive']);
        $this->assertSame('default', $el['properties']['preload']);
    }

    public function testLinkTextWithTarget(): void
    {
        $el = ElementFactory::linkText('dp123456', null, 'Link', '#', ['target' => '_blank']);
        $this->assertSame('_blank', $el['properties']['attributes']['target']);
    }

    // ── Interactive Elements ───────────────────────────────────────────

    public function testButton(): void
    {
        $el = ElementFactory::button('dp123456', 'dpparent', 'Click Me');
        $this->assertSame('button', $el['name']);
        $this->assertSame('button', $el['properties']['tag']);
        $this->assertSame(['Click Me'], $el['properties']['contents']);
        $this->assertArrayHasKey('_extra_elements', $el);
        $this->assertNotEmpty($el['children']);
    }

    public function testButtonCreatesTextChild(): void
    {
        $el = ElementFactory::button('dp123456', null, 'Submit');
        $textId = $el['children'][0];
        $this->assertArrayHasKey($textId, $el['_extra_elements']);

        $textEl = $el['_extra_elements'][$textId];
        $this->assertSame('text', $textEl['name']);
        $this->assertSame('span', $textEl['properties']['tag']);
        $this->assertSame(['Submit'], $textEl['properties']['contents']);
        $this->assertSame('dp123456', $textEl['parentId']);
    }

    public function testButtonWithCustomTextId(): void
    {
        $el = ElementFactory::button('dp123456', null, 'Go', ['textId' => 'dpcustom']);
        $this->assertContains('dpcustom', $el['children']);
        $this->assertArrayHasKey('dpcustom', $el['_extra_elements']);
    }

    public function testButtonWithHref(): void
    {
        $el = ElementFactory::button('dp123456', null, 'Go', [
            'href'   => 'https://example.com',
            'target' => '_blank',
        ]);
        $this->assertSame('href', $el['properties']['type']);
        $this->assertSame('https://example.com', $el['properties']['attributes']['href']);
        $this->assertSame('_blank', $el['properties']['attributes']['target']);
    }

    public function testLinkBlock(): void
    {
        $el = ElementFactory::linkBlock('dp123456', 'dpparent', 'https://example.com');
        $this->assertSame('link-block', $el['name']);
        $this->assertSame('a', $el['properties']['tag']);
        $this->assertSame('https://example.com', $el['properties']['attributes']['href']);
        $this->assertSame('href', $el['properties']['type']);
        $this->assertFalse($el['properties']['isActive']);
    }

    public function testLinkBlockWithOptions(): void
    {
        $el = ElementFactory::linkBlock('dp123456', null, '/page', [
            'linkType' => 'page',
            'target'   => '_blank',
            'preload'  => 'hover',
        ]);
        $this->assertSame('page', $el['properties']['type']);
        $this->assertSame('_blank', $el['properties']['attributes']['target']);
        $this->assertSame('hover', $el['properties']['preload']);
    }

    public function testLinkBlockWithDynamicContent(): void
    {
        $el = ElementFactory::linkBlock('dp123456', null, '#', [
            'dynamicContent' => ['type' => 'post', 'value' => 'permalink'],
        ]);
        $this->assertSame(['type' => 'post', 'value' => 'permalink'], $el['properties']['dynamicContent']);
    }

    // ── Media Elements ─────────────────────────────────────────────────

    public function testImage(): void
    {
        $el = ElementFactory::image('dp123456', 'dpparent', 'https://img.com/photo.jpg', 'A photo');
        $this->assertSame('image', $el['name']);
        $this->assertSame('img', $el['properties']['tag']);
        $this->assertTrue($el['properties']['noEndTag']);
        $this->assertSame('https://img.com/photo.jpg', $el['properties']['attributes']['src']);
        $this->assertSame('A photo', $el['properties']['attributes']['alt']);
        $this->assertSame('lazy', $el['properties']['load']);
    }

    public function testImageWithOptions(): void
    {
        $el = ElementFactory::image('dp123456', null, 'img.jpg', '', [
            'load'             => 'eager',
            'hiDPIStatus'      => true,
            'width'            => ['value' => '100', 'unit' => 'px'],
            'height'           => ['value' => '50', 'unit' => 'px'],
            'href'             => 'https://example.com',
            'wp_attachment_id' => 42,
        ]);
        $this->assertSame('eager', $el['properties']['load']);
        $this->assertTrue($el['properties']['hiDPIStatus']);
        $this->assertSame(['value' => '100', 'unit' => 'px'], $el['properties']['width']);
        $this->assertSame(42, $el['properties']['wp_attachment_id']);
    }

    public function testVideo(): void
    {
        $el = ElementFactory::video('dp123456', 'dpparent', 'video.mp4');
        $this->assertSame('video', $el['name']);
        $this->assertSame('video', $el['properties']['tag']);
        $this->assertSame('video.mp4', $el['properties']['attributes']['src']);
        $this->assertTrue($el['properties']['attributes']['controls']);
        $this->assertFalse($el['properties']['attributes']['autoplay']);
    }

    public function testVideoWithOptions(): void
    {
        $el = ElementFactory::video('dp123456', null, 'v.mp4', [
            'controls' => false,
            'autoplay' => true,
            'loop'     => true,
            'muted'    => true,
        ]);
        $this->assertFalse($el['properties']['attributes']['controls']);
        $this->assertTrue($el['properties']['attributes']['autoplay']);
        $this->assertTrue($el['properties']['attributes']['loop']);
        $this->assertTrue($el['properties']['attributes']['muted']);
    }

    public function testSvg(): void
    {
        $el = ElementFactory::svg('dp123456', 'dpparent', '<svg><circle/></svg>');
        $this->assertSame('svg', $el['name']);
        $this->assertSame('svg', $el['properties']['tag']);
        $this->assertSame('<svg><circle/></svg>', $el['properties']['svgOuterHtml']);
    }

    public function testIcon(): void
    {
        $el = ElementFactory::icon('dp123456', 'dpparent', 'fa-star');
        $this->assertSame('svg-icon', $el['name']);
        $this->assertSame('i', $el['properties']['tag']);
        $this->assertSame('fa-star', $el['properties']['iconClass']);
    }

    // ── Form Elements ──────────────────────────────────────────────────

    public function testForm(): void
    {
        $el = ElementFactory::form('dp123456', 'dpparent');
        $this->assertSame('form', $el['name']);
        $this->assertSame('form', $el['properties']['tag']);
    }

    public function testInput(): void
    {
        $el = ElementFactory::input('dp123456', 'dpparent', 'email', 'user_email');
        $this->assertSame('input', $el['name']);
        $this->assertSame('input', $el['properties']['tag']);
        $this->assertSame('email', $el['properties']['attributes']['type']);
        $this->assertSame('user_email', $el['properties']['attributes']['name']);
    }

    public function testInputWithPlaceholder(): void
    {
        $el = ElementFactory::input('dp123456', null, 'text', 'name', ['placeholder' => 'Enter name']);
        $this->assertSame('Enter name', $el['properties']['attributes']['placeholder']);
    }

    public function testTextarea(): void
    {
        $el = ElementFactory::textarea('dp123456', 'dpparent', 'message');
        $this->assertSame('textarea', $el['name']);
        $this->assertSame('textarea', $el['properties']['tag']);
        $this->assertSame('message', $el['properties']['attributes']['name']);
    }

    public function testTextareaWithPlaceholder(): void
    {
        $el = ElementFactory::textarea('dp123456', null, 'msg', ['placeholder' => 'Type here']);
        $this->assertSame('Type here', $el['properties']['attributes']['placeholder']);
    }

    public function testSelect(): void
    {
        $options = [['value' => 'a', 'label' => 'Option A']];
        $el = ElementFactory::select('dp123456', 'dpparent', 'choice', $options);
        $this->assertSame('select', $el['name']);
        $this->assertSame('select', $el['properties']['tag']);
        $this->assertSame('choice', $el['properties']['attributes']['name']);
        $this->assertSame($options, $el['properties']['options']);
    }

    // ── Advanced Elements ──────────────────────────────────────────────

    public function testCustomCode(): void
    {
        $el = ElementFactory::customCode('dp123456', 'dpparent', '<div>Custom</div>');
        $this->assertSame('custom-code', $el['name']);
        $this->assertSame('div', $el['properties']['tag']);
        $this->assertSame('<div>Custom</div>', $el['properties']['content']);
        $this->assertSame('code', $el['properties']['data-type']);
    }

    public function testSymbolInstance(): void
    {
        $el = ElementFactory::symbolInstance('dp123456', 'dpparent', 42);
        $this->assertSame('symbol', $el['name']);
        $this->assertSame('div', $el['properties']['tag']);
        $this->assertSame(42, $el['properties']['symbolId']);
    }

    // ── Collection Elements ────────────────────────────────────────────

    public function testCollection(): void
    {
        $el = ElementFactory::collection('dp123456', 'dpparent', ['collectionType' => 'post']);
        $this->assertSame('collection', $el['name']);
        $this->assertSame('div', $el['properties']['tag']);
        $this->assertSame('post', $el['properties']['dynamicContent']['collectionType']);
        $this->assertTrue($el['template_mounted']);
    }

    public function testCollectionDefaults(): void
    {
        $el = ElementFactory::collection('dp123456', null, []);
        $dc = $el['properties']['dynamicContent'];
        $this->assertSame('post', $dc['collectionType']);
        $this->assertSame('6', $dc['items']);
        $this->assertTrue($dc['pagination']);
        $this->assertSame([], $dc['filters']);
        $this->assertSame('0', $dc['offset']);
        $this->assertFalse($dc['inherit']);
    }

    public function testCollectionItems(): void
    {
        $el = ElementFactory::collectionItems('dp123456', 'dpparent');
        $this->assertSame('items', $el['name']);
        $this->assertTrue($el['template_mounted']);
    }

    public function testCollectionItem(): void
    {
        $el = ElementFactory::collectionItem('dp123456', 'dpparent');
        $this->assertSame('item', $el['name']);
        $this->assertTrue($el['template_mounted']);
    }

    public function testPagination(): void
    {
        $el = ElementFactory::pagination('dp123456', 'dpparent');
        $this->assertSame('pagination', $el['name']);
        $this->assertSame('pagination', $el['properties']['componentType']);
        $this->assertArrayHasKey('data-droip-pagination', $el['properties']['customAttributes']);
        $this->assertTrue($el['template_mounted']);
    }

    public function testPaginationItem(): void
    {
        $el = ElementFactory::paginationItem('dp123456', 'dpparent');
        $this->assertSame('pagination-item', $el['name']);
        $this->assertTrue($el['template_mounted']);
    }

    public function testPaginationNumber(): void
    {
        $el = ElementFactory::paginationNumber('dp123456', 'dpparent');
        $this->assertSame('pagination-number', $el['name']);
        $this->assertTrue($el['template_mounted']);
    }

    public function testEmptyState(): void
    {
        $el = ElementFactory::emptyState('dp123456', 'dpparent');
        $this->assertSame('empty', $el['name']);
        $this->assertTrue($el['template_mounted']);
    }

    // ── Dynamic Content Helper ─────────────────────────────────────────

    public function testWithDynamicContent(): void
    {
        $el = ElementFactory::heading('dp123456', null, 'Title');
        $el = ElementFactory::withDynamicContent($el, 'post', 'post_title');
        $this->assertSame('post', $el['properties']['dynamicContent']['type']);
        $this->assertSame('post_title', $el['properties']['dynamicContent']['value']);
        $this->assertTrue($el['template_mounted']);
    }

    public function testWithDynamicContentAuthor(): void
    {
        $el = ElementFactory::paragraph('dp123456', null, 'Bio');
        $el = ElementFactory::withDynamicContent($el, 'author', 'display_name');
        $this->assertSame('author', $el['properties']['dynamicContent']['type']);
        $this->assertSame('display_name', $el['properties']['dynamicContent']['value']);
    }
}
