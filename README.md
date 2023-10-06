# PhpCollective Code Sniffer
[![CI](https://github.com/php-collective/code-sniffer/workflows/CI/badge.svg)](https://github.com/php-collective/code-sniffer/actions?query=workflow%3ACI+branch%3Amaster)
[![Latest Stable Version](https://poser.pugx.org/php-collective/code-sniffer/v/stable.svg)](https://packagist.org/packages/php-collective/code-sniffer)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://poser.pugx.org/php-collective/code-sniffer/license.svg)](https://packagist.org/packages/php-collective/code-sniffer)
[![Total Downloads](https://poser.pugx.org/php-collective/code-sniffer/d/total.svg)](https://packagist.org/packages/php-collective/code-sniffer)

This sniffer package follows [PSR-2](http://www.php-fig.org/psr/psr-2/) completely and ships with a lot of additional fixers on top (incl. PSR-12).
Please see the PhpCollective Coding conventions for details.

[List of included sniffs.](docs/sniffs.md)

## Documentation
See **[docs](docs/README.md)**.

Upstream docs: [squizlabs/PHP_CodeSniffer/wiki](https://github.com/squizlabs/PHP_CodeSniffer/wiki)

## Usage

### How to use in projects
Make sure you include the sniffer as `require-dev` dependency:
```
composer require --dev php-collective/code-sniffer
```

The Development module provides a convenience command:
```
console code:sniff:style
```
(or `console c:s:s` as shortcut)

To automatically fix fixable errors, use
```
console code:sniff:style -f
```

`-v` is useful for more info output.
To run only a specific sniff, use the `-s` option. See `-h` for help.

You can also sniff a specific project level module or path:
```
console code:sniff:style [-m ModuleName] [optional-sub-path] -v
```

### How to use in any project
You can also manually invoke the phpcs/phpcbf commands:
```
vendor/bin/phpcs --standard=vendor/php-collective/code-sniffer/PhpCollective/ruleset.xml ./
vendor/bin/phpcbf --standard=vendor/php-collective/code-sniffer/PhpCollective/ruleset.xml ./
```
The command `phpcs` just sniffs, `phpcbf` fixes.

You probably want to ignore some folders, e.g. `--ignore=vendor/` or some of your test fixture folders.

### Standards
You can always switch the standard to the stricter one named `PhpCollectiveStrict`.
It is an extension of the `PhpCollective` standard with its own (strict) sniffs added on top.

### How to include in your IDE
E.g. for PHPStorm:
* Open Settings -> Tools -> External Tools
* Add a new tool named "cs-sniffer" and set Program to `$ProjectFileDir$/vendor/bin/phpcs`, Parameters to `--standard=$ProjectFileDir$/vendor/php-collective/code-sniffer/PhpCollective/ruleset.xml -p $FilePath$` and Working directory to `$ProjectFileDir$`.
* Add a new tool named "cs-fixer" and set Program to `$ProjectFileDir$/vendor/bin/phpcbf`, Parameters to `--standard=$ProjectFileDir$/vendor/php-collective/code-sniffer/PhpCollective/ruleset.xml -v $FilePath$` and Working directory to `$ProjectFileDir$`.
* Remove the "Open console" if you don't want to see any output here for the fixer.
* Now set up your hotkeys under Settings -> Keymap (search for cs-sniffer and cs-fixer). E.g. `Control + Comma` for sniffing, and `Control + Dot` for fixing.

You can also set up file watchers, but here you should better only whitelist certain sniffs that only add things and don't remove anything.

### How to configure the default rule set

In order to simplify command line interface, `phpcs` allows to specify [default rule set](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Configuration-Options#setting-the-default-coding-standard) in and [standards path](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Configuration-Options#setting-the-installed-standard-paths) the following way.

Assuming the following directory structure:

```
vendor/php-collective/code-sniffer/                          # Base directory
                           |_ PhpCollective/                # Rule set name
                                      |_ ruleset.xml  # Rule set
```

The base directory and rule set can be used in configuration now.

```
vendor/bin/phpcs --config-set installed_paths vendor/php-collective/code-sniffer/
vendor/bin/phpcs --config-set default_standard PhpCollective
```

You might need to specify full directory path. Now the tools can be used without `--standard` switch.

## Using own project standard
You can exchange or extend the PhpCollective coding standard by providing your own ruleset.xml.
This can be configured in the Development module config:

```php
// DevelopmentConfig.php

    /**
     * Either a relative or full path to the ruleset.xml or a name of an installed
     * standard (see `phpcs -i` for a list of available ones).
     *
     * @return string
     */
    public function getCodingStandard()
    {
        return '/path/to/your/ruleset.xml';
    }
```
If you use it for custom projects, just use `--standard` to point to your ruleset file.

Make sure that you include the PhpCollective core standard ruleset in your custom one, e.g.:
```xml
<?xml version="1.0"?>
<ruleset name="PhpCollectiveProject">
    <description>
        PhpCollective Coding Standard for Project.
        Extends main PhpCollective Coding Standard.
        All sniffs in ./Sniffs/ will be auto loaded
    </description>

    <rule ref="vendor/php-collective/code-sniffer/PhpCollective/ruleset.xml"/>

    <!-- Define your own sniffs here -->
</ruleset>
```
If you want to use the `PhpCollectiveStrict` standard in your project, you should replace the string:
```xml
<rule ref="vendor/php-collective/code-sniffer/PhpCollective/ruleset.xml"/>
```
with this one:
```xml
<rule ref="vendor/php-collective/code-sniffer/PhpCollectiveStrict/ruleset.xml"/>
```
