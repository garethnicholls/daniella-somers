# Daniella Sommers Counselling

Presentation-ready counselling website concept plus an editable WordPress block theme for Daniella Sommers.

The design recreates the calm editorial language of the supplied counselling reference site from scratch: sparse navigation, oversized serif typography, long-form personal copy, portrait-led imagery, generous spacing and simple About / Practice / Fees / Contact sections.

## Fastest way to review it: GitHub Codespaces

1. Open this repository in GitHub.
2. Switch to the `feature/wordpress-site-foundation` branch while PR #1 is still open.
3. Click **Code → Codespaces → Create codespace on feature/wordpress-site-foundation**.
4. Wait for the container to start.
5. Port **8080** is forwarded automatically and should open a browser preview.
6. If it does not open automatically, use the **Ports** tab and click the globe/open-browser icon for port `8080`.

The Codespaces preview serves `demo/index.html` and does not require WordPress, a domain, hosting, a database or any paid service.

## What the showcase includes

- Complete curated counselling copy rather than lorem ipsum
- Editorial mock portrait and interior imagery
- Responsive desktop/mobile layout
- About me, approach, session format, fees and contact journey
- Mock £60 session fee and introductory-call proposition
- Accessible semantic structure and clear navigation
- WordPress-ready equivalent homepage template

## Important demo-content note

The photography, fee, availability, location wording and `hello@daniellasommers.co.uk` email are **concept content for review only**. Confirm Daniella's real qualifications, memberships, fees, availability, address/location, contact details and professional wording before production launch.

Mock visual sources currently used for art direction:
- Portrait: Unsplash, free image by yan kolesnyk (`Xwk7fYSA7cQ`).
- Interior: remote Unsplash image (`photo-1494438639946-1ebd1d20bf85`).

These should ideally be replaced with Daniella's own commissioned photography before production launch.

## WordPress editing

The production theme lives at:

`wp-content/themes/daniella-sommers`

It is a native block theme designed for the WordPress Site Editor, allowing the site owner to edit copy, headings, navigation, imagery, fees, contact details and sections without touching code.

## Full local WordPress development

1. Install Docker Desktop.
2. Run `docker compose up -d`.
3. Open `http://localhost:8080`.
4. Complete WordPress setup.
5. Activate **Daniella Sommers Counselling** under **Appearance → Themes**.
6. Edit through **Appearance → Editor**.

When using Docker WordPress locally, stop the simple Codespaces/static preview first if both are trying to use port 8080.

## Productionising later

Only after the design/content is approved do you need to choose hosting and buy/connect a domain. Before launch, replace the mock assets/details and configure SSL, backups, form spam protection, privacy policy, search metadata, analytics if wanted, and cookie controls only where required by the technologies actually deployed.
