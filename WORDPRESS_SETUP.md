# Daniella Somers WordPress setup

This repository keeps the existing GitHub Pages demo unchanged. The WordPress theme lives at:

`wp-content/themes/daniella-somers/`

## Current staging plan

Use a temporary **InstaWP** WordPress site to test the theme before choosing permanent hosting. The GitHub Pages demo remains live and independent while staging is tested.

### 1. Create the temporary WordPress site

1. Sign in to InstaWP.
2. Go to **Sites** and choose **+ New Site / Add Site**.
3. Choose **From Scratch**.
4. Use the current stable WordPress version.
5. Keep the temporary InstaWP domain rather than connecting a production domain.
6. Do not add unnecessary plugins during initial testing.
7. Create the site.
8. Use **Magic Login** to enter WordPress Admin. Do not copy WordPress passwords into GitHub, issues, commits or documentation.

Temporary/free InstaWP sites may expire, so treat this environment as disposable staging rather than the production site.

### 2. Download the installable theme ZIP from GitHub

The repository includes a GitHub Actions workflow called **Package WordPress Theme**.

1. In GitHub open **Actions**.
2. Choose **Package WordPress Theme**.
3. Click **Run workflow** and run it from `main`.
4. Open the completed workflow run.
5. Download the `daniella-somers-theme` artifact.
6. Unzip the downloaded artifact once if necessary. The WordPress upload file should contain the `daniella-somers` theme folder with `style.css`, `theme.json`, `templates/` and `parts/` at the correct level.

Alternatively, copy only `wp-content/themes/daniella-somers/` locally and ZIP that folder as `daniella-somers.zip`.

Do **not** ZIP or upload the whole GitHub repository as a WordPress theme.

### 3. Install the theme in InstaWP

Inside the temporary WordPress Admin:

1. Go to **Appearance → Themes**.
2. Select **Add New Theme** / **Add New**.
3. Choose **Upload Theme**.
4. Select `daniella-somers.zip`.
5. Click **Install Now**.
6. Click **Activate**.

The static GitHub Pages demo is not changed by activating the WordPress theme.

### 4. Configure the staging site

1. Go to **Settings → General** and set the site title to `Daniella Somers`.
2. Go to **Appearance → Editor** and inspect the front-page template.
3. Confirm the following sections are present and editable:
   - hero
   - portrait
   - Daniella's journey
   - how I practice
   - qualifications and training
   - sessions and fees
   - contact details
   - navigation
   - footer
4. Replace the portrait placeholder with Daniella's supplied portrait using the WordPress **Media Library**.
5. Confirm the factual content against the supplied source material, including:
   - free 15-minute phone consultation
   - £55 per 50-minute session
   - online and in-person sessions
   - Mirfield and Leeds area
   - `somerd26@gmail.com`
   - `07803 842151`
   - BACP registered member / MBACP
6. Check desktop, tablet and mobile previews in the Site Editor.
7. Open the staging URL on a real phone as well as desktop before approving it.

### 5. What to test before keeping or promoting the site

- Main design closely matches the approved GitHub Pages concept.
- Daniella can edit text without touching code.
- Daniella can replace images through the Media Library.
- Navigation works on desktop and mobile.
- Phone and email links work.
- No Option B or Option C content appears in the WordPress production theme.
- No credentials or environment-specific values appear in page source or the GitHub repository.
- No unnecessary analytics, advertising or tracking plugins are installed during staging.
- Contact enquiries are not stored until a deliberate privacy-conscious form solution is selected.

## What Daniella can edit

The Site Editor exposes the hero, portrait, journey, practice sections, qualifications, fees, contact details, navigation and footer as normal WordPress blocks. The theme constrains the brand palette and layout so routine edits do not require code changes.

Recommended account split when moving beyond temporary staging:

- Daniella: **Editor** for day-to-day content changes.
- Site owner/maintainer: **Administrator** for plugins, themes, security and configuration.

For a disposable InstaWP staging site, it is fine to use the generated administrator account while testing. Create Daniella's permanent account only on the long-lived staging/production installation.

## Contact forms and privacy

The theme intentionally does not store form submissions. For launch, use a reputable form plugin configured for minimal data retention and spam protection. Counselling enquiries may contain sensitive information, so do not retain submissions in WordPress unless there is a clear operational and privacy reason.

Do not add a contact-form plugin merely to prove the visual design works. First validate the theme and editing experience; configure production email delivery later.

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

The temporary InstaWP username/password belongs only in InstaWP or your password manager. It should never be placed in this repository.

If deployment is later automated through GitHub Actions, store deployment values in **Repository Settings → Secrets and variables → Actions** and reference them as `${{ secrets.NAME }}`.

## Deployment model

For now:

`GitHub Pages demo → remains unchanged`

`GitHub main branch → source-controlled WordPress theme`

`InstaWP temporary site → disposable staging and editability testing`

Later, once a permanent host is chosen:

`GitHub theme → permanent staging → tested → production WordPress`

GitHub should contain the custom theme and documentation only. WordPress core, the database, Media Library uploads and runtime configuration belong on the hosting platform.

The existing static demo pages at `/`, `/option-b/` and `/option-c/` are independent of this theme and should remain available until the WordPress site is approved and launched.
