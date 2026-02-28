# Droip Claude Bridge

A WordPress plugin that connects [Claude Code](https://claude.ai/claude-code) to the [Droip](https://droip.com) page builder via the [Model Context Protocol (MCP)](https://modelcontextprotocol.io). This lets you describe components in natural language and have Claude create them directly in Droip's symbol system.

## What It Does

- Exposes Droip's symbol (reusable component) system to Claude Code as MCP tools
- Provides built-in documentation resources so Claude understands Droip's data structures
- Adds a native-looking integration card to Droip's Integrations settings tab
- Auto-detects your environment (PHP binary, MySQL socket, paths) for zero-config setup

## Requirements

- WordPress 6.0+
- [Droip](https://droip.com) plugin (v3.0.0+) installed and active
- PHP 8.1+ (the MCP server runs as a CLI process)
- [Composer](https://getcomposer.org) (for installing the MCP SDK dependency)
- [Claude Code](https://claude.ai/claude-code) CLI

## Installation

### 1. Install the Plugin

Copy or clone the `droip-claude-bridge` directory into your WordPress plugins folder:

```
wp-content/plugins/droip-claude-bridge/
```

### 2. Install Composer Dependencies

```bash
cd wp-content/plugins/droip-claude-bridge
composer install
```

### 3. Activate the Plugin

Go to **WordPress Admin > Plugins** and activate "Droip Claude Bridge". The plugin will block activation if Droip is not already active.

### 4. Enable the MCP Server

Go to **WordPress Admin > Droip > Integrations** tab. You'll see a "Claude Code (MCP)" card. Click **Configure**, then toggle **Enable MCP Server** on.

### 5. Configure Claude Code

In the same Configure drawer, copy the auto-generated MCP config JSON. Add it to your Claude Code configuration:

- **Per-project**: `.claude/settings.json` in your project root
- **Global**: `~/.claude.json`

The config looks like:

```json
{
  "mcpServers": {
    "droip-bridge": {
      "command": "/path/to/php",
      "args": [
        "-d", "mysqli.default_socket=/path/to/mysqld.sock",
        "/path/to/droip-claude-bridge/mcp-server/server.php"
      ],
      "env": {
        "WP_ROOT_PATH": "/path/to/wordpress"
      }
    }
  }
}
```

The plugin auto-detects all paths. For [Local by Flywheel](https://localwp.com/) environments, it finds the PHP binary and MySQL socket automatically.

### 6. Restart Claude Code

Restart Claude Code to pick up the new MCP server. You can verify it's connected by asking Claude to list your Droip symbols.

## How It Works

### Architecture

```
┌──────────────┐     stdio (JSON-RPC)     ┌──────────────────────┐
│  Claude Code  │ ◄─────────────────────► │  MCP Server (PHP CLI) │
└──────────────┘                           │  boots WordPress      │
                                           │  uses Droip classes   │
                                           └──────────┬───────────┘
                                                      │ direct PHP calls
                                           ┌──────────▼───────────┐
                                           │   WordPress + Droip   │
                                           │   droip_symbol posts  │
                                           │   post meta storage   │
                                           └──────────────────────┘
```

The MCP server (`mcp-server/server.php`) is a standalone PHP process that:

1. Bootstraps WordPress by loading `wp-load.php` (with themes disabled, output buffered)
2. Gains access to all Droip classes and functions
3. Registers 16 tools and 4 documentation resources
4. Communicates with Claude Code over stdin/stdout using the MCP protocol (JSON-RPC)

### MCP Tools (17)

#### Knowledge Tools
| Tool | Description |
|------|-------------|
| `droip_get_element_schema` | Documentation of all element types and their properties |
| `droip_get_symbol_guide` | Step-by-step guide + complete JSON schema reference |
| `droip_get_style_guide` | How Droip's CSS style system works |
| `droip_get_animation_guide` | CSS transitions, transforms, and the JS interaction system |
| `droip_get_example_symbols` | Real example symbols from your site |

#### Symbol CRUD Tools
| Tool | Description |
|------|-------------|
| `droip_create_symbol` | Create a new reusable symbol |
| `droip_list_symbols` | List all existing symbols |
| `droip_get_symbol` | Get full data for a specific symbol |
| `droip_update_symbol` | Update symbol name, data, styles, or category |
| `droip_delete_symbol` | Permanently delete a symbol |

#### Page Data Tools
| Tool | Description |
|------|-------------|
| `droip_list_pages` | List all pages with their Droip editor status |
| `droip_get_page_data` | Get a page's element tree and style blocks |
| `droip_get_global_styles` | Get all global CSS style blocks |
| `droip_get_variables` | Get design system variables (colors, fonts, spacing) |

#### Builder Tools
| Tool | Description |
|------|-------------|
| `droip_validate_symbol` | Validate a symbol's JSON structure before saving |
| `droip_generate_ids` | Generate unique Droip-compatible element or style IDs |
| `droip_add_symbol_to_page` | Add a symbol instance to a page's element tree |

### MCP Resources (5)

Documentation served as markdown that Claude can read on demand:

| URI | Content |
|-----|---------|
| `droip://docs/quick-start` | Quick start guide for creating symbols |
| `droip://docs/symbol-schema` | Complete JSON schema reference |
| `droip://docs/element-types` | All element types with properties |
| `droip://docs/style-system` | Style block and CSS system docs |
| `droip://docs/animations-interactions` | Animations, transitions & interactions |

### Admin Integration

The plugin integrates natively into Droip's admin dashboard at **Settings > Integrations**. Since Droip's dashboard is a React SPA with no external plugin hooks, the integration card is injected via JavaScript:

1. A script is enqueued only on Droip's admin pages
2. A `MutationObserver` detects when the Integrations tab renders
3. A "Claude Code (MCP)" card is appended after the last native integration
4. The card matches Droip's exact DOM structure and CSS classes
5. Clicking "Configure" opens a slide-in drawer with:
   - Enable/disable toggle
   - Status checklist (PHP binary, Composer deps, MCP server, Droip version)
   - Auto-generated MCP config JSON with copy-to-clipboard

## File Structure

```
droip-claude-bridge/
├── droip-claude-bridge.php        # Plugin entry point
│   ├── Activation guard (requires Droip)
│   ├── Constants (DROIP_BRIDGE_VERSION, PATH, URL, OPTION_KEY)
│   └── Loads admin/admin-page.php in admin context
│
├── admin/
│   ├── admin-page.php             # AJAX endpoints + script enqueue
│   │   ├── wp_ajax_droip_bridge_save_settings
│   │   ├── wp_ajax_droip_bridge_get_settings
│   │   ├── admin_enqueue_scripts (conditional on Droip page)
│   │   └── Utility functions (detect PHP, MySQL socket, generate config)
│   └── integration.js             # Injects card into Droip integrations tab
│       ├── MutationObserver on #droip-app
│       ├── Card builder (matches Droip DOM structure)
│       ├── Configure drawer (toggle + status + config JSON)
│       └── AJAX save/load via droipBridge localized data
│
├── mcp-server/
│   ├── server.php                 # MCP entry point
│   │   ├── Composer autoload
│   │   ├── WordPress bootstrap
│   │   ├── Enable check (reads WP option)
│   │   ├── Tool registration (16 tools)
│   │   ├── Resource registration (4 resources)
│   │   └── ServerRunner on stdio
│   │
│   ├── bootstrap.php              # WordPress loader
│   │   ├── WP_USE_THEMES = false
│   │   ├── Output buffered (clean stdio)
│   │   ├── Path detection (WP_ROOT_PATH env or relative)
│   │   └── Droip verification
│   │
│   ├── Tools/
│   │   ├── KnowledgeTools.php     # 4 tools: schema, guide, style, examples
│   │   ├── SymbolCrudTools.php    # 5 tools: create, list, get, update, delete
│   │   ├── PageDataTools.php      # 4 tools: pages, page data, global styles, variables
│   │   └── BuilderTools.php       # 3 tools: validate, generate IDs, add to page
│   │
│   ├── Builders/
│   │   ├── IdGenerator.php        # dp + 6 chars, mcpbr_dp + 6, sep + 7
│   │   ├── ElementFactory.php     # Static factory for all element types
│   │   ├── StyleBuilder.php       # Responsive CSS style blocks
│   │   └── SymbolBuilder.php      # Fluent API: build() and save()
│   │
│   ├── Validators/
│   │   └── SymbolValidator.php    # Structure, refs, orphans, style ID checks
│   │
│   ├── Resources/
│   │   └── DocsProvider.php       # Serves Docs/*.md as MCP resources
│   │
│   └── Docs/
│       ├── quick-start.md         # Getting started guide
│       ├── symbol-schema.md       # Complete JSON schema
│       ├── element-types.md       # All element types reference
│       ├── style-system.md        # CSS/style block documentation
│       └── animations-interactions.md  # Animations, transitions & interactions
│
├── composer.json                  # logiscape/mcp-sdk-php ^1.2
├── .mcp.json.example              # Example Claude Code config
├── CLAUDE.md                      # Claude Code project instructions
└── README.md                      # This file
```

## Usage Examples

Once configured, you can ask Claude Code things like:

- "List all my Droip symbols"
- "Create a hero section symbol with a heading, paragraph, and CTA button"
- "Show me the element structure of symbol ID 42"
- "Update the hero section symbol to add a background image"
- "Add the header symbol to page ID 15"
- "What element types does Droip support?"
- "Show me the style system documentation"

Claude will use the MCP tools to interact with your Droip installation directly, creating valid symbol JSON structures that appear in Droip's editor.

## Droip Data Model (For Developers)

### Symbols

Symbols are WordPress posts of type `droip_symbol` (always draft status). Their data is stored in the `droip` post meta key as JSON:

```json
{
  "name": "Component Name",
  "category": "other",
  "root": "dp7azz8i",
  "setAs": "",
  "customFonts": {},
  "data": {
    "dp7azz8i": { "id": "dp7azz8i", "name": "section", "parentId": null, "children": ["..."], "..." : "..." }
  },
  "styleBlocks": {
    "style_id": { "id": "...", "type": "class", "name": "...", "variant": { "md": "css..." } }
  }
}
```

### Elements

Elements are stored as a flat map (keyed by ID). Each element has:
- `id` — Unique ID (pattern: `dp` + 6 alphanumeric chars)
- `name` — Element type (`heading`, `paragraph`, `button`, `image`, `section`, etc.)
- `parentId` — Parent element ID (null for root)
- `children` — Array of child element IDs
- `properties` — Type-specific props (`tag`, `contents`, `attributes`, etc.)
- `styleIds` — Array of style block IDs applied to this element
- `className` — Additional CSS classes

### Style Blocks

CSS style blocks with responsive variants:
- `md` — Desktop (>1200px, required)
- `tablet` — Tablet (768px)
- `mobile` — Mobile (360px)
- `md_hover` — Desktop hover state

CSS is stored as semicolon-separated declarations: `"display:flex;gap:12px;padding:16px;"`

## Troubleshooting

### Server won't start
- Check the Status section in the Configure drawer
- Ensure `composer install` was run in the plugin directory
- Verify the PHP binary path is correct and executable
- Check `mcp-server-errors.log` in the plugin directory

### Can't connect from Claude Code
- Ensure the MCP server is enabled in the Droip Integrations tab
- Verify the config JSON is correctly added to your Claude Code settings
- Restart Claude Code after changing MCP config
- Test manually: pipe JSON-RPC messages to `server.php` (see CLAUDE.md)

### Symbols don't appear in Droip editor
- Symbols are created as draft posts — they should appear in the symbol panel
- Check that the symbol data passes validation (`droip_validate_symbol` tool)
- Verify element parent-child relationships are consistent

### MySQL connection errors
- For Local by Flywheel, ensure the MySQL socket path is correct in the config
- The plugin auto-detects sockets at `~/Library/Application Support/Local/run/*/mysql/mysqld.sock`
- You can manually set the socket via the `-d mysqli.default_socket=...` PHP arg

## License

GPL-2.0-or-later
