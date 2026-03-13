# Bahia Blanca Property Scraper

Daily scraper for houses for sale in Bahia Blanca, Argentina. Generates a static HTML report hosted on GitHub Pages.

**[View the report](https://tomasperezponisio.github.io/scraper/)**

## Filters

- **Neighborhood:** Macrocentro
- **Property type:** Casa (house)
- **Operation:** Venta (sale)
- **Price range:** USD 120,000 – 250,000
- **Min bedrooms:** 3

## How it works

1. Downloads the sitemap from [bahiablancapropiedades.com](https://www.bahiablancapropiedades.com)
2. Filters URLs matching `Casa-en-Venta` in `Macrocentro`
3. Fetches each property detail page and parses: price, bedrooms, bathrooms, area, neighborhood, description, image
4. Saves new properties to a SQLite database, marks removed ones
5. Generates a static HTML report at `docs/index.html`

## Stack

- **PHP 8.4** / **Laravel 11**
- **SQLite** — committed to the repo, persists between runs
- **GitHub Actions** — daily cron at 08:00 Argentina time (11:00 UTC)
- **GitHub Pages** — serves the report from `/docs`

## Local setup

```bash
git clone https://github.com/tomasperezponisio/scraper.git
cd scraper
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Usage

```bash
# Run the scraper
php artisan scrape:run

# Regenerate the report without scraping
php artisan report:generate

# Open the report
open public/report.html
```

## Key files

| File | Description |
|---|---|
| `app/Scrapers/BahiaBlancaPropScraper.php` | Sitemap-based scraper |
| `app/Console/Commands/ScrapeProperties.php` | Artisan command, dedup + removal detection |
| `app/Console/Commands/GenerateReport.php` | HTML report generator |
| `resources/views/report.blade.php` | Report template |
| `resources/views/partials/property-card.blade.php` | Property card component |
| `.github/workflows/scraper.yml` | Daily GitHub Actions workflow |
| `database/database.sqlite` | Persistent SQLite database |
| `docs/index.html` | Generated report (served by GitHub Pages) |

## Automation

The GitHub Actions workflow runs daily at 11:00 UTC (08:00 Argentina). It:

1. Checks out the repo (including the SQLite database)
2. Installs dependencies and runs migrations
3. Runs the scraper
4. Commits the updated database and report back to the repo
5. GitHub Pages auto-deploys the new `docs/index.html`

Can also be triggered manually from the Actions tab.
