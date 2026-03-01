# Droip Symbol JSON Schema

## Top-Level Structure

A symbol is stored as the `droip` post meta on a `droip_symbol` post. The data structure:

```
{
  "name":        string,      // Display name (e.g., "Navbar", "Hero Section")
  "category":    string,      // Grouping category (e.g., "Sections", "Buttons", "other")
  "root":        string,      // ID of the root element in the data map
  "setAs":       string,      // "" | "header" | "footer" — site-wide role
  "customFonts": object,      // Font definitions used by this symbol
  "data":        object,      // Flat element map: { [elementId]: ElementNode }
  "styleBlocks": object       // Style definitions: { [styleId]: StyleBlock }
}
```

## Element Node Structure

Every element in the `data` map has this shape:

```
{
  "id":          string,       // Unique element ID (matches the key in data map)
  "name":        string,       // Element type: "div", "section", "heading", "paragraph",
                               //   "button", "link-block", "link-text", "image", "video",
                               //   "svg", "svg-icon", "form", "input", "textarea", "select",
                               //   "custom-code", "symbol", "collection", "items", "item",
                               //   "pagination", "pagination-item", "pagination-number", "empty"
  "title":       string,       // Human-readable label shown in editor layers panel
  "parentId":    string|null,  // Parent element ID, null for root
  "children":    string[],     // CONTAINER ELEMENTS ONLY. Ordered list of child element IDs.
                               //   CRITICAL: Do NOT include this key on text/leaf elements
                               //   (heading, paragraph, button, link-text, image, etc.).
                               //   Its presence causes the renderer to skip contents[] text.
  "properties":  object,       // Element-specific properties (see below)
  "styleIds":    string[],     // References to style blocks applied to this element
  "className":   string,       // Additional CSS class names (usually "")
  "source":      "droip",      // Always "droip"
  "visibility":  boolean,      // REQUIRED. Controls front-end rendering. Must always be
                               //   explicitly set to true — omitting it may cause the
                               //   element to not render.
  "collapse":    boolean,      // Editor layer panel collapse state (always false)
  "stylePanels": object,       // Editor panel visibility flags (use defaults)
  "hide":        boolean,      // Optional. Hides element in editor layer panel only —
                               //   element may still render on front end. Distinct from
                               //   visibility. Used for utility elements.
  "template_mounted": boolean  // Optional. Set to true on collection-related elements
                               //   and link-blocks with dynamic content.
}
```

## Properties Object

The `properties` object varies by element type. Common fields:

### All elements:
- `tag` (string): HTML tag to render ("div", "h1", "p", "a", "button", "img", etc.)
- `symbolElPropId` (string): Unique property override ID for symbol instances

### Text elements (heading, paragraph, button):
- `contents` (string[]): Array of text content strings

### Link elements (link-block, link-text, button with href):
- `type` (string): "href" for URL links
- `isActive` (boolean): Whether link is active
- `preload` (string): "default" | "eager" | "none"
- `attributes.href` (string): Target URL
- `attributes.target` (string): "" or "_blank"

### Image elements:
- `attributes.src` (string): Image URL
- `attributes.alt` (string): Alt text
- `wp_attachment_id` (int): Optional WordPress media ID

### SVG elements:
- `svgOuterHtml` (string): Full SVG markup

### Symbol instances:
- `symbolId` (int): WordPress post ID of the referenced symbol

### Root element (set by save_to_db):
- `symbolId` (int): Auto-set to the symbol's post ID on save

### Custom-code elements:
- `content` (string): Raw HTML/CSS/JS code as a singular string (NOT `contents` array)
- `data-type` (string): Always `"code"` for custom-code elements

### Dynamic content (heading, paragraph, image, link-block):
- `dynamicContent` (object): Connects element to WordPress data. See element-types.md
  Dynamic Content section for full details on available types and values.
  - `type` (string): `"post"` or `"author"`
  - `value` (string): Field name (e.g., `"post_title"`, `"featured_image"`)

### Custom attributes:
- `customAttributes` (object): Key-value pairs added as HTML attributes

## Style Block Structure

```
{
  "id":             string,    // Unique style block ID (e.g., "mcpbr_dp3vqhil")
  "type":           "class",   // Always "class"
  "name":           string,    // CSS class name (e.g., "mcpbr_dplsdzbj")
  "variant":        object,    // CSS rules per viewport (see below)
  "isGlobal":       boolean,   // true for symbol styles
  "isSymbolStyle":  boolean    // true — marks as symbol-owned style
}
```

### Variant Keys (Responsive Breakpoints)

- `"md"` — Desktop (≥1200px) — **required**, base styles
- `"tablet"` — Tablet (≤991px)
- `"mobileLandscape"` — Mobile landscape (≤767px)
- `"mobile"` — Mobile portrait (≤575px)
- `"md_hover"` — Desktop hover state
- `"tablet_hover"` — Tablet hover state

Each variant value is a **CSS declaration string** (no selectors, just properties):
```
"display:flex;align-items:center;gap:12px;padding:16px 24px;"
```

## Custom Fonts Structure

```
{
  "FontName": {
    "fontUrl":  "https://fonts.googleapis.com/css2?family=...",
    "family":   "FontName",
    "variants": ["regular", "500", "600", "700", ...],
    "subsets":  ["latin", "latin-ext"]
  }
}
```

## Validation Rules

1. `root` must reference an existing key in `data`
2. Root element must have `parentId: null`
3. All `children` entries must exist in `data`
4. All `parentId` references must exist in `data`
5. All `styleIds` should reference existing entries in `styleBlocks`
6. Each element's `id` field must match its key in the data map
7. `name` and `root` are required

## Page Data Structure (vs Symbols)

Page data is stored differently from symbol data. A page's `droip` post meta uses this wrapper:

```
{
  "blocks": { [elementId]: ElementNode, ... },
  "rootId": "root"
}
```

### CRITICAL: `root` and `body` Virtual Elements

The Droip editor's Redux store expects **every page** to have two virtual wrapper elements at the top of the element hierarchy:

```
root (name="root", id="root")
  └── body (name="body", id="body", parentId="root")
        └── your content elements...
```

**If `root` or `body` are missing, the editor crashes** with:
```
TypeError: Cannot read properties of undefined (reading 'children')
    at loadBlockStyleData
```

This happens because the editor accesses `data.root.children[0]` on load. Without the `root` element in the blocks map, this is `undefined.children` → crash.

### Required Virtual Elements

```json
{
  "blocks": {
    "root": {
      "id": "root",
      "name": "root",
      "children": ["body"],
      "accept": "*",
      "title": "Body",
      "styleIds": [],
      "stylePanels": { "typography": true, "composition": true, "size": true, "background": true, "stroke": true, "shadow": true, "effects": true, "position": true, "transform": true, "interaction": true, "animation": true }
    },
    "body": {
      "id": "body",
      "name": "body",
      "parentId": "root",
      "children": ["<your-content-root-id>"],
      "title": "Body",
      "visibility": true,
      "collapse": false,
      "properties": { "tag": "div" },
      "styleIds": [],
      "className": "droip-body",
      "stylePanels": { "typography": true, "composition": true, "size": true, "background": true, "stroke": true, "shadow": true, "effects": true, "position": true, "transform": true, "interaction": true, "animation": true }
    }
  }
}
```

### Key Differences: Page vs Symbol

| Aspect | Symbol | Page |
|--------|--------|------|
| Post meta key | `droip` | `droip` |
| Wrapper | `{ name, root, data, styleBlocks }` | `{ blocks, rootId }` |
| Element map key | `data` | `blocks` |
| Root element | Any `dp*` element with `parentId: null` | Must be virtual `root` → `body` hierarchy |
| Style storage | `styleBlocks` inside symbol data | Separate `droip_global_style_block_random` post meta |

### The `droip_add_symbol_to_page` Tool

This tool automatically handles the root/body scaffold:
- Creates `root` + `body` if the page has no existing Droip data
- Injects missing `root`/`body` if page data exists but lacks them
- Re-parents orphaned top-level elements under `body`

You do **not** need to manually create root/body when using this tool.
