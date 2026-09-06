# Daniella Sommers Counselling

Presentation-ready counselling website concept plus an editable WordPress block theme for Daniella Sommers.

The design recreates the calm editorial language of the supplied counselling reference site from scratch: sparse navigation, oversized serif typography, long-form personal copy, portrait-led imagery, generous spacing and simple About / Practice / Fees / Contact sections.

## Review it in GitHub Codespaces

1. Open this repository in GitHub.
2. Create a Codespace from `main` once this PR is merged.
3. The devcontainer starts a static preview server on port `8080`.
4. Open the **Ports** tab if the site does not open automatically.
5. Port `8080` is configured as **Public**.
6. Copy the forwarded-port URL and send that URL to Daniella.

Daniella does **not** need a GitHub account to view the public forwarded-port link. The repository itself can stay private.

Important: a Codespaces forwarded-port URL is a temporary review environment, not permanent hosting. The Codespace must be running for the link to work. It is ideal for design/content approval before paying for hosting or a domain.

If GitHub overrides the requested visibility, use the Ports tab, right-click port `8080`, choose **Port Visibility → Public**, then copy the public URL.

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
