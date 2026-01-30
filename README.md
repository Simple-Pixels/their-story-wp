# Their Story WordPress Plugin

A WordPress plugin for story creation, moderation, and collection. Stories can be turned into pre-built books for sale.

## User Roles

- **Storyteller**: Can create and manage their own stories, view submissions, and close stories
- **Administrator**: Can view all stories, moderate submissions, manage storytellers, and close any story

## Installation

1. Upload the plugin files to `/wp-content/plugins/their-story-wp/`
2. Activate the plugin through the WordPress admin panel
3. Create storyteller user accounts with the "Storyteller" role

## Usage

### For Storytellers

1. Access "My Stories" from the WordPress admin menu
2. Click "Create New Story" and enter a title (optional password)
3. Share the unique story link with visitors
4. View and manage submissions on the story page
5. Close the story when ready to prevent new submissions

### For Administrators

1. Access "All Stories" to view all stories from all storytellers
2. Use "Submissions" to review and moderate pending submissions
3. Approve or delete submissions from the submissions page or story page
4. View submission images in a lightbox with navigation

### For Visitors

1. Visit a story using the unique link
2. Enter password if required
3. Submit messages with optional images (up to 5 per submission)
4. View approved messages and images in the gallery
5. Click images to view in full-screen lightbox

## Technical Details

- Stories are stored as WordPress pages with custom meta fields
- Submissions use custom post type `story_submission`
- Images are stored as WordPress attachments
- Unique links use rewrite rules: `/story/{unique-link}/`
- AJAX-powered interactions throughout
- Responsive design for mobile and desktop