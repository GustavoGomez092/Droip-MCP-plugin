# Droip Quick Start — Creating Your First Symbol

## Overview

A **symbol** in Droip is a reusable component (like a header, footer, card, or hero section).
Symbols are stored as WordPress posts of type `droip_symbol` with JSON data in the `droip` post meta.

## Core Concepts

1. **Elements** are stored in a **flat map** keyed by unique IDs (e.g., `"dp7azz8i"`)
2. Parent-child relationships are expressed through `parentId` and `children` arrays
3. **Style blocks** are stored separately and referenced by `styleIds` on each element
4. Every symbol has a **root element** that serves as the top-level container

## Step-by-Step: Create a Hero Section

### 1. Generate IDs

Every element and style block needs a unique ID:
- Element IDs: `"dp"` + 6 random alphanumeric chars (e.g., `"dp7azz8i"`)
- Style block IDs: `"mcpbr_dp"` + 6 random chars (e.g., `"mcpbr_dp3vqhil"`)

Use `droip_generate_ids` tool to create batches.

### 2. Build the Element Tree

```
root (section)
├── container (div)
│   ├── heading (h1) — "Build Something Amazing"
│   ├── paragraph (p) — "A description of what we do."
│   └── button — "Get Started"
```

### 3. Create Style Blocks

Each style block contains CSS for one or more viewports:
```json
{
  "id": "mcpbr_dp3vqhil",
  "type": "class",
  "name": "mcpbr_dplsdzbj",
  "variant": {
    "md": "display:flex;flex-direction:column;align-items:center;padding:80px 24px;",
    "mobile": "padding:40px 16px;"
  },
  "isGlobal": true,
  "isSymbolStyle": true
}
```

### 4. Assemble the Symbol Data

```json
{
  "name": "Hero Section",
  "category": "Sections",
  "root": "<root-id>",
  "setAs": "",
  "customFonts": {},
  "data": {
    "<root-id>": { ... },
    "<container-id>": { ... },
    "<heading-id>": { ... },
    "<paragraph-id>": { ... },
    "<button-id>": { ... }
  },
  "styleBlocks": {
    "<style-id-1>": { ... },
    "<style-id-2>": { ... }
  }
}
```

### 5. Save via `droip_create_symbol`

Pass the assembled data to the `droip_create_symbol` tool. It validates the structure
and saves it to the database. The symbol will immediately appear in Droip's editor.

## Using the Tools

### Recommended Workflow

1. Call `droip_get_symbol_guide` — learn the full structure
2. Call `droip_generate_ids` — get unique IDs for your elements and styles
3. Call `droip_get_example_symbols` — see real examples from the current site
4. Call `droip_create_symbol` — save your new symbol
5. Call `droip_validate_symbol` — check for issues (or use create which validates first)

### Tips

- Always create style blocks for layout (flexbox/grid) on container elements
- Use responsive variants (`tablet`, `mobile`) for a mobile-friendly design
- The root element's `parentId` must be `null`
- Every child ID in a `children` array must exist in the `data` map
- Set `"setAs": "header"` or `"setAs": "footer"` for site-wide header/footer symbols

## Dynamic Content

Symbols can display WordPress data dynamically using the `dynamicContent` property on headings, paragraphs, images, and link-blocks. This is how you build blog post cards, author bios, and other data-driven components. See `droip_get_element_schema` for full details on available dynamic content fields.

## Collections (Advanced)

The **collection** system creates dynamic content repeaters — blog post grids, product listings, team member sections, etc. A collection connects to a WordPress post type and repeats an item template for each result, with built-in pagination and empty state support.

Required hierarchy: `collection > [items > item, pagination, empty]`

Use `droip_get_element_schema` for the full collection element reference and `droip_get_example_symbols` to see real collection examples from the site.
