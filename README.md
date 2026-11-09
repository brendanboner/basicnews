# BasicWeb Content System

A simple, elegant markdown-to-HTML publishing system designed for Synology NAS Basic Web Service.

## Features

- **Newspaper-style design**: Times-like serif fonts, clean and elegant
- **Markdown support**: Headers, bold, italics, links (bold, uncolored), images
- **User management**: Admin creates users/passwords
- **Per-user workflow**: Each user can create/edit markdown and publish to HTML
- **Chronological front page**: Lists all published articles by date
- **Clean article view**: Hidden back button that appears on hover
- **File-based**: No database required, just copy to web directory
- **No AI dependencies**

## Deployment to Synology

1. Copy/clone this directory to your Synology web server home directory (usually `/volume1/web/` or similar) and enable PHP (Web Station).
2. Set permissions: PHP/web server needs write access to `content/`, `content/_trash/`, `cache/`, and `media/` (avoid `777`).
3. Initialize and test (CLI): `bin/basicweb rebuild --all && bin/basicweb test`.
4. **Security**: change the default admin password (log in at `/login.php` with `admin`/`admin`, then you’ll be asked to set a new password) immediately and use HTTPS. Block sensitive paths (`/.git`, `/bin`, `/config`, `/includes`, `/users`, `/content/_*`) in your web server config.
   - Nginx: see `deploy/nginx-basicweb.conf`. **Nginx will not pick this up automatically** just because it’s in `/volume1/web` (the document root). You must include/paste it into the site’s `server {}` config (Synology Web Station/Portal/reverse proxy/nginx config) and reload nginx (`nginx -t` then reload). Rules apply to the site’s URI paths; they work for root or subpath installs when placed in the correct server block.

## Initial Setup

1. Go to `/login.php` and sign in with the default admin account: username `admin`, password `admin`.
2. **Change the admin password immediately** when prompted.
3. Create user accounts from the admin area (`/admin/`).
4. Writers, subeditors, and editors all sign in at the same `/login.php` page and are taken to the right area for their role.

## File Structure

- `index.php` - Front page (chronological article listing)
- `article.php` - Article view page
- `admin/` - Admin panel for user management
- `user/` - User panel for creating/editing/publishing
- `content/` - Stored markdown and published HTML files
- `users/` - User authentication data
- `config/` - Configuration files
- `css/` - Stylesheets
- `includes/` - Shared PHP functions

## How a story gets published

This site uses a simple newsroom workflow. A story moves through a few clear stages, and only an editor can make it live for readers.

### The story stages
- **Draft** – You’re still writing. It isn’t ready for review, and readers can’t see it.
- **Submitted** – You’ve sent the story to the editors. It’s waiting in the review queue.
- **In review** – A subeditor or editor is reading and editing it.
- **Approved** – An editor has approved it. It’s ready to publish, but readers still can’t see it until it’s published.
- **Published** – The story is live on the website. Only published stories appear to readers.

### Writing and publishing a new story
1. **You (the writer/contributor)** write the story and **Save Draft**. When it’s ready, choose **Submit for Review**. That moves it to **Submitted**.
2. **A subeditor or editor** reviews it. They may edit the text, add notes, or send it back to you as a **Draft** if changes are needed.
3. **An editor** approves the story and then **publishes** it. Publishing is the step that makes it appear on the public site.

### If you change a story after it’s been published
This part matters:
- **If an editor updates a published story:** the change can go live right away, and the published story stays on the site.
- **If a writer (contributor) updates a published story:** the new changes do **not** stay live immediately. When you save, the story is sent back to the editors as **Submitted** and the old live version is taken down until an editor reviews the changes and publishes the story again.

That way, readers only see the version that has been checked and approved.

### A quick note on accounts
If you’re using the default setup, the admin login starts as `admin` / `admin`. **Change that password right away** after you log in.

## Supported Markdown

- `# Header 1`, `## Header 2`, `### Header 3`
- `**bold text**`
- `*italic text*`
- `[link text](url)` - rendered as bold, uncolored
- `![alt text](image.jpg)` - images are reformatted by CSS
