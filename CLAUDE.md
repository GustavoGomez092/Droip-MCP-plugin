# Droip Claude Bridge — CLAUDE.md

## Project Overview

WordPress MCP server plugin that connects Claude Code to the Droip page builder. Lets Claude create, read, update, and delete Droip symbols (reusable components) programmatically via the Model Context Protocol over stdio.

## Architecture

```
Claude Code ◄──── stdio JSON-RPC ────► mcp-server/server.php (PHP CLI)
                                        │ loads wp-load.php
                                        ▼
                                  WordPress + Droip
                                  (droip_symbol posts + post meta)
```

The admin UI lives inside Droip's Integrations tab (`/admin.php?page=droip&tab=integrations`), injected via JavaScript because Droip's dashboard is a React SPA with no filter system for external integrations.

## File Structure

```
droip-claude-bridge/
├── droip-claude-bridge.php          # WP plugin entry: activation guard, constants, loads admin
├── composer.json                    # logiscape/mcp-sdk-php ^1.2, PSR-4 DroipBridge\
├── admin/
│   ├── admin-page.php               # AJAX endpoints, script enqueue, config generation
│   └── integration.js               # Injects card into Droip integrations tab
├── mcp-server/
│   ├── server.php                   # MCP entry: registers 16 tools + 4 resources, runs ServerRunner
│   ├── bootstrap.php                # WordPress bootstrap (WP_USE_THEMES=false, output buffered)
│   ├── Tools/
│   │   ├── KnowledgeTools.php       # 4 tools: element schema, symbol guide, style guide, examples
│   │   ├── SymbolCrudTools.php      # 5 tools: create, list, get, update, delete symbols
│   │   ├── PageDataTools.php        # 4 tools: list pages, get page data, global styles, variables
│   │   └── BuilderTools.php         # 3 tools: validate, generate IDs, add symbol to page
│   ├── Builders/
│   │   ├── IdGenerator.php          # dp-prefixed element IDs, mcpbr_dp style IDs, sep prop IDs
│   │   ├── ElementFactory.php       # Static factory for all Droip element types
│   │   ├── StyleBuilder.php         # CSS style blocks with responsive variants
│   │   └── SymbolBuilder.php        # Fluent builder: addElement → addChild → addStyleBlock → save()
│   ├── Validators/
│   │   └── SymbolValidator.php      # Validates symbol JSON: structure, refs, orphans, style IDs
│   ├── Resources/
│   │   └── DocsProvider.php         # Serves Docs/*.md as MCP resources
│   └── Docs/
│       ├── quick-start.md
│       ├── symbol-schema.md
│       ├── element-types.md
│       └── style-system.md
└── .mcp.json.example
```

## Key Constants

```php
DROIP_BRIDGE_VERSION    = '1.0.0'
DROIP_BRIDGE_PATH       = plugin_dir_path(__FILE__)
DROIP_BRIDGE_URL        = plugin_dir_url(__FILE__)
DROIP_BRIDGE_OPTION_KEY = 'droip_claude_bridge_settings'  // WP option: {enabled: bool}
```

## MCP Tools (17 total)

| Tool | Handler Class | Purpose |
|------|--------------|---------|
| `droip_get_element_schema` | KnowledgeTools | Element type docs |
| `droip_get_symbol_guide` | KnowledgeTools | Symbol structure guide |
| `droip_get_style_guide` | KnowledgeTools | Style/CSS docs |
| `droip_get_animation_guide` | KnowledgeTools | Animations, transitions & interactions docs |
| `droip_get_example_symbols` | KnowledgeTools | Real symbols from site |
| `droip_create_symbol` | SymbolCrudTools | Create symbol |
| `droip_list_symbols` | SymbolCrudTools | List all symbols |
| `droip_get_symbol` | SymbolCrudTools | Get symbol by ID |
| `droip_update_symbol` | SymbolCrudTools | Update symbol fields |
| `droip_delete_symbol` | SymbolCrudTools | Delete symbol |
| `droip_list_pages` | PageDataTools | List pages |
| `droip_get_page_data` | PageDataTools | Get page elements + styles |
| `droip_get_global_styles` | PageDataTools | Global style blocks |
| `droip_get_variables` | PageDataTools | Design tokens |
| `droip_validate_symbol` | BuilderTools | Pre-save validation |
| `droip_generate_ids` | BuilderTools | Generate unique IDs |
| `droip_add_symbol_to_page` | BuilderTools | Add symbol instance to page |

## MCP Resources (5)

| URI | File |
|-----|------|
| `droip://docs/quick-start` | quick-start.md |
| `droip://docs/symbol-schema` | symbol-schema.md |
| `droip://docs/element-types` | element-types.md |
| `droip://docs/style-system` | style-system.md |
| `droip://docs/animations-interactions` | animations-interactions.md |

## Development Environment

- **PHP**: Local by Flywheel PHP 8.4 at `~/Library/Application Support/Local/lightning-services/php-8.4.10+0/bin/darwin-arm64/bin/php`
- **MySQL socket**: `~/Library/Application Support/Local/run/l41e64xx4/mysql/mysqld.sock`
- **WP root**: `/Users/gustavogomez/Local Sites/droip-test/app/public`
- **System PHP is broken** (icu4c dylib issue) — always use Local's PHP
- **MCP SDK**: logiscape/mcp-sdk-php v1.2.9

## Testing the MCP Server

```bash
# Quick smoke test (tools/list)
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}
{"jsonrpc":"2.0","method":"notifications/initialized"}
{"jsonrpc":"2.0","id":2,"method":"tools/list"}' | \
WP_ROOT_PATH="/Users/gustavogomez/Local Sites/droip-test/app/public" \
"$HOME/Library/Application Support/Local/lightning-services/php-8.4.10+0/bin/darwin-arm64/bin/php" \
-d "mysqli.default_socket=$HOME/Library/Application Support/Local/run/l41e64xx4/mysql/mysqld.sock" \
mcp-server/server.php 2>/dev/null
```

## How the Admin Integration Works

The Droip integrations tab is a React SPA (minified in `droip-admin.min.js`). There are **no filters or hooks** for external plugins to add integrations. Instead:

1. `admin_enqueue_scripts` on `toplevel_page_droip` loads `integration.js` with localized data
2. JS uses `MutationObserver` on `#droip-app` to detect when integrations tab renders
3. Finds `.droip-api-list` elements (native integration cards) and appends our card after the last one
4. Card matches Droip's exact DOM structure: `droip-dashboard-label-wrapper`, `droip-api-logo`, `droip-api-details`, etc.
5. "Configure" button opens a slide-in drawer (480px, right side) with toggle + status + MCP config JSON
6. AJAX endpoints `droip_bridge_save_settings` / `droip_bridge_get_settings` handle persistence

---

## Droip Plugin Reference

### Post Types
- `droip_symbol` — Reusable components (symbols). Draft status, data in `droip` post meta
- `droip_global_data` — Single post holding global data as separate meta entries
- `droip_post` — Droip-managed pages (also regular `page` posts with `droip_editor_mode` meta)

### Key Meta Keys
- `droip` — Main data blob (blocks/elements for pages, symbolData for symbols)
- `droip_editor_mode` — `'droip'` if using Droip editor
- `droip_global_style_block` — Global styles (on global_data post)
- `droip_global_style_block_random` — Per-post styles
- `droip_user_saved_data` — Design variables/tokens
- `droip_user_custom_fonts` — Custom fonts
- `droip_user_controller` — Viewport settings
- `droip_wp_admin_common_data` — WP option: API keys, license, toggles

### Key Droip Classes
- `Droip\Ajax\Symbol` — `save_to_db()`, `fetch_list()`, `get_single_symbol()`, `update()`, `delete()`
- `Droip\Ajax\Page` — `save_page_data()`, `get_page_blocks_and_styles()`
- `Droip\HelperFunctions` — `get_global_data_using_key()`, `update_global_data_using_key()`, `save_droip_data_to_db()`, `get_page_styleblocks()`
- `Droip\Admin\AdminMenu` — Menu slug `droip`, capability `edit_posts`

### Element Node Structure
```json
{
  "id": "dp7azz8i",
  "name": "section",
  "parentId": null,
  "children": ["dpabc123"],
  "properties": { "tag": "section", "symbolElPropId": "sepj9r513" },
  "styleIds": ["mcpbr_dp3vqhil"],
  "className": "",
  "source": "droip",
  "visibility": true,
  "collapse": false,
  "stylePanels": { "typography": true, "composition": true, ... }
}
```

### Style Block Structure
```json
{
  "id": "mcpbr_dp3vqhil",
  "type": "class",
  "name": "mcpbr_dplsdzbj",
  "variant": {
    "md": "display:flex;flex-direction:column;padding:80px 24px;",
    "mobile": "padding:40px 16px;"
  },
  "isGlobal": true,
  "isSymbolStyle": true
}
```

### ID Patterns
- **Element IDs**: `dp` + 6 alphanumeric (e.g., `dp7azz8i`)
- **Style IDs**: `mcpbr_dp` + 6 alphanumeric (e.g., `mcpbr_dp3vqhil`)
- **Symbol element prop IDs**: `sep` + 7 alphanumeric (e.g., `sepj9r513`)

### Symbol Data Structure
```json
{
  "symbolData": {
    "name": "Hero Section",
    "category": "other",
    "root": "dp7azz8i",
    "setAs": "",
    "customFonts": {},
    "data": { "dp7azz8i": { /* element */ }, "dpabc123": { /* element */ } },
    "styleBlocks": { "mcpbr_dp3vqhil": { /* style block */ } }
  }
}
```

### Viewport Variants (Style Blocks)
- `md` — Desktop (>1200px)
- `tablet` — Tablet (768px)
- `mobileLandscape` — Mobile landscape (480px)
- `mobile` — Mobile (360px)
- `md_hover` — Desktop hover state

### AJAX Routing
All Droip AJAX goes through `droip_post_apis` / `droip_get_apis` actions with `endpoint` parameter. Key endpoints:
- POST `save-user-saved-symbol-data` — Create symbol
- POST `update-user-saved-symbol-data` — Update symbol
- POST `delete-user-saved-symbol-data` — Delete symbol
- GET `get-symbol-list` — List symbols
- GET `get-single-symbol` — Get symbol
- POST `save-page-data` — Save page
- GET `get-page-data` — Get page data

### Droip Apps System
- Apps stored in `/wp-content/droip-apps/`
- Settings: `droip_app_settings_{slug}` (global data)
- Filter: `droip_apps_configuration_{slug}` — modify app settings
- No filter for adding integrations to the dashboard (React SPA hardcodes them)
