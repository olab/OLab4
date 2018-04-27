#!/bin/bash
cd $(dirname "$0")/
rm -rf coverage
../../www-root/core/library/vendor/bin/phpunit --coverage-html coverage -c phpunit.xml
