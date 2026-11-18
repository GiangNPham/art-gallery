# Art Gallery

An art collection website built with PHP and MySQL. Visitors sign in to explore paintings and artist profiles, filter the collection, and view catalog details. Collection managers can upload artwork and maintain artists, galleries, eras, genres and subjects.

The project also demonstrates offline web scraping: PHP reads locally saved WikiArt pages, extracts artist and painting information, and imports the results into the same gallery.

## Pages

- `galleries.php`: list galleries and their painting counts.
- `gallery-detail.php`: view a gallery's details and linked paintings.
- `gallery-add.php`: add a gallery.
- `gallery-delete.php`: confirm deletion and block it while paintings reference the gallery.
- `genres.php`, `genre-add.php`, `genre-delete.php`: genre catalog with names, descriptions, painting counts, add and protected delete pages.
- `eras.php`: view eras alphabetically by name, with links to details, update and delete.
- `era-detail.php`: view one era's complete name and year information.
- `era-add.php`: add an era after validating its name and years and checking for a duplicate name.
- `era-update.php`: update an era's years while keeping its identifying name unchanged.
- `era-delete.php`: confirm and delete an era.
- `painting-genres.php`: view paintings with their assigned genres, followed by genre descriptions, painting counts and management links. Genre names open `genre-detail.php`, which returns to Painting genres.
- `painting-genre-add.php`: add a genre with a unique name and optional description.
- `painting-genre-delete.php`: confirm deletion; blocks deletion while any existing painting is linked through `PaintingGenres`, including on direct POST requests. Unused stale associations are removed with the genre.
- `subjects.php`, `subject-detail.php`, `subject-add.php`, `subject-delete.php`: browse subjects and painting counts, view linked paintings, add a named subject, and confirm deletion of an unused subject.
- `painting-subjects.php`: show each linked painting once with its image, title and all assigned subjects. Each subject has its own delete link; new links can be added. Subject names and painting counts are listed separately on `subjects.php`.
- `painting-subject-add.php`: link a painting to a subject after validating both IDs and checking for a duplicate link.
- `painting-subject-delete.php`: confirm and remove only the selected painting-subject link.
- `artists.php`: view artists and their painting counts, with links to add and delete artists.
- `artist-add.php`: add an artist with a required last name and optional first name, nationality and birth/death years.
- `artist-delete.php`: confirm deletion; artists with paintings cannot be deleted. The check also runs on submission.
- `art-login.php`: User ID/password form.
- `authenticate.php`: checks Lab14 `Customers` and `CustomerLogon`, then opens `art-main.php`.
- `art-main.php`: basic painting table, artist selection, ADD link and Delete links.
- `art-detail.php`: detailed artwork image, artist, year, medium, dimensions, genre and description.
- `art-add.php`: title, typed artist name, optional year and an uploaded image.
- `art-delete.php`: confirmation followed by deletion of the selected PaintingID.
- `logout.php`: Sign out. `index.php` and `artwork.php` open the gallery.

## Demonstration

1. **Sign in** using `user1` / `pass1`.
2. **Explore the collection** on `art-main.php`. Filter by artist, title or year.
3. **Open a painting** to view its image, artist, year, medium, genres and available descriptive information.
4. **Explore related records** through Artists, Galleries, Eras, Genres and Subjects.
5. **Show offline scraping** at `scripts/scraping/preview.php`: three artists, five paintings per artist, extracted profile information and source links.
6. **Run the offline import**, refresh the gallery, and show that repeated imports reuse existing records.

Additional seeded demonstration accounts are `user2/pass2`, `user3/pass3` and `user4/pass4`. These are local coursework accounts.

## Features

| Area | Capability |
| --- | --- |
| Collection | Browse, filter, view details, upload and delete paintings |
| Artists | Browse names and painting counts; add artists; protect artists referenced by paintings from deletion |
| Galleries | Browse details and linked paintings; add galleries; protect referenced galleries from deletion |
| Eras | Browse, search, add, update years and delete; era names remain fixed on update |
| Genres | Browse descriptions and painting counts; add genres; protect genres linked to paintings from deletion |
| Subjects | Browse and manage subjects; add or remove individual painting-subject associations |
| Offline scraping | Extract saved HTML, preview results, export JSON/SQL and import without duplicate records |
| Form handling | Login sessions, password-hash verification, CSRF tokens, prepared statements and escaped output |

Painting uploads accept JPEG, PNG, GIF or WebP up to 5 MB. Uploaded images and database inserts are handled together; failed saves roll back and remove partially saved upload files.

## Technology and organization

PHP 8.1+, MySQL/MariaDB, HTML and CSS. The scraper uses PHP DOMDocument and DOMXPath. Required PHP extensions: mysqli, mbstring, DOM and libxml. XAMPP provides a convenient local environment.

```text
artwork/
├── README.md                       Project guide and demonstration steps
├── config.php                      Database configuration
├── art-*.php                       Login, collection and painting operations
├── artists.php, galleries.php, …    Catalog modules and their operation pages
├── includes/                       Shared database, authentication and form helpers
├── assets/                         Gallery stylesheet and image placeholder
├── images/art/works/square-medium/  Gallery images, including scraped images
├── sql/                            Original database and account seeds
└── scripts/
    ├── setup-subjects.php           One-time subject ID setup
    └── scraping/
        ├── scraper.php             Offline parser, exports and optional import
        ├── preview.php             Generated-results preview
        ├── test.php                Repeatable development checks
        ├── saved_pages/            Saved HTML and local assets
        ├── output/                 Generated results.json and import.sql
        └── (local tracking notes are excluded from Git)
```

`index.php` and `artwork.php` are alternate gallery entry points. Shared helpers remain in `includes/`; source captures stay separate from generated output. This root README is the single setup and presentation guide. Local development tracking notes are excluded from the shared repository.

## First-time database setup

1. Install XAMPP and start MySQL. Start Apache too if using phpMyAdmin.
2. Open phpMyAdmin, normally `http://localhost/phpmyadmin`, and import **`sql/art-small.sql` first**, then **`sql/users.sql`**.
3. Check `config.php`. Defaults: host `127.0.0.1`, port `3306`, database `art`, user `root`, empty password. The equivalent environment overrides are `ART_DB_HOST`, `ART_DB_PORT`, `ART_DB_NAME`, `ART_DB_USER` and `ART_DB_PASSWORD`.
4. Give the PHP process write access to `images/art/works/square-medium/` for uploads and scraped images.
5. Run the subject setup below before demonstrating subject management.

**Import the seed files only for a new database. Reimporting them can reset existing collection data.** The gallery connection uses utf8mb4 for Vietnamese text. Keep the supplied Apache `.htaccess` restrictions when serving through Apache.

### Subjects setup

The original Subjects seed lacks IDs. From the project root, run this once using your platform's PHP executable:

```sh
php scripts/setup-subjects.php
```

This gives the original 21 subject entries stable IDs, preserves both original Abstract entries and existing painting-subject links, and retains the original table as `Subjects_without_ids_backup`. It refuses changed seed names instead of guessing their IDs. Running it again after successful setup makes no changes. After a fresh database seed import, inspect existing backup/staging tables before repeating the conversion.

The platform sections below show how to make `php` available in the current terminal.

## Run on macOS

Start MySQL in XAMPP Manager. In Terminal, open your project folder and run:

```sh
cd "/path/to/artwork"
export PATH="/Applications/XAMPP/xamppfiles/bin:$PATH"
mkdir -p /tmp/artwork-php-sessions
php -d session.save_path=/tmp/artwork-php-sessions -S localhost:8000 -t .
```

Keep Terminal open. Visit **http://localhost:8000/art-login.php**. The writable session directory prevents lost session/CSRF tokens. Apache is not needed with this development server. Press **Control+C** to stop the website; stop MySQL separately through XAMPP when finished.

## Run on Windows

1. Copy the project to `C:\xampp\htdocs\artwork`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Complete first-time database setup if needed.
4. Open **http://localhost/artwork/art-login.php**.

For setup scripts, scraping and tests, open **Command Prompt**:

```bat
cd /d C:\xampp\htdocs\artwork
set "PATH=C:\xampp\php;%PATH%"
```

You can now run the common `php` commands in this guide. Adjust paths if XAMPP is installed elsewhere. Stop Apache and MySQL in XAMPP Control Panel when finished.

## Run on Linux

These commands assume XAMPP is installed in `/opt/lampp` and the project folder belongs to your regular account:

```sh
sudo /opt/lampp/lampp startmysql
cd "/path/to/artwork"
export PATH="/opt/lampp/bin:$PATH"
mkdir -p "$HOME/.local/share/artwork/sessions"
chmod 700 "$HOME/.local/share/artwork/sessions"
php -d session.save_path="$HOME/.local/share/artwork/sessions" -S localhost:8000 -t .
```

Complete first-time database setup before using collection pages. If phpMyAdmin is unavailable, import the seeds from the project root with `/opt/lampp/bin/mysql -h 127.0.0.1 -u root < sql/art-small.sql`, then repeat for `sql/users.sql`; add `-p` if your MySQL account requires a password.

Keep Terminal open and visit **http://localhost:8000/art-login.php**. Press **Control+C** to stop PHP. Stop MySQL with `sudo /opt/lampp/lampp stopmysql` when finished. Apache is not required for this PHP-server method.

Windows and Linux instructions are documented for portability; runtime verification in this development session was on macOS.

## Offline web scraping

### Assignment scope and saved inputs

The scraper selects **Edouard Manet, Lê Phổ and Trần Văn Cẩn**, with **five valid paintings per artist**. All extraction and testing use locally saved HTML and images. The scraper makes no live WikiArt requests and executes no page JavaScript.

To prepare inputs, manually open each artist's WikiArt profile in Chrome:

- https://www.wikiart.org/en/edouard-manet
- https://www.wikiart.org/en/le-pho
- https://www.wikiart.org/en/tran-van-can

Expand biographies and scroll until images load, then choose **Save Page As → Webpage, Complete** into `scripts/scraping/saved_pages/`. Keep matching `_files` folders beside the HTML. Saved single-file HTML with embedded images is also supported. Save the all-works or detailed-list view when needed for additional paintings and metadata.

Multiple captures for one artist are combined by saved source URL, not filename. A page labeled with an artwork count may contain fewer loaded cards; only paintings and images actually present in the saved files can be extracted.

### Extract, preview and import

Open a second terminal in the project root and configure `php` as shown in your platform section. Generate results without modifying the database:

```sh
php scripts/scraping/scraper.php
```

To generate results and update the gallery automatically while MySQL is running:

```sh
php scripts/scraping/scraper.php --import
```

The command prints extraction warnings, inserted-record counts and the private backup path. Refresh `art-main.php` after importing.

| Page | macOS/Linux PHP server | Windows Apache |
| --- | --- | --- |
| Gallery | http://localhost:8000/art-main.php | http://localhost/artwork/art-main.php |
| Scraping preview | http://localhost:8000/scripts/scraping/preview.php | http://localhost/artwork/scripts/scraping/preview.php |

The preview reads `output/results.json`; it does not scrape or require login. For manual import, select the existing `art` database in phpMyAdmin and import `scripts/scraping/output/import.sql`.

### Extraction and data integration

DOMDocument loads HTML with `LIBXML_NONET`. DOMXPath identifies saved URLs through `og:url` or canonical links, then reads artist headings, schema.org fields and biography paragraphs. Painting cards are found using `artwork-name` or `masonry-detailed-artwork-title` links. Their surrounding card supplies the title, date and local image. Detailed-list records merge by painting source URL even when another capture supplied the selected image.

| Extracted information | Storage/use |
| --- | --- |
| Artist name, nationality, birth/death years, biography | Existing Artists columns |
| Full dates and source provenance | JSON |
| Painting title, available year, image and source link | Existing Paintings columns |
| Medium and description, when present | Fill missing painting fields |
| Genre | Existing Genres and PaintingGenres records |
| Style, original title and exact dimension text | JSON and labeled painting Description text |

Dimension text is retained with its units rather than guessing axis order or rounding into integer Height/Width columns. No unsupported facts are invented. Missing optional details stay absent and profile omissions are reported. Fewer than five valid paintings for an artist blocks SQL import.

Images must be embedded base64 or relative files inside saved_pages. Remote URLs and paths escaping that folder are rejected. Gallery copies use safe `t9_` hash stems and detected jpg/png/gif/webp extensions, matching `art_image()`. Identical copies are reused; collisions never overwrite files.

Imports resolve artist and painting IDs from matching records, preserve existing nonempty metadata and fill missing fields. Repeated sequential imports skip duplicate paintings and genre associations. No database schema changes or fixed primary keys are required. Ambiguous names/titles and concurrent imports require review. Different captures can generate additional image files; older images may still be referenced by the database and must not be removed blindly.

### Backups

Before `--import`, the program backs up **Artists, Paintings, Genres and PaintingGenres** to a uniquely named SQL file in PHP's temporary directory under `artwork-task9-backups`. Files have private permissions and are outside the website/repository. Backup failure stops import; SQL failure rolls back uncommitted changes.

Keep any needed backup in durable private storage because temporary files may be removed by the OS. Restoring a backup replaces those four tables and should be deliberate, in the original database, with other writes stopped. A manual phpMyAdmin import requires your own backup first.

## Verification and presentation evidence

From the project root with `php` configured:

```sh
php scripts/scraping/test.php
php scripts/scraping/test.php --database
```

The first command checks generated results, local images, UTF-8, missing-input diagnostics, safe local paths and detailed metadata merging using clearly marked synthetic fixtures. It requires generated JSON and the saved painting images. The optional database test uses config.php and creates an isolated disposable database, imports twice, checks record relationships and metadata, exercises rollback, then removes the test database. CREATE/DROP DATABASE privileges are required; collection rows are not used as test fixtures.

Executed checks include offline parsing, PHP syntax, duplicate imports, backups, genre relationships, metadata filling/preservation, rollback and authenticated gallery/detail HTTP requests. Browser screenshots and Windows/Linux runtime checks remain pending. Local development tracking notes are excluded from this shared branch.

For a judge demonstration, show the gallery and filters, one painting detail page, the offline preview and terminal import summary. Use actual screenshots of these screens plus database relationships and unchanged counts after a repeat import if required by the course rubric. Optional fields vary with the saved capture; do not present missing source data as extracted facts.

## Module reference

| Area | Main pages | Behavior to demonstrate |
| --- | --- | --- |
| Authentication | art-login.php, authenticate.php, logout.php | Sign in, session redirect, sign out |
| Paintings | art-main.php, art-detail.php, art-add.php, art-delete.php | Filters, catalog details, validated upload, confirmed deletion |
| Artists | artists.php, artist-add.php, artist-delete.php | Painting counts, add, protected delete |
| Galleries | galleries.php, gallery-detail.php, gallery-add.php, gallery-delete.php | Linked collection, add, protected delete |
| Eras | eras.php, era-detail.php, era-add.php, era-update.php, era-delete.php | Name/year search and editable year range |
| Genres | genres.php, genre-detail.php, genre-add.php, genre-delete.php | Catalog, linked paintings, validation and protected delete |
| Painting genres | painting-genres.php, painting-genre-add.php, painting-genre-delete.php | Painting/genre listing; add/delete pages manage genre catalog entries |
| Subjects | subjects.php, subject-detail.php, subject-add.php, subject-delete.php | Catalog and linked paintings |
| Painting subjects | painting-subjects.php, painting-subject-add.php, painting-subject-delete.php | Add/remove individual painting-subject links |

Genre data uses auto-increment GenreID, unique GenreName (1–50 characters), optional Description (form limit 10,000 characters), and optional HTTP/HTTPS Link (255 characters). PaintingGenres associates painting and genre IDs. Used genres cannot be deleted; direct requests repeat that check transactionally. Details validate IDs and distinguish invalid IDs from missing records. Genre forms report invalid or duplicate entries without writing. Subject names are nonempty, at most 255 characters and checked for duplicates; linked subjects cannot be deleted.

All collection pages require authentication. Read-only filters use GET; writes require validated POST and CSRF tokens. Confirmation pages precede deletions. Shared behavior is implemented in includes/app.php, includes/auth.php and the subject helpers.

## Troubleshooting

| Problem | Action |
| --- | --- |
| Database unavailable | Start MySQL; check config.php and ART_DB_* overrides. Avoid reseeding an existing database. |
| Form expired | Use a writable PHP session folder, reload the login form and keep the same hostname. |
| Port 8000 busy | Stop the earlier PHP server with Control+C before starting another. |
| Missing source/image | Read the named item in scraper output; save the rendered page and its local assets again. |
| Subject setup required | Run scripts/setup-subjects.php once against the original subject seed. |
| Changes absent from gallery | Run scraper.php --import, refresh the page and clear active collection filters. |
| Not Found | Use the exact URL without punctuation after .php; check the correct server base URL above. |
