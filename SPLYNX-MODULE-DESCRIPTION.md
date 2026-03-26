# Client Config — Splynx Add-on Module Description

**Module name:** splynx-addon-client-config
**Version:** 1.0.0
**Author:** DK Connect
**Type:** Splynx Add-on (Yii2 module)
**Category:** Tools

---

## What This Module Does

Client Config adds a **"Client Config" tab** to the Splynx customer detail page. It gives administrators a rich text notepad — powered by Quill.js — where they can store free-form configuration notes, site details, CPE information, and any other per-customer documentation directly inside Splynx.

Every save is tracked with a **full audit history** so the team can see who changed what and when, view side-by-side diffs of previous versions, and restore earlier content with one click.

---

## How It Should Function Within Splynx

### 1. Customer Tab Integration
- The module registers a new tab called **"Client Config"** (icon: `fa-file-text-o`) on the customer detail page.
- The tab appears at position 90 (after the standard Splynx tabs like Information, Services, Billing, etc.).
- It is registered via Splynx's `customer.tabs` event system in `Module.php`.
- Each customer has their own independent config notepad — there is one config entry per customer.

### 2. Rich Text Editor (Quill.js)
When an admin opens the Client Config tab for a customer, they see:
- A **header bar** showing the last-saved status ("Last saved by **[Name]** on [datetime]") and a Save button.
- A **toolbar** with formatting options: headings (H1/H2/H3), bold, italic, underline, strikethrough, text color, background color, ordered/unordered lists, blockquote, code block, links, images, and a "clean formatting" button.
- An **editor area** (minimum 350px height) pre-loaded with the customer's saved content (or empty for new customers).
- A **Save button** that is disabled until the editor content changes. When content changes, the button pulses orange to visually indicate unsaved changes, and the status text changes to "Unsaved changes".
- **Ctrl+S / Cmd+S** keyboard shortcut support for quick saving.

### 3. Save Workflow
When the admin clicks Save:
1. The Save button changes to a spinner with "Saving..." text.
2. The HTML content is sent via AJAX POST to the save endpoint with CSRF token protection.
3. **Server-side HTML sanitization** strips dangerous tags and attributes (event handlers like `onclick`/`onerror`, `javascript:` URLs) while preserving safe formatting tags that Quill produces (p, br, strong, em, u, s, a, ul, ol, li, h1-h3, blockquote, pre, code, img, span, sub, sup).
4. If the content hasn't actually changed compared to what's stored, the server responds with "No changes detected" and skips the database write.
5. If the content has changed:
   - The `client_config` table row is updated (or created if first save) with the new HTML content, the current admin's user ID, their display name, and a timestamp.
   - A new row is inserted into `client_config_history` storing both the **before** and **after** HTML content, who made the change, and when — creating a complete audit trail.
6. The Save button briefly turns green with a checkmark to confirm success, then resets after 2 seconds.
7. The status line updates to show "Last saved by **[Admin Name]** on [datetime]".
8. If the history panel is open, it automatically refreshes to show the new entry.

### 4. Audit History Panel
Below the editor is a collapsible **"Change History"** panel (collapsed by default):
- A clickable header with a badge showing the count of history entries, and a chevron icon that toggles open/closed.
- A table of all previous edits with columns: **Date**, **Changed By**, and **Actions**.
- Two action buttons per history entry:
  - **"View Changes"** — opens a Bootstrap modal with a side-by-side diff view. The "Before" pane has a red-tinted background; the "After" pane has a green-tinted background. This lets admins quickly see what was changed.
  - **"Restore Before"** — loads the previous version's content back into the Quill editor (with a confirmation prompt: "Restore this previous version? You can still save or discard."). The content is loaded into the editor but **not automatically saved** — the admin must click Save to persist the restore, giving them a chance to review first.
- History is **paginated** at 20 entries per page with a "Load More" button that lazy-loads additional entries via the history AJAX endpoint.

### 5. Access Control & Security
- All three controller actions (index, save, history) require an **authenticated administrator** (Yii2 `AccessControl` with `@` role).
- Unauthenticated requests are redirected to `/admin/login/`.
- The admin's identity (user ID + display name) is captured from the Yii2 user session for audit tracking.
- All user input is **HTML-sanitized server-side** before storage to prevent XSS attacks.
- CSRF tokens are included in all POST requests.
- All user-generated content rendered in the history panel and status bar is escaped with `htmlspecialchars()` (server-side) and DOM-based text node escaping (client-side).

### 6. Responsive Design
- The layout adapts to smaller screens — the header bar stacks vertically on mobile (below 768px).
- The history table scrolls horizontally if needed.

---

## Database Schema

The migration (`m260325_000000_create_client_config_tables`) creates two tables:

### `client_config` — one row per customer
| Column | Type | Description |
|--------|------|-------------|
| id | INT PRIMARY KEY | Auto-increment |
| customer_id | INT UNIQUE NOT NULL | Links to the Splynx customer record |
| content | TEXT (default: '') | HTML content from the rich text editor |
| updated_by | INT (default: 0) | Admin user ID who last saved |
| updated_by_name | VARCHAR(255) (default: '') | Admin display name who last saved |
| created_at | DATETIME | When the record was first created |
| updated_at | DATETIME | When the record was last modified |

**Index:** `idx_client_config_customer` on `customer_id`

### `client_config_history` — one row per edit (audit trail)
| Column | Type | Description |
|--------|------|-------------|
| id | INT PRIMARY KEY | Auto-increment |
| customer_id | INT NOT NULL | Links to the Splynx customer record |
| content_before | TEXT | Full HTML content before the change |
| content_after | TEXT | Full HTML content after the change |
| changed_by | INT NOT NULL | Admin user ID who made the change |
| changed_by_name | VARCHAR(255) NOT NULL | Admin display name who made the change |
| created_at | DATETIME | Timestamp of the change |

**Indexes:** `idx_client_config_history_customer` on `customer_id`, `idx_client_config_history_date` on `created_at`

---

## File Structure

```
splynx-addon-client-config/
├── composer.json                 # Composer package (type: splynx-addon, requires splynx-addon-base >=3.1)
├── module.json                   # Splynx module descriptor (name, version, customer_tabs registration)
├── config/
│   ├── config.json               # Addon settings blocks (versioning toggle, max history entries)
│   ├── web.php                   # Yii2 web component config (baseUrl /client-config, admin auth)
│   ├── console.php               # Yii2 console config (empty stub)
│   └── url_rules.php             # URL routing rules
├── migrations/
│   └── m260325_000000_create_client_config_tables.php
├── src/
│   ├── Module.php                # Yii2 module: bootstraps URL rules + customer.tabs event hook
│   ├── controllers/
│   │   └── CustomerController.php    # 3 actions: index (render), save (AJAX POST), history (AJAX GET)
│   ├── models/
│   │   ├── ClientConfig.php          # ActiveRecord — findOrCreate(), auto-timestamps, history relation
│   │   └── ClientConfigHistory.php   # ActiveRecord — audit entries with auto-timestamp on create
│   ├── views/
│   │   └── customer/
│   │       └── index.php             # Full page: Quill toolbar, editor, history panel, diff modal
│   └── assets/
│       ├── ClientConfigAsset.php     # Asset bundle (Quill 2.0.3 CDN + addon CSS/JS)
│       ├── css/
│       │   └── client-config.css     # Styles: header, editor, history panel, diff modal, responsive
│       └── js/
│           └── client-config.js      # Editor init, change detection, AJAX save, Ctrl+S, history
│                                     # toggle, diff viewer, restore, pagination, HTML escaping
└── preview.html                      # Standalone HTML demo (development/preview only, not deployed)
```

---

## Dependencies

- **Splynx addon base** (`splynx/splynx-addon-base >= 3.1`) — provided by Splynx
- **Yii2 framework** — provided by Splynx
- **Quill.js 2.0.3** — loaded from CDN (cdn.jsdelivr.net/npm/quill@2.0.3)
- **Bootstrap 3** — provided by Splynx
- **Font Awesome 4** — provided by Splynx
- **jQuery** — provided by Splynx (via YiiAsset)

No additional Composer dependencies are required beyond what Splynx already provides.

---

## Configurable Settings (via Splynx Admin > Add-ons)

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| Enable versioning | Checkbox | true | Track change history for client config entries |
| Max history entries | Integer | 100 | Maximum audit history entries to keep per customer (0 = unlimited) |

---

## URL Routes

| Method | URL | Action | Description |
|--------|-----|--------|-------------|
| GET | `/admin/customers/client-config/{customerId}` | `customer/index` | Render the editor tab |
| POST | `/admin/customers/client-config/{customerId}/save` | `customer/save` | Save content (returns JSON) |
| GET | `/admin/customers/client-config/{customerId}/history` | `customer/history` | Paginated history (returns JSON) |
