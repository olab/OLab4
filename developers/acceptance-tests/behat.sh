#!/bin/bash
cd $(dirname "$0")/
touch ../../www-root/core/config/config.inc.php
touch ../../www-root/.htaccess
chmod 777 ../../www-root/core/config/config.inc.php
chmod 777 ../../www-root/.htaccess

../../www-root/core/library/vendor/bin/behat features/setup.feature

docker exec -it entrada-developer php /var/www/vhosts/entrada-1x-me/entrada migrate --quiet --up

../../www-root/core/library/vendor/bin/behat features/users.feature
../../www-root/core/library/vendor/bin/behat features/course.feature
../../www-root/core/library/vendor/bin/behat features/assessment-evaluation.feature
../../www-root/core/library/vendor/bin/behat features/curriculum-tag.feature

# Exam Module
../../www-root/core/library/vendor/bin/behat features/exam-questions.feature
../../www-root/core/library/vendor/bin/behat features/exam-exams.feature


chmod 644 ../../www-root/core/config/config.inc.php
chmod 644 ../../www-root/.htaccess
