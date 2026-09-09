# Homelab Weekly

Shipping repo for Homelab Weekly (newsletter/blog). Product code lives in the `homelabweekly-core` WordPress plugin, analogous to Zoomies Core.

## Layout

```
src/wp-content/plugins/homelabweekly-core/
  homelabweekly-core.php
  includes/
  assets/
.github/workflows/deploy.yml
```

WordPress core, themes, and Hostinger site files are not built or deployed from this repository.

## Deploy

Push to `main`. GitHub Actions rsyncs `homelabweekly-core` into `wp-content/plugins/homelabweekly-core/` on Hostinger.

Secrets (already used by the previous Hostinger deploy):

- `HOSTINGER_HOST`
- `HOSTINGER_USERNAME`
- `HOSTINGER_PRIVATE_KEY`
- `HOSTINGER_PORT`
- `HOMELABWEEKLY_PATH` (WordPress root on the server)

Do not install this plugin from WordPress.org. After rsync, activate it in wp-admin.

## Changelog

Releases are tracked with [Changie](https://github.com/miniscruff/changie). See `CHANGELOG.md`.
