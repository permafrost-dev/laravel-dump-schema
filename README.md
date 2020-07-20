### laravel-dump-schema
---
This package Implements a `db:schema` artisan command, used to dump the database schema to or load it from an sql file.

The basis of this code is from Laravel 8.x Pull Request [#32275](https://github.com/laravel/framework/pull/32275), although currently only the MySQL portion has been implemented.

_Note: this code is a work in progress and should not be used in production._

---

#### Usage

You can either dump or load the database schema, avoiding the need to re-run migrations if they haven't changed:
> `php artisan db:schema dump`

> `php artisan db:schema load`

---

Currently, these commands will use the filename `database/schema-dump.sql`.
