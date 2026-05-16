# noerd/customer

Foundation package providing the base customer model, migration, and views. Designed as a building block for appointment, order, or CRM modules. Multi-tenant support included.

The noerd package is required. Make sure the project is already initialized as a Git repository.
```
composer require noerd/noerd
php artisan noerd:install
```

Install the package. Make sure you already initiated a git project.
```
git submodule add git@github.com:noerd-dev/customer.git app-modules/customer
php artisan noerd:module customer
composer update noerd/customer
```
