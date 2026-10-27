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

1. Copy this entire directory to your Synology web server home directory (usually `/volume1/web/` or similar)
2. Ensure PHP is enabled in Synology Control Panel > Web Station
3. Set appropriate permissions (PHP needs write access to `content/`, `users/`, `config/`)
4. Access via your Synology web address

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
