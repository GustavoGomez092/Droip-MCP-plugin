# Droip Claude Bridge

A WordPress plugin that connects [Claude Code](https://claude.ai/claude-code) to the [Droip](https://droip.com) page builder via the [Model Context Protocol (MCP)](https://modelcontextprotocol.io). Describe components in natural language and have Claude create them directly in Droip's symbol system.

## Requirements

- WordPress 6.0+
- [Droip](https://droip.com) plugin (v2.5.7+) installed and active
- PHP 8.1+
- [Composer](https://getcomposer.org)
- [Claude Code](https://claude.ai/claude-code) CLI

## Installation

### 1. Install the Plugin

Clone into your WordPress plugins folder:

```bash
cd wp-content/plugins/
git clone git@github.com:GustavoGomez092/Droip-MCP-plugin.git droip-claude-bridge
cd droip-claude-bridge
composer install
```

### 2. Activate

Go to **WordPress Admin > Plugins** and activate "Droip Claude Bridge". Activation is blocked if Droip is not active.

### 3. Enable the MCP Server

Go to **Droip > Integrations** tab. Click **Configure** on the "Claude Code (MCP)" card, then toggle **Enable MCP Server** on.

### 4. Configure Claude Code

Copy the auto-generated MCP config JSON from the Configure drawer. Add it to your Claude Code config (`.claude/settings.json` or `~/.claude.json`):

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

All paths are auto-detected. For [Local by Flywheel](https://localwp.com/) environments, the PHP binary and MySQL socket are found automatically.

### 5. Restart Claude Code

Restart Claude Code to pick up the new MCP server. Verify by asking Claude to list your Droip symbols.

## Architecture

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

The MCP server (`mcp-server/server.php`) is a standalone PHP process that bootstraps WordPress (themes disabled, output buffered), gains access to Droip classes, and communicates with Claude Code over stdin/stdout using JSON-RPC.

## MCP Tools (17)

### Knowledge Tools (5)
| Tool | Description |
|------|-------------|
| `droip_get_element_schema` | All element types and their properties |
| `droip_get_symbol_guide` | Symbol JSON schema reference |
| `droip_get_style_guide` | CSS style system docs |
| `droip_get_animation_guide` | Animations, transitions & interactions |
| `droip_get_example_symbols` | Real symbols from your site |

### Symbol CRUD Tools (5)
| Tool | Description |
|------|-------------|
| `droip_create_symbol` | Create a new symbol with validation and pre-save cleanup |
| `droip_list_symbols` | List all symbols |
| `droip_get_symbol` | Get full symbol data |
| `droip_update_symbol` | Update symbol fields |
| `droip_delete_symbol` | Delete a symbol |

### Page Data Tools (4)
| Tool | Description |
|------|-------------|
| `droip_list_pages` | List pages with Droip editor status |
| `droip_get_page_data` | Get page element tree and styles |
| `droip_get_global_styles` | Global CSS style blocks |
| `droip_get_variables` | Design tokens (colors, fonts, spacing) |

### Builder Tools (3)
| Tool | Description |
|------|-------------|
| `droip_validate_symbol` | Validate symbol JSON before saving |
| `droip_generate_ids` | Generate Droip-compatible element/style IDs |
| `droip_add_symbol_to_page` | Add a symbol instance to a page |

## MCP Resources (5)

| URI | Content |
|-----|---------|
| `droip://docs/quick-start` | Quick start guide |
| `droip://docs/symbol-schema` | JSON schema reference |
| `droip://docs/element-types` | Element types with properties |
| `droip://docs/style-system` | Style block and CSS docs |
| `droip://docs/animations-interactions` | Animations & interactions |

## Pre-Save Data Cleanup

The bridge automatically fixes common structural issues before saving symbols:

- **Empty `children` on leaf elements** — Droip's renderer uses the presence of the `children` key to decide whether to render text (`contents[]`) or iterate children. An empty `children: []` on a heading or paragraph causes it to render blank. The cleanup strips these.
- **Buttons without text children** — Droip's button renderer ignores `contents` and only renders child elements. The cleanup auto-creates a `text` child element (`<span>`) if a button has none.
- **Root `parentId`** — Ensures root elements have `parentId: null`.

## Usage Examples

```
"List all my Droip symbols"
"Create a hero section with a heading, paragraph, and CTA button"
"Update the hero section to add a background image"
"Add the header symbol to page ID 15"
```

## File Structure

```
droip-claude-bridge/
├── droip-claude-bridge.php        # Plugin entry, activation guard, constants
├── admin/
│   ├── admin-page.php             # AJAX endpoints, script enqueue, config generation
│   └── integration.js             # Injects card into Droip integrations tab
├── mcp-server/
│   ├── server.php                 # MCP entry: registers tools + resources
│   ├── bootstrap.php              # WordPress bootstrap (themes off, output buffered)
│   ├── Tools/
│   │   ├── KnowledgeTools.php     # 5 tools: schema, guide, style, animations, examples
│   │   ├── SymbolCrudTools.php    # 5 tools: CRUD + pre-save cleanup
│   │   ├── PageDataTools.php      # 4 tools: pages, page data, global styles, variables
│   │   └── BuilderTools.php       # 3 tools: validate, generate IDs, add to page
│   ├── Builders/
│   │   ├── IdGenerator.php        # dp + 6 chars, mcpbr_dp + 6, sep + 7
│   │   ├── ElementFactory.php     # Static factory for all element types
│   │   ├── StyleBuilder.php       # Responsive CSS style blocks
│   │   └── SymbolBuilder.php      # Fluent API: build() and save()
│   ├── Validators/
│   │   └── SymbolValidator.php    # Structure, refs, orphans, style ID checks
│   ├── Resources/
│   │   └── DocsProvider.php       # Serves Docs/*.md as MCP resources
│   └── Docs/                      # Markdown docs served as MCP resources
├── composer.json                  # logiscape/mcp-sdk-php ^1.2
├── .mcp.json.example              # Example Claude Code MCP config
└── CLAUDE.md                      # Claude Code project instructions
```

## Troubleshooting

**Server won't start** — Check the Status section in the Configure drawer. Ensure `composer install` was run. Verify the PHP binary path. Check `mcp-server-errors.log`.

**Can't connect from Claude Code** — Ensure the MCP server is enabled in the Integrations tab. Verify config JSON is in your Claude Code settings. Restart Claude Code after changes.

**Symbols don't appear** — Symbols are created as draft posts and should appear in the symbol panel. Use `droip_validate_symbol` to check structure.

**MySQL connection errors** — For Local by Flywheel, ensure the site is running. The plugin auto-detects sockets at `~/Library/Application Support/Local/run/*/mysql/mysqld.sock`. Set manually via `-d mysqli.default_socket=...` if needed.

## License

GPL-2.0-or-later
