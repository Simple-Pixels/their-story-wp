# Their Story

> **Site-specific** · Powers Their Story (`sharetheirstory.com.au`): storytellers create a story page, share a link so friends and family contribute messages and photos, then close the story and order it as a printed book through WooCommerce.

| | |
| --- | --- |
| Type | Site-specific (Their Story, `sharetheirstory.com.au`) |
| Version | 1.1.4 in the plugin header. `THEIR_STORY_VERSION` still says 1.0.0 (see [Gotchas](#gotchas)). |
| Main file | `their-story.php` |
| Text domain | `their-story` |
| Prefix | `Their_Story` (class), `their_story_` (functions, options, AJAX actions, hooks), `_their_story_` / `_story_` (meta), `THEIR_STORY_` (constants) |
| Repository | `Simple-Pixels/their-story-wp` |

## What it does

**Storytellers** (custom `storyteller` role)

- Log in to a stripped-back **My Stories** dashboard in wp-admin (the rest of wp-admin and the admin bar are hidden from them).
- Create a story: title, optional password, the subject's name and the contributor's relation label. The story becomes a WordPress **page** with a share link `/story/<unique-link>/`.
- See and moderate contributions, change the password, close and reopen the story.
- After closing, go to the **Next steps** page to choose a book format and check out. A one-off **setup fee** is added to the cart. Reorders of an existing story are supported.

**Contributors** (no account needed)

- Open the share link, enter the password if set, and answer the contribution form (configurable questions, up to 5 images).
- Land on a thank-you page.

**Administrators**

- **All Stories**, **Submissions** (moderation queue), **Story Pages** and **Settings** screens.
- Approve or delete submissions, view images in a lightbox, export a story's messages as CSV, close/reopen any story.

**WooCommerce**

- Stories are linked to book products; the variation is chosen from the message count.
- Story details appear in the cart, order, and order emails (with a CSV download link for admins).
- On payment, the order is linked to its story and the storyteller is emailed.

## Requirements

| Requirement | Version / notes |
| --- | --- |
| WordPress | No minimum declared. |
| PHP | No minimum declared. Uses `??`, so 7.0+. |
| Required plugins | **WooCommerce** for book ordering. The story/contribution side works without it. |
| Optional plugins | **Elementor** (Next steps page layout), **Yoast** or **Rank Math** (story pages are set to noindex and removed from Yoast's sitemap), the Simple Pixels **gradient background** plugin (`spgb_*`), used for the storyteller dashboard background if active. |
| External services | Google Fonts (Inter). The `Diolce` font is loaded from the production uploads folder. |

## Installation

1. Deploy the repository root to `wp-content/plugins/their-story-wp/`.
2. Activate **Their Story**. Activation registers the `/story/…` rewrite rule and flushes permalinks.
3. Install and configure WooCommerce, and create the variable book products.
4. Create a published page with slug **`next-steps`** containing `[their_story_book_closed]` (layout it in Elementor if needed).
5. Fill in **All Stories → Settings**.
6. Create storyteller accounts with the **Storyteller** role (or let them register, depending on site setup).

## Configuration

**All Stories → Settings** (`manage_options`). Each setting is its own option in `wp_options`.

| Section | Options |
| --- | --- |
| Customer Portal Content | `their_story_welcome_heading`, `their_story_welcome_body`, `their_story_contributor_message` |
| Pricing | `their_story_setup_fee` (default `300`) |
| Email Notifications | `their_story_admin_email` (default `grant@sharetheirstory.com.au`), `their_story_support_email` (default `support@sharetheirstory.com.au`), `their_story_email_subject`, `their_story_email_body` |
| Story-Ready Email | Subject and body of the email sent to the storyteller |
| Terms & Conditions | `their_story_terms_content` |
| URLs | `their_story_about_url`, `their_story_what_is_this_url`, `their_story_purchases_url` |
| Form Questions | `their_story_q_*` (label and help text for each contribution form question, plus an extra question) |

## Usage

| Item | Where |
| --- | --- |
| `[their_story_book_closed]` | On the `next-steps` page. Book-format picker for a closed story (reads `?story=` or the `their_story_id` cookie). |
| `/story/<unique-link>/` | Share link. 301-redirects to the story page. |
| `?their_story_begin_checkout=1` | Storyteller checkout entry point (adds the book + setup fee or reorder to the cart). |
| `?their_story_thankyou=1` | Contributor thank-you page. |
| `?their_story_export_csv=1&story_id=<id>` | CSV export (administrators only, cached 1 hour). |

## How it works

### Data model

| Thing | Stored as |
| --- | --- |
| Story | A WordPress **page**, authored by the storyteller. Hidden from the normal Pages list. Optional `post_password`. |
| Story meta | `_storyteller_id`, `_story_unique_link`, `_story_closed`, `_their_story_contribution_subject_name`, `_their_story_contribution_relation_label` |
| Contribution | `story_submission` post type (no UI), created as `pending`, set to `publish` when approved. Images are attachments. |
| Pending checkout | User meta `_their_story_pending_creation` / `_their_story_pending_reorder` (expire after 1 hour) |
| Order link | Order item meta `_their_story_pending`, `_their_story_id`. Order meta `_their_story_created`, `_their_story_id`, `_their_story_reorder`. |

### Request flow

1. Storyteller creates a story through AJAX (`their_story_create_story`). The page and unique link are created immediately.
2. Contributors submit through `wp_ajax_nopriv_their_story_submit_message`. Submissions are pending until moderated.
3. Contributors see the story page with the form and approved gallery. The story owner bypasses the password (`post_password_required` filter).
4. Storyteller closes the story, picks a product on Next steps, and is sent through `their_story_begin_checkout` to WooCommerce checkout with the setup fee applied in `woocommerce_before_calculate_totals`.
5. `create_story_on_payment()` runs on `payment_complete`, `processing`, `on-hold` and `completed`, guarded by `_their_story_created` so it only runs once. It links the order to the story and sends emails.

### Endpoints

| Endpoint | Purpose / checks |
| --- | --- |
| `/story/<unique-link>/` | Rewrite rule (`their_story_link` query var). 301-redirects to the story page, 404 if not found. |
| `admin-ajax.php` actions `their_story_*` | `create_story`, `delete_story`, `update_password`, `submit_message` (also `nopriv`), `moderate_submission`, `close_story`, `reopen_story`, `delete_all`, `get_products`, `prepare_checkout`, `prepare_reorder`, `help_request`. Each checks a nonce; storyteller/admin actions also check role. |
| `?their_story_begin_checkout=1` | Storyteller only. Builds the cart (book + setup fee, or reorder) and redirects to checkout. |
| `?their_story_thankyou=1` | Contributor thank-you page. |
| `?their_story_export_csv=1&story_id=<id>` | Administrators only. CSV cached for 1 hour. |

### Front end

- Story page content (intro, contribution form, approved gallery) is appended through `the_content` (priority 20).
- `frontend.js` (gallery, lightbox) and `contribution-form.js` (multi-step form, uploads) load on story pages; `admin.js` on the plugin's admin screens. Password-protected story pages load only `story-password.css`.
- Asset versions come from `filemtime()`, so edits bust caches automatically.
- Fonts: Inter from Google Fonts; `Diolce` from the production uploads folder.
- Story pages are `noindex` (core, Yoast, Rank Math) and excluded from Yoast's sitemap.

## File structure

```
their-story.php                      Bootstrap: header, constants, init, activation/deactivation
includes/
  class-their-story.php              Everything PHP (≈3,300 lines): role, post type, rewrites, admin menus,
                                     settings, AJAX handlers, story page output, CSV export, WooCommerce hooks, emails
templates/
  storyteller-dashboard.php          My Stories (storyteller wp-admin screen)
  admin-dashboard.php                All Stories
  admin-submissions.php              Submissions moderation
  admin-story-pages.php              Story Pages list
  story-page-content.php             Story page body (intro, gallery)
  contribution-form.php              Contributor form
  contributor-thankyou.php           Contributor thank-you page
  book-closed-inner.php              [their_story_book_closed] output
assets/css/                          admin, frontend, storyteller-dashboard, book-closed, story-password
assets/js/
  admin.js                           Dashboards, moderation, lightbox, checkout prep
  contribution-form.js               Multi-step contributor form and uploads
  frontend.js                        Story page gallery and lightbox
```

## Hooks

| Hook | Type | Purpose |
| --- | --- | --- |
| `their_story_book_closed_slug` | filter | Slug of the Next steps page (default `next-steps`). |
| `their_story_book_closed_page_id` | filter | Page ID fallback if the slug isn't found. |
| `their_story_about_url` | filter | "About" link on the thank-you page. |
| `their_story_register_url` | filter | Register link on the thank-you page. |
| `spgb_gradient_background_config` | filter | Applied when passing config to the gradient background plugin. |

## Gotchas

- **Hard-coded email address.** The story-closed notification (`ajax_close_story()`), help requests (`ajax_help_request()`) and reorder notifications (`create_story_on_payment()`) are sent to `grant@sharetheirstory.com.au` directly, ignoring the Admin notification email setting.
- **Version mismatch.** Plugin header is 1.1.4 but `THEIR_STORY_VERSION` is 1.0.0. Assets are versioned by file modification time (`filemtime()`), so caches still bust, but keep the two in sync.
- **One very large class.** Nearly all logic is in `class-their-story.php`. Search by AJAX action or hook name to find things. Split it up when making large changes rather than adding more to it.
- **Stories are pages.** Anything that lists, exports or deletes pages (page builders, SEO plugins, bulk tools) also sees stories. They're hidden from the admin Pages list by `pre_get_posts`, not by post type.
- **Next steps depends on a slug.** Rename the `next-steps` page and book shopping breaks unless the `their_story_book_closed_slug` filter is updated.
- **Storyteller access is locked down by redirect.** `restrict_admin_access()` sends storytellers to My Stories from any wp-admin URL. If you add a screen storytellers need, allow it there.
- **Font from production.** `Diolce` loads from `https://sharetheirstory.com.au/wp-content/uploads/…`, so staging depends on production and may hit CORS.
- **The role is re-added on every `init`.** Changing capabilities in `add_storyteller_role()` won't apply to the existing role until it's removed first.
- **Order hooks fire several times** (payment complete, processing, on-hold, completed). Keep `_their_story_created` as the guard when changing that flow.
- **Mixed coding style.** Spaces and `array()` with no Yoda conditions. Match the file you're in for small changes.
- No `.gitignore`, uninstall routine or text-domain loading.

## Development

- Coding standards, git workflow and environment notes: [CLAUDE.md](CLAUDE.md).
- Versioning: bump the plugin header **and** `THEIR_STORY_VERSION` together.
- Testing (staging with WooCommerce test payments): create a story as a storyteller, contribute as a logged-out visitor with images, approve it as admin, close the story, order a book, and check the order emails and story link. Repeat a reorder.

## Changelog

No changelog file. See the git history (`git log --oneline`). Latest: editable form questions and settings tabs; fixes for extra-question placeholders and a redeclaration fatal.
