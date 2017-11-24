#!/bin/bash
cd $(dirname "$0")/
if [[ ! -d coverage ]]; then
    mkdir coverage
fi
../../www-root/core/library/vendor/bin/phpunit --coverage-html coverage -c phpunit.xml
