# Daniella Somers Counselling

The approved design is previewed at [garethnicholls.github.io/daniella-somers](https://garethnicholls.github.io/daniella-somers/) and deployed through GitHub Pages with `daniellasomerscounselling.co.uk` as its custom domain.

The same design is supplied as the installable **Daniella Somers Counselling** WordPress block theme in `wp-content/themes/daniella-somers/`.

## Authoritative layout

- `index.html` is the exact visual reference published by GitHub Pages.
- `wp-content/themes/daniella-somers/content/home-page.html` contains the equivalent native WordPress blocks.
- `wp-content/themes/daniella-somers/style.css` is the theme’s only stylesheet and is loaded in both the public site and block editor.
- `templates/front-page.html` renders the selected WordPress front page at full width without adding another layout layer.

Keeping a single theme stylesheet prevents `exact.css`, editor CSS, gutter CSS and media CSS from overriding one another. The responsive browser tests verify the same geometry in the public WordPress view and native editor.

## GitHub Pages

Every push to `main` runs `.github/workflows/pages.yml`. The published artifact contains only:

- `index.html`
- `assets/daniella-hero.jpg`
- `CNAME`
- `.nojekyll`

## WordPress theme package

Every theme change on `main` runs `.github/workflows/package-wordpress-theme.yml`, which produces `daniella-somers.zip` as a downloadable workflow artifact.

To preview locally, run:

```sh
python3 -m http.server 8080
```

Then open `http://localhost:8080/`.
