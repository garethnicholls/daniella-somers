# Daniella Somers Counselling

The production website for [daniellasomerscounselling.co.uk](https://daniellasomerscounselling.co.uk/).

The site is one self-contained, responsive front page published by GitHub Pages. [`index.html`](index.html) is the authoritative source; there is no WordPress theme, Customizer layer, alternative design, or runtime build that can override it.

## Publishing

Every push to `main` runs [the Pages workflow](.github/workflows/pages.yml). It packages only:

- `index.html`
- `assets/daniella-hero.jpg`
- `CNAME`
- `.nojekyll`

GitHub Pages then serves the artifact at the custom domain. Changes should be made through a pull request and merged only after the production front-page check passes.

## Local preview

Run:

```sh
python3 -m http.server 8080
```

Then open `http://localhost:8080/`.

In GitHub Codespaces the devcontainer starts the same static preview automatically and forwards port `8080`.

## Content ownership

All public copy, layout, responsive styles, navigation and contact behaviour live in `index.html`. The portrait lives at `assets/daniella-hero.jpg`. This keeps the reviewed front page identical to the Pages deployment and prevents theme or editor customizations from changing production unexpectedly.
