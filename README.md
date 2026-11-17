# WP Emoji Disabler

Removes WordPress's emoji detection script, styles and TinyMCE emoji plugin — two fewer requests and no inline JS on every page.

## Why

WordPress prints an emoji detection script (`wp-emoji-release.min.js`) plus inline emoji styles on every front-end page and in wp-admin, even when your content never uses emoji. On a plain blog that is pure dead weight: an extra script request, inline CSS, and a TinyMCE plugin. This plugin removes all of it.

## Features

- **Front-end toggle** — removes `print_emoji_detection_script` from `wp_head`, `print_emoji_styles` from `wp_print_styles`, and the emoji staticizers on feeds and emails.
- **Admin toggle** — removes the same scripts from wp-admin.
- **TinyMCE** — drops the `wpemoji` plugin from the editor, so the editor never loads emoji assets either.
- **Optional content stripping** — with "Strip emoji on save" enabled, emoji characters are removed from post titles, content and excerpts when a post is saved (off by default).
- **SVG URL disabled** — the `emoji_svg_url` filter returns false, so nothing tries to fetch Twemoji assets.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it from **Plugins**.
3. Configure it under **Settings → Emoji Disabler**.

## What it does not do

Emoji characters typed in the editor still work — they are plain Unicode, not images. This plugin only removes the JavaScript/CSS WordPress loads to detect and render them. If you also want emoji removed from saved content, enable "Strip emoji on save".

## Uninstall

Deleting the plugin removes its settings. Emoji scripts and styles return to WordPress defaults.
