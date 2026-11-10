# BasicWeb Content System

A simple, elegant markdown-to-HTML publishing system designed for Synology NAS Basic Web Service.

## Features

- **Newspaper-style design**: Times-like serif fonts, clean and elegant
- **Markdown support**: Headers, bold, italics, links (bold, uncolored), images
- **User management**: Admin creates users/passwords
- **Author profiles**: Each user can set a full name, title, and short biography for public bylines
- **Per-user workflow**: Each user can create/edit markdown and publish to HTML
- **Chronological front page**: Lists all published articles by date
- **Clean article view**: Compact masthead and print-style previous/next links
- **Print-friendly**: Dedicated print styles for a broadsheet-like page
- **Page-turn between stories**: Article pages turn like paper — the Previous/Next story links curl the page over its spine in 3D and the next story settles into place (arrow keys work too). Tune perspective/speed or turn it off in **Admin → Site settings**
- **Promotable lead story**: Editors promote a published story to the top of the front page (one lead at a time) from the dashboard or their article list; the lead runs with an extended, multi-paragraph newspaper excerpt
- **File-based**: No database required, just copy to web directory
- **No AI dependencies**

## Deployment to Synology

1. Copy/clone this directory into a **subdirectory of your web root** — e.g. `/volume1/web/news/` — and enable PHP (Web Station). The app works at the site root or in any subdirectory; login redirects adjust automatically.
2. Optionally copy the contents of `homepage/` (a static `index.html` plus a self-hosted `vendor/three.min.js`) to the **web root** for a visual-illusion landing page that links into the newsroom at `news/`.
3. Set permissions: PHP/web server needs write access to `content/`, `content/_trash/`, `cache/`, and `media/` (avoid `777`).
4. Initialize and test (CLI): `bin/basicweb rebuild --all && bin/basicweb test`.
5. **Security**: change the default admin password (log in at `/news/login.php` with `admin`/`admin`, then you’ll be asked to set a new password) immediately and use HTTPS. Block sensitive paths (`/news/.git`, `/news/bin`, `/news/config`, `/news/includes`, `/news/users`, `/news/content/_*`) in your web server config.
   - Nginx: see `deploy/nginx-basicweb.conf`. **Nginx will not pick this up automatically** just because it’s in your web root. You must include/paste it into the site’s `server {}` config (Synology Web Station/Portal/reverse proxy/nginx config) and reload nginx (`nginx -t` then reload). The rules are written for a `/news/` subpath install; adjust prefixes if you serve the app elsewhere.

## Initial Setup

1. Go to `/news/login.php` and sign in with the default admin account: username `admin`, password `admin`.
2. **Change the admin password immediately** when prompted.
3. Create user accounts from the admin area (`/news/admin/`).
4. Writers, subeditors, and editors all sign in at the same `/news/login.php` page and are taken to the right area for their role.

## Author profiles

Users can open **Profile** in their workspace to set the full name, title, and short biography that appear with their published stories. Admins can edit any user's profile from **Admin → Users → Edit profile**. If a full name is not set, the site falls back to the username.

## File Structure

- `index.php` - Front page (chronological article listing)
- `article.php` - Article view page
- `admin/` - Admin panel for user management
- `user/` - User panel for creating/editing/publishing
- `content/` - Stored markdown and published HTML files
- `users/` - User authentication data
- `config/` - Configuration files
- `css/` - Stylesheets
- `fonts/` - Self-hosted Noticia Text webfonts (no external font requests)
- `includes/` - Shared PHP functions
- `homepage/` - Optional static landing page for the web root (three.js illusion), copy its contents next to the app folder

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
