# Droip Element Types Reference

## Required Element Fields

Every element in Droip must include these fields. Omitting any of them may cause rendering or editor issues.

| Field | Type | Value | Description |
|-------|------|-------|-------------|
| `id` | string | `"dp"` + 6 alphanum | Unique element identifier |
| `name` | string | Element type name | One of the types documented below |
| `title` | string | Human-readable label | Shown in the editor layers panel |
| `parentId` | string\|null | Parent element ID | `null` for root element only |
| `children` | string[] | Child element IDs | **Container elements only.** See critical note below. |
| `properties` | object | Varies by type | See each element type below |
| `styleIds` | string[] | Style block IDs | References to style blocks applied to this element |
| `className` | string | `""` | Additional CSS class names (usually empty) |
| `source` | string | `"droip"` | Always `"droip"` |
| `visibility` | boolean | `true` | **Required.** Controls whether the element renders on the front end. Must be explicitly set — omitting it may cause the element to not render. |
| `collapse` | boolean | `false` | Controls editor layer panel collapse state |
| `stylePanels` | object | Default panel flags | Editor panel visibility flags (see below) |

### CRITICAL: `children` Key Behavior

Droip's renderer uses the **presence** of the `children` key to decide how to render an element:
- If `children` key **exists** (even as `[]`): the renderer iterates child elements and **ignores `contents`**
- If `children` key is **absent**: the renderer outputs the text from `properties.contents`

**Rules:**
- **Container elements** (`div`, `section`, `form`, `link-block`, `collection`, `items`, `item`, `pagination`, `pagination-item`, `empty`): MUST have `children` array with child element IDs
- **Text/leaf elements** (`heading`, `paragraph`, `link-text`, `text`): MUST NOT have a `children` key — omit it entirely so their `contents` text renders
- **Button elements**: MUST have `children` with at least one `text` child element. The button renderer ignores `contents` and only renders children. See the `button` section below.
- **Other leaf elements** (`image`, `video`, `svg`, `svg-icon`, `input`, `textarea`, `select`, `custom-code`, `symbol`, `pagination-number`): SHOULD NOT have a `children` key

Setting `children: []` on a heading or paragraph will cause it to render as empty — no text will appear.

### Optional Fields

| Field | Type | Description |
|-------|------|-------------|
| `hide` | boolean | Distinct from `visibility`. When `true`, hides the element in the editor's layer panel only. The element may still render on the front end. Used for utility elements like custom-code style blocks. |
| `template_mounted` | boolean | Set to `true` on collection-related elements (`collection`, `items`, `item`, `pagination`, `pagination-item`, `pagination-number`, `empty`) and link-blocks with dynamic content. Indicates the element is part of a dynamic content template. |

### Default stylePanels Object

```json
{
  "typography": true,
  "composition": true,
  "size": true,
  "background": true,
  "stroke": true,
  "shadow": true,
  "effects": true,
  "position": true,
  "transform": true,
  "interaction": true,
  "animation": true
}
```

---

## Container Elements

### `div` (Frame/Container)
- **Tag**: `div` (or any block element)
- **Purpose**: Generic layout container
- **Properties**: `{ tag: "div" }`
- **Children**: Any elements
- **Notes**: Most common container type. Use for flexbox/grid layouts.

### `section`
- **Tag**: `section`
- **Purpose**: Semantic section container, commonly used as root elements
- **Properties**: `{ tag: "section" }`
- **Children**: Any elements
- **Notes**: Used as the root element for most symbols.

## Text Elements

### `heading`
- **Tag**: `h1`–`h6`
- **Purpose**: Headings and titles
- **Properties**: `{ tag: "h1"|"h2"|..., contents: ["Heading text"] }`
- **Children**: None (leaf element)
- **Notes**: The `contents` array contains one or more text strings. Supports `dynamicContent` (see Dynamic Content section).

### `paragraph`
- **Tag**: `p`
- **Purpose**: Body text
- **Properties**: `{ tag: "p", contents: ["Paragraph text"] }`
- **Children**: None (leaf element)
- **Notes**: Supports `dynamicContent` (see Dynamic Content section).

### `link-text`
- **Tag**: `a`
- **Purpose**: Text hyperlinks
- **Properties**: `{ tag: "a", contents: ["Link text"], type: "href", attributes: { href: "/url", target: "" } }`
- **Children**: None

## Interactive Elements

### `button`
- **Tag**: `button` or `a`
- **Purpose**: Clickable buttons
- **Properties**:
  ```json
  {
    "tag": "button",
    "contents": ["Button text"],
    "type": "href",
    "attributes": { "href": "#", "target": "" }
  }
  ```
- **Children**: **MUST have a `text` child element** to display the button label. The button renderer ignores `contents` and only renders children.
- **Required `text` child**:
  ```json
  {
    "name": "text",
    "title": "Text",
    "properties": { "tag": "span", "contents": ["Button text"] }
  }
  ```
  The `text` child must NOT have a `children` key (it's a leaf element that renders via `contents`).
- **Notes**: When using `type: "href"`, the button acts as a link. The bridge's pre-save cleanup automatically creates a `text` child if one is missing.

### `link-block`
- **Tag**: `a`
- **Purpose**: Clickable container block (wraps other elements in a link)
- **Properties (static link)**:
  ```json
  {
    "tag": "a",
    "type": "href",
    "isActive": false,
    "preload": "default",
    "attributes": { "href": "/", "target": "" }
  }
  ```
- **Properties (dynamic page link)**:
  ```json
  {
    "tag": "a",
    "type": "page",
    "isActive": false,
    "preload": "default",
    "attributes": { "href": "/", "target": "" }
  }
  ```
- **Children**: Any elements (images, text, etc.)
- **Notes**: Use `type: "page"` for dynamic content links (e.g., inside collection items that link to their post). When used with dynamic content, also set `template_mounted: true` on the element and add `dynamicContent` to properties.

## Media Elements

### `image`
- **Tag**: `img`
- **Purpose**: Images
- **Properties**:
  ```json
  {
    "tag": "img",
    "noEndTag": true,
    "type": "href",
    "load": "lazy",
    "hiDPIStatus": false,
    "width": { "value": "", "unit": "auto" },
    "height": { "value": "", "unit": "auto" },
    "attributes": {
      "src": "https://example.com/image.jpg",
      "alt": "Description",
      "href": "",
      "target": ""
    }
  }
  ```
- **Children**: None
- **Notes**:
  - `noEndTag: true` — img is a void element
  - `type: "href"` — enables optional link wrapping behavior
  - `load: "lazy"` — lazy loading (use `"eager"` for above-the-fold images)
  - `hiDPIStatus: false` — retina/HiDPI image support toggle
  - `width`/`height` — dimension objects with `value` (number or `""`) and `unit` (`"auto"`, `"px"`, `"%"`)
  - `attributes.href` / `attributes.target` — optional link on image click
  - `wp_attachment_id` (int) — WordPress media library attachment ID, set when image is from the WP media library
  - Supports `dynamicContent` for featured images (see Dynamic Content section)

### `video`
- **Tag**: `video`
- **Purpose**: Video player
- **Properties**: `{ tag: "video", attributes: { src: "url", controls: true } }`
- **Children**: None

### `svg`
- **Tag**: `svg`
- **Purpose**: Inline SVG graphics
- **Properties**: `{ tag: "svg", svgOuterHtml: "<svg>...</svg>" }`
- **Children**: None
- **Notes**: Full SVG markup goes in `svgOuterHtml`.

### `svg-icon`
- **Tag**: `i`
- **Purpose**: Icon elements
- **Properties**: `{ tag: "i", iconClass: "icon-name" }`
- **Children**: None

## Form Elements

### `form`
- **Tag**: `form`
- **Purpose**: Form container
- **Properties**: `{ tag: "form" }`
- **Children**: Input, textarea, select, button elements

### `input`
- **Tag**: `input`
- **Purpose**: Form input fields
- **Properties**: `{ tag: "input", attributes: { type: "text", name: "field_name", placeholder: "Enter..." } }`
- **Children**: None

### `textarea`
- **Tag**: `textarea`
- **Purpose**: Multi-line text input
- **Properties**: `{ tag: "textarea", attributes: { name: "field_name", placeholder: "Enter..." } }`
- **Children**: None

### `select`
- **Tag**: `select`
- **Purpose**: Dropdown select menu
- **Properties**: `{ tag: "select", attributes: { name: "field_name" }, options: [...] }`
- **Children**: None

## Advanced Elements

### `custom-code`
- **Tag**: `div`
- **Purpose**: Raw HTML/CSS/JS code block
- **Properties**:
  ```json
  {
    "tag": "div",
    "content": "<div class=\"my-widget\">Custom HTML here</div>",
    "data-type": "code"
  }
  ```
- **Children**: None
- **Notes**: Uses `content` (singular string), NOT `contents` (array). The `data-type` property must be `"code"`.

### `symbol`
- **Tag**: `div`
- **Purpose**: Instance of another symbol (reusable component reference)
- **Properties**: `{ tag: "div", symbolId: 123 }`
- **Children**: None (rendered from referenced symbol)

### `slider` / `slider_mask`
- **Purpose**: Carousel/slider components
- **Children**: Slide items

---

## Collection System Elements

The collection system powers dynamic content repeaters — blog post grids, product listings, team member sections, etc. Collections connect to WordPress content (posts, pages, custom post types) and repeat a template for each item.

### Required Hierarchy

```
collection
├── items
│   └── item (template — repeated for each content item)
│       └── ... (any elements: headings, images, link-blocks, etc.)
├── pagination
│   └── pagination-item
│       └── pagination-number
└── empty (shown when no results)
    └── ... (any elements for empty state)
```

### `collection`
- **Tag**: `div`
- **Purpose**: Dynamic content repeater connected to WordPress content
- **Properties**:
  ```json
  {
    "tag": "div",
    "dynamicContent": {
      "collectionType": "post",
      "items": "6",
      "pagination": true,
      "filters": [],
      "sorting": { "orderby": "date", "order": "DESC" },
      "offset": "0",
      "taxonomy": {},
      "inherit": false
    },
    "uiState": { "open": true }
  }
  ```
- **Children**: `["<items-id>", "<pagination-id>", "<empty-id>"]`
- **Notes**: Set `template_mounted: true` on this element. `collectionType` can be `"post"`, `"page"`, or any custom post type slug. Set `inherit: true` to inherit query from the current archive/taxonomy page.

### `items`
- **Tag**: `div`
- **Purpose**: Collection items wrapper — contains the item template
- **Properties**: `{ tag: "div" }`
- **Children**: `["<item-id>"]` (single item template)
- **Notes**: Set `template_mounted: true`. Direct child of `collection`.

### `item`
- **Tag**: `div`
- **Purpose**: Single collection item template — repeated for each content item
- **Properties**: `{ tag: "div" }`
- **Children**: Any elements (headings, images, link-blocks with dynamic content)
- **Notes**: Set `template_mounted: true`. Direct child of `items`. Elements inside this template use `dynamicContent` to pull in post data.

### `pagination`
- **Tag**: `div`
- **Purpose**: Pagination container
- **Properties**:
  ```json
  {
    "tag": "div",
    "componentType": "pagination",
    "customAttributes": { "data-droip-pagination": "" }
  }
  ```
- **Children**: `["<pagination-item-id>"]`
- **Notes**: Set `template_mounted: true`. Direct child of `collection`.

### `pagination-item`
- **Tag**: `div`
- **Purpose**: Pagination item wrapper
- **Properties**: `{ tag: "div" }`
- **Children**: `["<pagination-number-id>"]`
- **Notes**: Set `template_mounted: true`. Direct child of `pagination`.

### `pagination-number`
- **Tag**: `div`
- **Purpose**: Pagination number display
- **Properties**: `{ tag: "div" }`
- **Children**: `[]`
- **Notes**: Set `template_mounted: true`. Direct child of `pagination-item`.

### `empty`
- **Tag**: `div`
- **Purpose**: Empty state container — shown when collection has no results
- **Properties**: `{ tag: "div" }`
- **Children**: Any elements (e.g., a paragraph saying "No posts found")
- **Notes**: Set `template_mounted: true`. Direct child of `collection`.

---

## Dynamic Content

Dynamic content connects elements to WordPress data, replacing static text/images with post fields. It is used inside collection item templates and can also be used standalone.

### Supported Elements

Dynamic content can be added to: `heading`, `paragraph`, `image`, `link-block`.

### Property Structure

Add `dynamicContent` to the element's `properties` object:

```json
{
  "tag": "h2",
  "contents": [""],
  "dynamicContent": {
    "type": "post",
    "value": "post_title"
  }
}
```

### Available Dynamic Content Values

#### Post fields (`type: "post"`)
| Value | Use with | Description |
|-------|----------|-------------|
| `post_title` | heading, paragraph | Post/page title |
| `post_content` | paragraph | Full post content |
| `post_excerpt` | paragraph | Post excerpt |
| `post_author` | heading, paragraph | Author display name |
| `featured_image` | image | Post featured/thumbnail image |
| `post_page_link` | link-block | Post permalink URL |

#### Author fields (`type: "author"`)
| Value | Use with | Description |
|-------|----------|-------------|
| `author_profile_picture` | image | Author avatar/profile image |

### Example: Blog Post Card with Dynamic Content

```json
{
  "tag": "a",
  "type": "page",
  "isActive": false,
  "preload": "default",
  "attributes": { "href": "/", "target": "" },
  "dynamicContent": {
    "type": "post",
    "value": "post_page_link"
  }
}
```

Elements with dynamic content inside collections should have `template_mounted: true` set on the element node.

---

## Common Property Patterns

### Custom Attributes
Any element can have custom HTML attributes:
```json
{
  "customAttributes": {
    "data-animate": "fade-in",
    "aria-label": "Navigation"
  }
}
```

### Interactions
Elements can have interaction definitions (hover animations, scroll triggers, click
animations, page load effects, etc.) stored in `properties.interactions`. Use the
`droip_get_animation_guide` tool for the complete interaction data structure,
supported trigger types, animation properties, and working examples.

### symbolElPropId
Every element in a symbol has a `symbolElPropId` — a unique ID used to override
element properties when the symbol is instantiated on a page. This is auto-generated
by the bridge tools.
