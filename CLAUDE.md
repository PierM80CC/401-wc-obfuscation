# WC Attribute Obfuscation

## Description
Obfuscates WooCommerce attribute links on single product pages to save crawl budget. Removes `href` from `<a>` tags and replaces with `data-url`, handled by inline JS click delegation.

## Stack
- PHP (WordPress plugin)
- WooCommerce (requires 8.0+)

## Prefix
- Functions: `wco_`
- Constants: `WCO_`

## Files
- `wc-obfuscation.php` — single-file plugin (hooks, JS, CSS)

## Key Functions
- `wco_should_load()` — returns true on `is_product()`, filterable via `wco_should_load`
- `wco_obfuscate_attribute_link()` — hooks `woocommerce_attribute`, removes href, adds data-url
- `wco_inline_script()` — delegated click/auxclick/keydown handlers for `[data-url]` elements
- `wco_inline_css()` — cursor:pointer for obfuscated links

## Deploy on a site
1. Clone or add as submodule in `wp-content/plugins/wc-obfuscation`
2. Activate in WP admin
3. No configuration needed — works automatically on product pages

## Submodule workflow
This plugin is used as a git submodule in site projects.

**After modifying here:**
```bash
# 1. Commit + push in this repo
cd ~/Projects/401-wc-obfuscation
git add . && git commit -m "fix: ..." && git push

# 2. Update ref in each site that uses it
cd ~/Projects/<site>
git submodule update --remote plugins/wc-obfuscation
git add plugins/wc-obfuscation
git commit -m "chore: update wc-obfuscation submodule"
git push

# 3. Deploy on server
ssh <host> "cd ~/files/wp-content && git pull --recurse-submodules"
ssh <host> "cd ~/files && wp spinupwp cache purge-site"
```

## Compatibility
- Works independently — no dependency on Filter Everything Pro
- Uses same `data-url` convention as `pp-filter-obfuscation` (no conflict: different page types)
- Safe to use alongside `pp-filter-obfuscation` (shop pages vs product pages)

## Manual tests
1. Frontend: attribute links on product pages should NOT have href in source
2. Frontend: clicking an attribute link navigates correctly
3. Frontend: Ctrl+click opens in new tab
4. Frontend: middle-click opens in new tab
5. Source: no `<a href="...">` in attribute section of product page HTML
