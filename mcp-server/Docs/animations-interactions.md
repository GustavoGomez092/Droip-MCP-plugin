# Droip Animations, Transitions & Interactions

## Overview

Droip provides three systems for adding motion and interactivity to elements:

1. **CSS Transitions** — Smooth property changes on hover/focus/active states via style block variants
2. **CSS Transforms** — Static or hover-triggered transform effects via style blocks
3. **JavaScript Interactions** — Keyframe-based animations triggered by events (scroll, click, load, etc.) via `properties.interactions`

---

## 1. CSS Transitions (Style Block Variants)

### How It Works

Transitions are defined using standard CSS transition properties in a style block's `md` variant, combined with state variants (`md_hover`, `md_focus`, `md_active`, `md_placeholder`) that define what changes on interaction.

### State Variant Keys

Append the state suffix to any breakpoint key:

| Variant Key | Description |
|-------------|-------------|
| `md_hover` | Desktop hover state |
| `md_focus` | Desktop focus state |
| `md_active` | Desktop active state |
| `md_placeholder` | Input placeholder styling |
| `tablet_hover` | Tablet hover state |
| `mobile_hover` | Mobile hover state |

### Transition CSS Properties

Add these to the **base variant** (`md`) to enable smooth transitions:

| Property | Values | Description |
|----------|--------|-------------|
| `transition-property` | `all`, `color`, `background-color`, `transform`, `border-color`, `opacity`, or comma-separated list | Which CSS properties to animate |
| `transition-duration` | `300ms`, `500ms`, `1s`, etc. | How long the transition takes |
| `transition-delay` | `0ms`, `100ms`, etc. | Delay before transition starts |
| `transition-timing-function` | `ease`, `ease-in`, `ease-out`, `ease-in-out`, `linear` | Acceleration curve |

### Complete Transition Example

A button that smoothly changes background color on hover:

```json
{
  "id": "mcpbr_dp4jzy42",
  "type": "class",
  "name": "mcpbr_dpbtnhvr",
  "variant": {
    "md": "display:flex;align-items:center;justify-content:center;padding:12px 24px;background-color:rgba(37, 51, 240, 1);color:#ffffff;border-radius:8px;cursor:pointer;transition-property:background-color;transition-duration:300ms;transition-delay:0ms;transition-timing-function:ease-out;",
    "md_hover": "background-color:rgba(0, 15, 225, 1);"
  },
  "isGlobal": true,
  "isSymbolStyle": true
}
```

### Transition Pattern: Transform on Hover

An element that lifts and scales on hover with a smooth transition:

```json
{
  "id": "mcpbr_dp6miore",
  "type": "class",
  "name": "mcpbr_dplicnhv",
  "variant": {
    "md": "transition-property:transform;transition-duration:300ms;transition-delay:0ms;transition-timing-function:ease-out;",
    "md_hover": "transform:translate(0px, -5px) scale(1.1, 1.1);"
  },
  "isGlobal": true,
  "isSymbolStyle": true
}
```

### Transition Pattern: Input Focus State

An input field with border highlight on focus and placeholder color:

```json
{
  "id": "mcpbr_dp5ffu0a",
  "type": "class",
  "name": "mcpbr_dpinpfld",
  "variant": {
    "md": "width:100%;padding:10px 16px;border:1px solid #e0e0e0;border-radius:8px;font-size:16px;transition-property:all;transition-duration:300ms;transition-delay:0ms;transition-timing-function:ease-out;",
    "md_hover": "border-color:rgba(37, 51, 240, 0.5);",
    "md_focus": "outline-style:none;border-color:rgba(37, 51, 240, 1);box-shadow:0px 0px 0px 3px rgba(37, 51, 240, 0.2);",
    "md_placeholder": "color:rgba(108, 117, 125, 0.60);"
  },
  "isGlobal": true,
  "isSymbolStyle": true
}
```

### Transition Pattern: Color Change

A text link that changes color on hover:

```json
{
  "id": "mcpbr_dpwk9544",
  "type": "class",
  "name": "mcpbr_dplnkhvr",
  "variant": {
    "md": "color:#333333;transition-property:color;transition-duration:300ms;transition-delay:0ms;transition-timing-function:ease;",
    "md_hover": "color:var(--planzo_dpil76ht);"
  },
  "isGlobal": true,
  "isSymbolStyle": true
}
```

### Common Transition Durations

| Duration | Use Case |
|----------|----------|
| `150ms` | Micro-interactions (color shifts, opacity) |
| `300ms` | Standard transitions (most common in Droip) |
| `500ms` | Emphasized transitions (transforms, layout shifts) |

### Common Easing Functions

| Function | Effect |
|----------|--------|
| `ease` | Slow start, fast middle, slow end (default) |
| `ease-out` | Fast start, slow end (most natural for hover) |
| `ease-in` | Slow start, fast end (good for exit animations) |
| `ease-in-out` | Slow start and end |
| `linear` | Constant speed |

---

## 2. CSS Transforms (Static & Hover)

### Transform in Base Styles

Apply static transforms in the `md` variant:

```
"transform:translate(0px, -6px);"
"transform:translate(-50%, -50%);"
"transform:none;"
```

### Transform on Hover

Combine with transitions for smooth hover effects:

```json
{
  "variant": {
    "md": "transition-property:transform;transition-duration:300ms;transition-timing-function:ease-out;",
    "md_hover": "transform:translate(0px, -5px) scale(1.1, 1.1);"
  }
}
```

### Transform Origin

Control the transform pivot point:

```
"transform-origin:50% 0%;"
```

### Backdrop Filter

Droip supports backdrop blur effects:

```
"backdrop-filter:opacity(100%) blur(15px);"
"backdrop-filter:opacity(100%) blur(50px);"
"backdrop-filter:blur(6px);"
```

To disable on smaller screens:
```json
{
  "variant": {
    "md": "backdrop-filter:opacity(100%) blur(15px);",
    "tablet": "backdrop-filter:none;"
  }
}
```

---

## 3. JavaScript Interactions (Element Interactions)

### Overview

The interaction system enables complex, multi-step animations triggered by events like scrolling, hovering, clicking, or page load. These are stored in `properties.interactions` on each element.

**Important**: Interactions are the most complex part of Droip's animation system. For simple hover/focus effects, use CSS transitions (Section 1) instead.

### Interaction Data Structure

```json
{
  "properties": {
    "interactions": {
      "elementAsTrigger": {
        "<trigger-type>": {
          "preset": { ... },
          "textAnimation": { ... },
          "custom": {
            "0": {
              "data": {
                "<targetElementId>": {
                  "0": {
                    "active": true,
                    "duration": 700,
                    "start": { ... },
                    "end": { ... },
                    "easing": "ease-out",
                    "delay": 200,
                    "property": "move",
                    "setAsInitial": false
                  }
                },
                "<targetElementId>____info": {
                  "applyToClass": false,
                  "classApplyOnly": "childrens",
                  "styleBlockId": "<styleBlockId>"
                }
              },
              "deviceAndClassList": {
                "classList": {},
                "devices": { "0": "md" },
                "applyToClass": false
              },
              "name": "Fade In on Scroll",
              "active": true,
              "maxTime": 1000,
              "livePreviewOn": false,
              "scrollArea": "viewport",
              "repeat": 1
            }
          }
        }
      },
      "deviceAndClassList": null
    }
  }
}
```

### Trigger Types

| Trigger Type | Event | Description |
|--------------|-------|-------------|
| `loading-start` | Page load begins | Fires when the page starts loading |
| `loading-finishes` | Page load complete | Fires after the page finishes loading |
| `scroll-into-ele` | Scroll into view | Fires when element enters the viewport |
| `scroll-out-ele` | Scroll out of view | Fires when element leaves the viewport |
| `element-scrolling` | While scrolling | Fires continuously as element scrolls through viewport |
| `mouseenter` | Mouse hover in | Fires when cursor enters element |
| `mouseleave` | Mouse hover out | Fires when cursor leaves element |
| `click` | First click | Fires on element click |
| `dblclick` | Second click | Fires on double-click (or toggle back) |
| `tab-in-view` | Tab becomes visible | Fires when a tab panel becomes active |
| `tab-out-of-view` | Tab becomes hidden | Fires when a tab panel is deactivated |

### Three Animation Channels

Each trigger contains three animation channels:

| Channel | Purpose |
|---------|---------|
| `preset` | Built-in Droip animation presets (fade in, slide up, etc.) |
| `textAnimation` | Text-specific animations (letter-by-letter, word-by-word) |
| `custom` | Custom keyframe-based animations with full control |

### Animation Properties (Keyframes)

Each keyframe in the `custom` channel animates one property:

#### `move` — Translate Element

Moves element along X, Y, Z axes using CSS transforms.

```json
{
  "property": "move",
  "duration": 500,
  "delay": 0,
  "easing": "ease-out",
  "start": {
    "x": { "value": "auto", "unit": "auto" },
    "y": { "value": 20, "unit": "px" },
    "z": { "value": "auto", "unit": "auto" }
  },
  "end": {
    "x": { "value": "auto", "unit": "auto" },
    "y": { "value": 0, "unit": "px" },
    "z": { "value": "auto", "unit": "auto" }
  },
  "setAsInitial": true,
  "active": true
}
```

**Units**: `px`, `%`, `auto` (auto = no change)
**CSS output**: `transform: translateX() translateY() translateZ()`

#### `scale` — Scale Element

```json
{
  "property": "scale",
  "duration": 300,
  "delay": 0,
  "easing": "ease-out",
  "start": {
    "x": { "value": 0.8, "unit": "x" },
    "y": { "value": 0.8, "unit": "x" }
  },
  "end": {
    "x": { "value": 1, "unit": "x" },
    "y": { "value": 1, "unit": "x" }
  },
  "setAsInitial": true,
  "active": true
}
```

**Units**: `x` (multiplier, e.g., `1.1` = 110%), `none` (no change)
**CSS output**: `transform: scaleX() scaleY()`

#### `rotate` — Rotate Element

```json
{
  "property": "rotate",
  "duration": 500,
  "delay": 0,
  "easing": "ease-in-out",
  "start": {
    "x": { "value": "auto", "unit": "auto" },
    "y": { "value": "auto", "unit": "auto" },
    "z": { "value": 0, "unit": "deg" }
  },
  "end": {
    "x": { "value": "auto", "unit": "auto" },
    "y": { "value": 180, "unit": "deg" },
    "z": { "value": "auto", "unit": "auto" }
  },
  "setAsInitial": false,
  "active": true
}
```

**Units**: `deg`, `auto` (auto = no change)
**CSS output**: `transform: rotateX() rotateY() rotateZ()`

#### `skew` — Skew Element

```json
{
  "property": "skew",
  "duration": 300,
  "delay": 0,
  "easing": "ease",
  "start": {
    "x": { "value": "auto", "unit": "auto" },
    "y": { "value": "auto", "unit": "auto" }
  },
  "end": {
    "x": { "value": 5, "unit": "deg" },
    "y": { "value": 0, "unit": "deg" }
  },
  "setAsInitial": false,
  "active": true
}
```

**Units**: `deg`, `auto`
**CSS output**: `transform: skewX() skewY()`

#### `fade` — Change Opacity

```json
{
  "property": "fade",
  "duration": 700,
  "delay": 200,
  "easing": "ease-out",
  "start": { "value": 0 },
  "end": { "value": 1 },
  "setAsInitial": true,
  "active": true
}
```

**Values**: `0` (invisible) to `1` (fully visible)
**CSS output**: `opacity: <value>`

#### `size` — Change Dimensions

```json
{
  "property": "size",
  "duration": 300,
  "delay": 0,
  "easing": "ease-out",
  "start": {
    "width": { "value": "auto", "unit": "auto" },
    "height": { "value": 0, "unit": "px" }
  },
  "end": {
    "width": { "value": "scrollWidth", "unit": "scrollWidth", "unitonly": true },
    "height": { "value": "scrollHeight", "unit": "scrollHeight", "unitonly": true }
  },
  "setAsInitial": true,
  "active": true
}
```

**Units**: `px`, `%`, `auto`, `scrollHeight` (full content height), `scrollWidth` (full content width)
**CSS output**: `width: <value>; height: <value>;`

#### `filter` — CSS Filter Effects

```json
{
  "property": "filter",
  "duration": 500,
  "delay": 0,
  "easing": "ease",
  "start": {
    "opacity": { "value": 100, "unit": "%" },
    "blur": { "value": 0, "unit": "px" },
    "brightness": { "value": 50, "unit": "%" },
    "contrast": { "value": 100, "unit": "%" },
    "saturate": { "value": 100, "unit": "%" },
    "invert": { "value": 0, "unit": "%" },
    "grayscale": { "value": 0, "unit": "%" },
    "hue-rotate": { "value": 0, "unit": "deg" },
    "sepia": { "value": 0, "unit": "%" }
  },
  "end": {
    "blur": { "value": 5, "unit": "px" }
  },
  "setAsInitial": false,
  "active": true
}
```

**CSS output**: `filter: opacity(100%) blur(5px) brightness(50%)...`

#### `background-position` — Animate Background

```json
{
  "property": "background-position",
  "duration": 1000,
  "delay": 0,
  "easing": "linear",
  "start": {
    "x": { "value": "auto", "unit": "auto" },
    "y": { "value": "auto", "unit": "auto" }
  },
  "end": {
    "x": { "value": -1100, "unit": "px" },
    "y": { "value": "auto", "unit": "auto" }
  },
  "setAsInitial": false,
  "active": true
}
```

**CSS output**: `background-position-x: <value>; background-position-y: <value>;`

#### Additional Animation Properties

These are also supported by Droip's animation engine:

| Property | CSS Output | Description |
|----------|-----------|-------------|
| `color` | `color: <value>; fill: <value>;` | Text/fill color animation |
| `border-color` | `border-color: <value>;` | Border color animation |
| `border-radius` | All four corner radii | Border radius animation |
| `background-color` | `background: <value>;` | Background color animation |
| `background-size` | `background-size: <w> <h>;` | Background size animation |
| `background-size-position` | Background size + position combined | Both size and position |
| `class-change` | Adds/removes CSS class | Toggle style block class |

### Keyframe Object Reference

Every keyframe in a `custom` animation has these fields:

| Field | Type | Description |
|-------|------|-------------|
| `active` | boolean | Whether this keyframe is enabled |
| `duration` | number | Animation duration in milliseconds |
| `delay` | number | Delay before animation starts (ms) |
| `easing` | string | Easing function (see list below) |
| `property` | string | What to animate (see property types above) |
| `start` | object | Starting values (property-specific) |
| `end` | object | Ending values (property-specific) |
| `setAsInitial` | boolean | If `true`, sets the `start` values as the element's initial CSS state before animation runs |

### Supported Easing Functions

| Easing | Description |
|--------|-------------|
| `linear` | Constant speed |
| `ease` | Default CSS easing |
| `ease-in` | Slow start |
| `ease-out` | Slow end |
| `ease-in-out` | Slow start and end |
| `cubic-bezier(x1,y1,x2,y2)` | Custom curve (e.g., `cubic-bezier(0.25,1,0.5,1)`) |

### `setAsInitial` — Pre-Animation State

When `setAsInitial: true`, Droip generates CSS that sets the element's initial state BEFORE the JavaScript animation runs. This is critical for scroll-triggered animations where the element needs to start hidden/offset:

- **Transform properties** (`move`, `rotate`, `scale`, `skew`): Combined into a single `transform` CSS rule
- **Other properties** (`fade`, `size`, `filter`, etc.): Applied as individual CSS properties

Generated CSS selector: `[data-droip='<elementId>'] { transform: translateY(20px); opacity: 0; }`

For preset/textAnimation on `scroll-into-ele` or `scroll-out-ele` triggers, elements are also hidden with `visibility: hidden` until the animation runs.

### Animation Trigger Metadata

Each animation trigger entry has these configuration fields:

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Display name for the animation (e.g., "Fade In on Scroll") |
| `active` | boolean | Whether the entire trigger is enabled |
| `maxTime` | number | Maximum animation timeline duration (ms) |
| `livePreviewOn` | boolean | Whether preview is active in editor |
| `scrollArea` | string | Scroll detection area (usually `"viewport"`) |
| `repeat` | number | Number of times to repeat (1 = play once) |

### Device Targeting

Interactions can be restricted to specific devices:

```json
{
  "deviceAndClassList": {
    "classList": {},
    "devices": { "0": "md", "1": "tablet", "2": "mobile" },
    "applyToClass": false
  }
}
```

| Device Key | Description |
|-----------|-------------|
| `md` | Desktop |
| `tablet` | Tablet |
| `mobile` | Mobile portrait |
| `mobileLandscape` | Mobile landscape |

### Class-Based Animation Targeting

When `applyToClass: true` in a target's `____info` object, the animation targets all elements with a specific class instead of a single element. The `classApplyOnly` field controls the scope:

| Value | Behavior |
|-------|----------|
| `childrens` | Applies to children of the trigger element |
| `siblings` | Applies to sibling elements |
| `trigger-siblings` | Applies to direct children of the trigger's parent |
| `trigger` | Applies to the trigger element itself |
| `*` (default) | Applies to all matching elements |

### Target Element Info

When targeting specific elements, a companion `____info` entry stores metadata:

```json
{
  "<elementId>____info": {
    "applyToClass": false,
    "classApplyOnly": "childrens",
    "styleBlockId": "<styleBlockId>"
  }
}
```

---

## 4. Tabs Animation

Tab elements have built-in transition support for switching between panels:

```json
{
  "name": "tabs",
  "properties": {
    "active_tab": 0,
    "animationName": "fade",
    "easing": "ease",
    "duration": 100
  }
}
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `active_tab` | number | `0` | Index of the initially active tab |
| `animationName` | string | — | Animation type for tab transitions (e.g., `"fade"`) |
| `easing` | string | `"ease"` | CSS easing function |
| `duration` | number | `100` | Animation duration in milliseconds |

---

## 5. Common Animation Patterns

### Fade In on Scroll

Element fades from invisible to visible when scrolled into view:

```json
{
  "interactions": {
    "elementAsTrigger": {
      "scroll-into-ele": {
        "custom": {
          "0": {
            "data": {
              "<selfId>": {
                "0": {
                  "active": true,
                  "duration": 700,
                  "delay": 0,
                  "easing": "ease-out",
                  "property": "fade",
                  "start": { "value": 0 },
                  "end": { "value": 1 },
                  "setAsInitial": true
                }
              }
            },
            "name": "Fade In",
            "active": true,
            "maxTime": 1000,
            "scrollArea": "viewport",
            "repeat": 1,
            "deviceAndClassList": {
              "classList": {},
              "devices": { "0": "md" },
              "applyToClass": false
            }
          }
        },
        "preset": {},
        "textAnimation": {}
      }
    }
  }
}
```

### Slide Up on Scroll

Element slides up from below while fading in:

```json
{
  "data": {
    "<selfId>": {
      "0": {
        "active": true,
        "duration": 700,
        "delay": 0,
        "easing": "ease-out",
        "property": "move",
        "start": { "x": {"value":"auto","unit":"auto"}, "y": {"value":30,"unit":"px"}, "z": {"value":"auto","unit":"auto"} },
        "end": { "x": {"value":"auto","unit":"auto"}, "y": {"value":0,"unit":"px"}, "z": {"value":"auto","unit":"auto"} },
        "setAsInitial": true
      },
      "1": {
        "active": true,
        "duration": 700,
        "delay": 0,
        "easing": "ease-out",
        "property": "fade",
        "start": { "value": 0 },
        "end": { "value": 1 },
        "setAsInitial": true
      }
    }
  }
}
```

### Hover Scale + Lift

Mouseenter/mouseleave pair for card lift effect:

```json
{
  "mouseenter": {
    "custom": {
      "0": {
        "data": {
          "<selfId>": {
            "0": {
              "property": "move",
              "duration": 300,
              "easing": "ease-out",
              "start": { "x": {"value":"auto","unit":"auto"}, "y": {"value":"auto","unit":"auto"}, "z": {"value":"auto","unit":"auto"} },
              "end": { "x": {"value":"auto","unit":"auto"}, "y": {"value":-5,"unit":"px"}, "z": {"value":"auto","unit":"auto"} },
              "setAsInitial": false,
              "active": true
            },
            "1": {
              "property": "scale",
              "duration": 300,
              "easing": "ease-out",
              "start": { "x": {"value":"none","unit":"none"}, "y": {"value":"none","unit":"none"} },
              "end": { "x": {"value":1.05,"unit":"x"}, "y": {"value":1.05,"unit":"x"} },
              "setAsInitial": false,
              "active": true
            }
          }
        },
        "name": "Card Hover In",
        "active": true,
        "maxTime": 500
      }
    }
  },
  "mouseleave": {
    "custom": {
      "0": {
        "data": {
          "<selfId>": {
            "0": {
              "property": "move",
              "duration": 300,
              "easing": "ease-out",
              "start": { "x": {"value":"auto","unit":"auto"}, "y": {"value":-5,"unit":"px"}, "z": {"value":"auto","unit":"auto"} },
              "end": { "x": {"value":"auto","unit":"auto"}, "y": {"value":"auto","unit":"auto"}, "z": {"value":"auto","unit":"auto"} },
              "setAsInitial": false,
              "active": true
            },
            "1": {
              "property": "scale",
              "duration": 300,
              "easing": "ease-out",
              "start": { "x": {"value":1.05,"unit":"x"}, "y": {"value":1.05,"unit":"x"} },
              "end": { "x": {"value":1,"unit":"x"}, "y": {"value":1,"unit":"x"} },
              "setAsInitial": false,
              "active": true
            }
          }
        },
        "name": "Card Hover Out",
        "active": true,
        "maxTime": 500
      }
    }
  }
}
```

### Dropdown Menu (Expand/Collapse)

A dropdown that scales and fades in on hover:

```json
{
  "mouseenter": {
    "custom": {
      "0": {
        "data": {
          "<dropdownId>": {
            "0": {
              "property": "size",
              "duration": 300,
              "easing": "ease-out",
              "start": { "width": {"value":"auto","unit":"auto"}, "height": {"value":0,"unit":"px"} },
              "end": { "width": {"value":"scrollWidth","unit":"scrollWidth","unitonly":true}, "height": {"value":"scrollHeight","unit":"scrollHeight","unitonly":true} },
              "setAsInitial": true,
              "active": true
            },
            "1": {
              "property": "fade",
              "duration": 300,
              "easing": "ease-out",
              "start": { "value": 0 },
              "end": { "value": 1 },
              "setAsInitial": true,
              "active": true
            }
          }
        },
        "name": "Show Dropdown",
        "active": true
      }
    }
  }
}
```

---

## 6. Best Practices

### When to Use CSS Transitions vs Interactions

| Scenario | Use |
|----------|-----|
| Simple hover color/background change | CSS Transitions (style block `md_hover`) |
| Input focus ring/border | CSS Transitions (style block `md_focus`) |
| Hover transform (lift, scale) | CSS Transitions (simpler) or Interactions (more control) |
| Scroll-triggered animations | Interactions (`scroll-into-ele`) |
| Page load animations | Interactions (`loading-start` / `loading-finishes`) |
| Multi-step sequenced animations | Interactions with multiple keyframes |
| Click-triggered animations | Interactions (`click` / `dblclick`) |

### Transition Tips

- Use `transition-property: all` only when needed — specifying exact properties is more performant
- `300ms` with `ease-out` is the most common and natural-feeling combination
- Always include the transition properties in the `md` base variant, not in the hover variant
- State variants (`md_hover`, `md_focus`) only need to include properties that change

### Interaction Tips

- Always pair `mouseenter` with `mouseleave` to reverse hover animations
- Use `setAsInitial: true` for scroll animations so elements start in their pre-animation state
- Multiple keyframes on the same element can animate different properties simultaneously (e.g., `move` + `fade`)
- The `delay` field is useful for staggering animations on multiple child elements
- Use `scrollArea: "viewport"` for standard scroll-into-view triggers

### Recommendation for MCP Symbol Creation

For symbols created via the MCP bridge:

1. **Use CSS transitions** for hover/focus effects — they're simpler and don't require JavaScript
2. **Avoid complex interactions** in programmatically created symbols — add them in the Droip editor where you can preview and fine-tune
3. **If you must add interactions**, start with simple single-property animations (e.g., fade on scroll)
4. **Always validate** that `setAsInitial` elements have proper start values that make visual sense before the animation triggers
