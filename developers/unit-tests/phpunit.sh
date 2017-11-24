#!/bin/bash
cd $(dirname "$0")/
../../www-root/core/library/vendor/bin/phpunit -c phpunit.xml
