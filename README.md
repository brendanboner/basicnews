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
4. **Security**: change the default admin password (`/admin/login.php`, `admin`/`admin`) immediately and use HTTPS. Block sensitive paths (`/.git`, `/bin`, `/config`, `/includes`, `/users`, `/content/_*`) in your web server config.
   - Nginx: see `deploy/nginx-basicweb.conf`. **Nginx will not pick this up automatically** just because it’s in `/volume1/web` (the document root). You must include/paste it into the site’s `server {}` config (Synology Web Station/Portal/reverse proxy/nginx config) and reload nginx (`nginx -t` then reload). Rules apply to the site’s URI paths; they work for root or subpath installs when placed in the correct server block.

## Initial Setup

1. Navigate to `/admin/` 
2. Default admin credentials: username `admin`, password `admin` (change immediately!)
3. Create user accounts
4. Users can log in at `/user/` to create and publish articles

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

## Supported Markdown

- `# Header 1`, `## Header 2`, `### Header 3`
- `**bold text**`
- `*italic text*`
- `[link text](url)` - rendered as bold, uncolored
- `![alt text](image.jpg)` - images are reformatted by CSS
