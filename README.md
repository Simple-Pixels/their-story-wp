# Their Story WordPress Plugin

A WordPress plugin for story creation, moderation, and collection. Stories can be turned into pre-built books for sale via WooCommerce integration.

## User Roles

- **Storyteller**: Can create and manage their own stories, view submissions, close stories, and access their own password-protected stories without entering a password
- **Administrator**: Can view all stories, moderate submissions, manage storytellers, close/reopen any story, and export CSV files

## Installation

1. Upload the plugin files to `/wp-content/plugins/their-story-wp/`
2. Activate the plugin through the WordPress admin panel
3. Create storyteller user accounts with the "Storyteller" role
4. Install WooCommerce for book ordering functionality

## Usage

### For Storytellers

1. Access "My Stories" from the WordPress admin menu
2. Click "Create New Story" and enter a title (optional password)
3. Share the unique story link with visitors
4. View and manage submissions on the story page
5. Close the story when ready to prevent new submissions
6. After closing, use "Shop now" to open your **Next steps** page and pick a book format

**Next steps page (required for book shopping):** Create a published WordPress page with slug `next-steps` and add the shortcode `[their_story_book_closed]` (or place that shortcode in Elementor). The theme handles header/footer; fonts load from your theme/Elementor as usual.

### For Administrators

1. Access "All Stories" to view all stories from all storytellers
2. Use "Submissions" to review and moderate pending submissions
3. Approve or delete submissions from the submissions page or story page
4. View submission images in a lightbox with navigation
5. Download CSV exports of story messages 
6. Reopen closed stories if needed

### For Visitors

1. Visit a story using the unique link
2. Enter password if required
3. Submit messages with optional images (up to 5 per submission)
4. View approved messages and images in the gallery
5. Click images to view in full-screen lightbox
6. Optional “What is this?” text link on the story intro

## WooCommerce Integration

When WooCommerce is installed:
- Stories can be linked to variable products for book ordering
- **Storytellers** see a dropdown on product pages to choose any **closed** story (defaults to `?story=` / cookie when valid); others still follow the link from the story with `story` in the URL
- Story details are automatically included in order confirmation emails
- CSV download links are included in admin order emails
- Product variations are auto-selected based on message count
- Story information is preserved throughout the cart and checkout process

## Technical Details

- Stories are stored as WordPress pages with custom meta fields
- Story pages are hidden from the main Pages list; admins can open **Story pages** under All Stories
- Submissions use custom post type `story_submission`
- Images are stored as WordPress attachments
- Unique links use rewrite rules: `/story/{unique-link}/`
- Password-protected story pages load minimal plugin CSS for the password form
- CSV exports are cached for 1 hour to reduce database load
- AJAX-powered interactions throughout
- Responsive design for mobile and desktop
- Slug `next-steps` is the default for the book-picker page; override with filter `their_story_book_closed_slug` if needed
