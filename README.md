# noerd/customer

Foundation package providing the base customer model, migration, and views. Designed as a building block for appointment, order, or CRM modules. Multi-tenant support included.

## Requirements

- The [`noerd/noerd`](https://github.com/noerd-dev/noerd) package must already be installed and configured.

## Installation

```bash
composer require noerd/customer
php artisan noerd:module customer
```

That's it — the package is auto-discovered via the Laravel service provider declared in `composer.json`.

## Installation as Submodule to contribute

If you want to contribute to the development of `noerd/customer`, you can install it as a git submodule:

```bash
git submodule add git@github.com:noerd-dev/customer.git app-modules/customer
```

Then add a path repository and the package to your `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "app-modules/customer",
        "options": {
            "symlink": true
        }
    }
],
"require": {
    "noerd/customer": "*"
}
```

Then run:

```bash
composer update noerd/customer
php artisan noerd:module customer
```

This way, you can make changes directly in `app-modules/customer` and push them back to the customer repository.
