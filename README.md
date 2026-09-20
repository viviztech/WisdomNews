# Wisdom News

A lightweight Tamil/English digital news website built with Core PHP, MySQL, PDO, Bootstrap 5, and vanilla JavaScript. It includes a modern public news portal, a secure single-admin CMS, SEO essentials, and a WordPress database importer.

## Requirements

- PHP 8.2 or newer with pdo_mysql, mbstring, fileinfo, and dom
- MySQL 8+ or MariaDB 10.5+
- Apache with mod_rewrite and .htaccess overrides enabled
- HTTPS in production

## Installation

1. Point the Apache document root (or a virtual host) at this project directory.
2. Copy .env.example to .env and set APP_URL and the database credentials.
3. Import database/wisdomnews.sql into MySQL/MariaDB:

       mysql --default-character-set=utf8mb4 -u root -p < database/wisdomnews.sql

   Optionally load the initial Wisdom News category set:

       mysql --default-character-set=utf8mb4 -u root -p < database/seed-categories.sql

4. Ensure Apache/PHP can write to uploads/posts, uploads/branding, and uploads/wordpress.
5. Create the first administrator:

       php scripts/create-admin.php --name="Administrator" --username="admin" --email="you@example.com"

   The command prompts for a password of at least 12 characters. A password can instead be supplied temporarily with --password, but the prompt is safer because shell history can retain arguments.
6. Open APP_URL for the public site and APP_URL/admin/ for administration.

## Configuration

The app reads a small .env file without an external package. Never deploy the real .env publicly or commit it. In production use:

    APP_ENV=production
    APP_DEBUG=false
    SESSION_SECURE=true

APP_URL must include any subdirectory, for example https://example.com/wisdomnews. The included .htaccess forces HTTPS except on localhost, provides clean routes, blocks hidden environment files, and prevents PHP execution inside uploads.

## Admin usage

- **Dashboard:** totals and recent posts.
- **Posts:** search, add, edit, publish/draft, view, and delete. Slugs are unique and remain editable.
- **Categories:** add/edit/delete, enable/disable, order, and assign a parent for the live site's “Wisdom Special” hierarchy.
- **Settings:** site name, logo, favicon, email, social links, and footer text.

All write forms use CSRF tokens. Authentication uses password_hash()/password_verify(), secure session settings, and session ID regeneration.

## WordPress migration

The importer reads from the old WordPress database and writes independent copies into the new database. It imports post categories and hierarchy, posts, status, title, slug, content, excerpt, featured-image reference, alt text, and dates. It also preserves WordPress post/category IDs for legacy ?p=123 and ?cat=45 redirects.

Add these values to .env before running:

    WP_DB_HOST=localhost
    WP_DB_PORT=3306
    WP_DB_NAME=old_wordpress_database
    WP_DB_USER=wordpress_user
    WP_DB_PASSWORD=change-me
    WP_TABLE_PREFIX=wp_
    WP_UPLOADS_PATH=/absolute/path/to/wp-content/uploads
    WP_UPLOADS_URL=https://wisdomnews.in/wp-content/uploads

Then run:

    php scripts/wordpress-import.php

The database work runs in a transaction and can be run again: records are matched by their WordPress IDs. Files are copied into uploads/wordpress without overwriting existing copies. Embedded and featured-image URLs under WP_UPLOADS_URL are rewritten to the new local upload path. Keep a database and file backup before any production migration.

Because no WordPress SQL/XML export or wp-content/uploads directory was present in the initial workspace, the importer has not been run against production content. Supply a database copy and uploads directory, run the importer, then compare the reported counts and sample URLs with WordPress before changing DNS.

## URL compatibility

New navigation uses:

- /category/{slug}
- /news/{slug}
- /search?q=keyword

The old live site currently exposes query URLs. /?p={wordpress_id} and /?cat={wordpress_id} resolve to their imported record and issue a targeted 301 redirect. Root-level legacy slugs are also redirected to /news/{slug} when found. Unknown URLs return a real 404.

## Production deployment

1. Back up the WordPress database and uploads.
2. Deploy this project and configure production .env.
3. Import database/wisdomnews.sql, create the administrator, and run the importer.
4. Check imported counts, Tamil text, featured/embedded images, a sample of old ?p= links, search, categories, and the sitemap.
5. Set writable directory permissions narrowly (normally directories 0755; hosting environments vary).
6. Keep APP_DEBUG=false, enable HTTPS, and set SESSION_SECURE=true.
7. Update robots.txt with the final absolute sitemap URL if the site is installed in a subdirectory.
8. Switch the domain only after acceptance checks. The new application has no WordPress runtime dependency after migration.

## Verification

Run syntax checks across PHP files:

    Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }

Run the database-independent Tamil/sanitization smoke checks:

    php tests/runtime-smoke.php

After configuring MySQL, run the transaction-based CRUD checks:

    php tests/database-smoke.php

Functional CRUD and migration tests require a configured MySQL/MariaDB instance. Test the login/logout flow, post/category create-edit-delete operations, drafts, image validation, settings, public pages, search, pagination, Tamil rendering, legacy URLs, and 404 responses against a staging database before production launch.
