# Daniella Somers WordPress setup

This repository keeps the existing GitHub Pages demo unchanged. The production WordPress theme lives at:

`wp-content/themes/daniella-somers/`

## Install

1. Create a managed WordPress site with HTTPS, backups and staging.
2. Copy only `wp-content/themes/daniella-somers/` to the WordPress installation.
3. In WordPress go to **Appearance → Themes** and activate **Daniella Somers Counselling**.
4. Go to **Appearance → Editor** and open the front page template.
5. Replace the portrait placeholder with an Image block and upload Daniella's portrait to the Media Library.
6. Review all copy, fees, phone number, email, BACP profile and locations before launch.
7. Set the WordPress site title to `Daniella Somers`.

## What Daniella can edit

The Site Editor exposes the hero, portrait, journey, practice sections, qualifications, fees, contact details, navigation and footer as normal WordPress blocks. The theme constrains the brand palette and layout so routine edits do not require code changes.

Recommended account split:
- Daniella: **Editor** for day-to-day content changes.
- Site owner/maintainer: **Administrator** for plugins, themes, security and configuration.

## Contact forms and privacy

The theme intentionally does not store form submissions. For launch, use a reputable form plugin configured for minimal data retention and spam protection. Counselling enquiries may contain sensitive information, so do not retain submissions in WordPress unless there is a clear operational and privacy reason.

## Secrets and credentials

Never commit any real credentials or keys to GitHub. In particular do not commit:

- `wp-config.php`
- `.env` / `.env.*`
- database credentials
- WordPress passwords
- SMTP/API secrets
- SFTP/SSH credentials
- private keys/certificates
- backup archives containing database or configuration data

If deployment is automated through GitHub Actions, store deployment values in **Repository Settings → Secrets and variables → Actions** and reference them as `${{ secrets.NAME }}`.

## Deployment model

GitHub should contain the custom theme and documentation only. WordPress core, the database, Media Library uploads and runtime configuration belong on the hosting platform.

The existing static demo pages at `/`, `/option-b/` and `/option-c/` are independent of this theme and should remain available until the WordPress site is approved and launched.
