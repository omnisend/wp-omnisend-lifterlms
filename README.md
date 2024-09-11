# wp-omnisend-lifterlms

Plugin for _LifterLMS_ WordPress plugin. More information can be found [here](https://wordpress.com/plugins/lifterlms).


## PHP Linting

WordPress.org team mandates our plugin to be linted
against [WordPress coding standards](https://github.com/WordPress/WordPress-Coding-Standards).

After each push to any branch `PHP Standards` action will run and all the PHP code will be linted. See action output for results.

### Linting locally

Tools needed:

-   php (7.4 version is recommended because at the time of writing WordPress coding standards supports only up to 7.4 version);
-   composer (can be installed as described in https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos);

After installing those tools one can run in local plugin dir (omnisend-for-contact-form-7) helper script:

```shell
./lint.sh check
./lint.sh fix
```

or all commands manually. Following commands

```shell
composer update
composer install
```

install linting tool and standards. And then actual linting `phpcs` script can be initiated with

```shell
./vendor/squizlabs/php_codesniffer/bin/phpcs --ignore=.js --standard=WordPress omnisend-connect
```

A second `phpcbf` script can be run to automatically correct coding standard violations:

```shell
./vendor/squizlabs/php_codesniffer/bin/phpcbf --ignore=.js --standard=WordPress omnisend-connect