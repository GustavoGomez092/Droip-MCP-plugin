<?php
/**
 * Fluent API for composing complete Droip symbols.
 *
 * A symbol is a reusable component stored as a droip_symbol post type.
 * Its data structure includes a flat element map, style blocks, a root ID,
 * and metadata like name, category, setAs, and customFonts.
 *
 * @package DroipBridge\Builders
 */

declare(strict_types=1);

namespace DroipBridge\Builders;

use Droip\Ajax\Symbol;

class SymbolBuilder
{
    private string $name;
    private string $category;
    private string $setAs = '';
    private string $rootTag = 'div';
    private string $rootId;
    private array $data = [];
    private array $styleBlocks = [];
    private array $customFonts = [];
    private array $rootStyleIds = [];

    public function __construct(string $name, string $category = 'other')
    {
        $this->name = $name;
        $this->category = $category;
        $this->rootId = IdGenerator::elementId();

        // Initialize root element
        $this->data[$this->rootId] = ElementFactory::section(
            $this->rootId,
            null,
            $name,
            ['tag' => $this->rootTag]
        );
    }

    /**
     * Get the root element ID.
     */
    public function getRootId(): string
    {
        return $this->rootId;
    }

    /**
     * Set the symbol role (e.g., 'header', 'footer', or '' for none).
     */
    public function setAs(string $role): self
    {
        $this->setAs = $role;
        return $this;
    }

    /**
     * Set the root element's HTML tag.
     */
    public function setRootTag(string $tag): self
    {
        $this->rootTag = $tag;
        $this->data[$this->rootId]['properties']['tag'] = $tag;
        $this->data[$this->rootId]['name'] = match ($tag) {
            'header' => 'section',
            'footer' => 'section',
            'section' => 'section',
            'nav' => 'div',
            default => 'div',
        };
        return $this;
    }

    /**
     * Add an element to the data map. Does NOT wire parent-child automatically.
     * Call addChild() separately or pass children via opts.
     */
    public function addElement(array $element): self
    {
        $this->data[$element['id']] = $element;
        return $this;
    }

    /**
     * Wire a parent-child relationship by appending $childId to the parent's children array.
     */
    public function addChild(string $parentId, string $childId): self
    {
        if (isset($this->data[$parentId])) {
            if (!isset($this->data[$parentId]['children'])) {
                $this->data[$parentId]['children'] = [];
            }
            if (!in_array($childId, $this->data[$parentId]['children'], true)) {
                $this->data[$parentId]['children'][] = $childId;
            }
        }
        return $this;
    }

    /**
     * Add a style block to the symbol.
     */
    public function addStyleBlock(array $styleBlock): self
    {
        $this->styleBlocks[$styleBlock['id']] = $styleBlock;
        return $this;
    }

    /**
     * Set style IDs on the root element.
     */
    public function setRootStyleIds(array $ids): self
    {
        $this->rootStyleIds = $ids;
        $this->data[$this->rootId]['styleIds'] = $ids;
        return $this;
    }

    /**
     * Add custom fonts used by this symbol.
     */
    public function addCustomFont(string $family, string $fontUrl, array $variants = []): self
    {
        $this->customFonts[$family] = [
            'fontUrl'  => $fontUrl,
            'family'   => $family,
            'variants' => $variants,
        ];
        return $this;
    }

    /**
     * Build the complete symbol data structure (without saving).
     */
    public function build(): array
    {
        return [
            'symbolData' => [
                'name'        => $this->name,
                'category'    => $this->category,
                'root'        => $this->rootId,
                'setAs'       => $this->setAs,
                'customFonts' => $this->customFonts,
                'data'        => $this->data,
                'styleBlocks' => $this->styleBlocks,
            ],
        ];
    }

    /**
     * Save the symbol to the WordPress database via Droip's API.
     *
     * @return array{id: int, symbolData: array, type: string, html: string}|null
     */
    public function save(): ?array
    {
        $payload = $this->build();
        return Symbol::save_to_db($payload);
    }
}
