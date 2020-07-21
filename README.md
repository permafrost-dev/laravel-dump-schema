### laravel-dump-schema
---
This package Implements a set of `db:schema` artisan commands, used to dump the database schema to or load it from an sql file.

The basis of this code is from Laravel 8.x Pull Request [#32275](https://github.com/laravel/framework/pull/32275), although currently only the MySQL portion has been implemented.

_Note: this code is a work in progress and should not be used in production._

---


#### Installation

You can Install this package with composer:
`composer require permafrost-dev/laravel-dump-schema`

---

#### Usage

You can either dump or load the database schema, avoiding the need to re-run migrations if they haven't changed:
> `php artisan db:schema:dump`

> `php artisan db:schema:load`

---

By default, these commands will use the filename `database/schema.sql` - it can be changed by specifying the `--filename` flag: 
`--filename=foobar.sql`.
