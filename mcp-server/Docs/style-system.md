# Droip Style System

## Overview

Droip uses a **style block** system instead of inline styles. Each style block
is a named CSS class with responsive variants. Elements reference style blocks
via their `styleIds` array.

## Style Block Structure

```json
{
  "id": "mcpbr_dp3vqhil",
  "type": "class",
  "name": "mcpbr_dplsdzbj",
  "variant": {
    "md": "display:flex;align-items:center;gap:12px;",
    "tablet": "flex-direction:column;",
    "mobile": "gap:8px;padding:16px;"
  },
  "isGlobal": true,
  "isSymbolStyle": true
}
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique identifier for the style block |
| `type` | string | Always `"class"` |
| `name` | string | CSS class name used in HTML output |
| `variant` | object | CSS declarations per responsive breakpoint |
| `isGlobal` | boolean | `true` for shared/symbol styles |
| `isSymbolStyle` | boolean | `true` when the style belongs to a symbol |

### ID and Name Conventions

- The `id` and `name` are separate values — `id` is the key in the styleBlocks map,
  `name` is the CSS class name applied in the DOM.
- For bridge-created styles, use `"mcpbr_dp"` prefix for both.
- Generate different random suffixes for id and name.

## Responsive Breakpoints (Variants)

| Key | Viewport | Description |
|-----|----------|-------------|
| `md` | ≥1200px | Desktop — **always required**, base styles |
| `tablet` | ≤991px | Tablet styles (overrides desktop) |
| `mobileLandscape` | ≤767px | Mobile landscape |
| `mobile` | ≤575px | Mobile portrait |

### State Variants

Append `_hover` to any breakpoint key for hover styles:
- `md_hover` — Desktop hover
- `tablet_hover` — Tablet hover

## CSS Format

Variant values are **CSS declaration strings** — semicolon-separated property:value pairs
with no selectors or braces:

```
"display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;padding:80px 40px;"
```

### Important Notes

- Always end with a semicolon
- No spaces around colons in property:value pairs
- Use standard CSS property names (kebab-case)
- Responsive variants only need to include properties that change from desktop

## CSS Variables

Droip supports CSS variables for design tokens. Reference them in style values:

```
"color:var(--planzo_dpwm1svt);background-color:var(--planzo_dpt2hy25);"
```

Use `droip_get_variables` to see available variables on the current site.

## Common CSS Patterns

### Flexbox Layout
```
"display:flex;flex-direction:row;align-items:center;justify-content:space-between;gap:16px;"
```

### Grid Layout
```
"display:grid;grid-template-columns:repeat(3, 1fr);gap:24px;"
```

### Typography
```
"font-family:\"Inter\";font-weight:600;font-size:48px;line-height:1.2em;letter-spacing:-0.5px;color:#1a1a1a;"
```

### Spacing
```
"padding:24px 32px;margin:0 auto;"
```

### Size Constraints
```
"max-width:1200px;width:100%;"
```

### Background
```
"background-color:#f8f9fa;border-radius:12px;"
```

### Responsive Override Example

Desktop (`md`):
```
"display:grid;grid-template-columns:repeat(3, 1fr);gap:32px;padding:80px 40px;"
```

Tablet (`tablet`):
```
"grid-template-columns:repeat(2, 1fr);gap:24px;padding:60px 24px;"
```

Mobile (`mobile`):
```
"grid-template-columns:1fr;gap:16px;padding:40px 16px;"
```

## How Elements Reference Styles

Each element has a `styleIds` array. The first style ID is the primary class:

```json
{
  "id": "dp7azz8i",
  "name": "div",
  "styleIds": ["mcpbr_dp3vqhil"],
  ...
}
```

An element can reference multiple style blocks. They are applied in order.

## Global vs Symbol Styles

- **Symbol styles** (`isSymbolStyle: true`): Belong to the symbol, stored in the
  symbol's `styleBlocks` map. Portable with the symbol.
- **Global styles** (`isGlobal: true`): Shared across the site, stored in the global
  style blocks option.

For bridge-created symbols, set both `isGlobal: true` and `isSymbolStyle: true`.
