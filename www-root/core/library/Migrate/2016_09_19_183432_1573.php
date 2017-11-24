<?php
class Migrate_2016_09_19_183432_1573 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `exam_category` (
            `category_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` int(11) unsigned NOT NULL,
            `exam_id` int(11) unsigned NOT NULL,
            `use_release_start_date` int(1) DEFAULT NULL,
            `use_release_end_date` int(1) DEFAULT NULL,
            `release_start_date` bigint(20) DEFAULT NULL,
            `release_end_date` bigint(20) DEFAULT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `updated_by` int(12) DEFAULT NULL,
            `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`category_id`),
        KEY `exam_cat_fk_3` (`exam_id`),
        KEY `exam_cat_fk_4` (`post_id`),
        CONSTRAINT `exam_cat_fk_3` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
        CONSTRAINT `exam_cat_fk_4` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_category_set` (
            `set_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `objective_set_id` int(11) unsigned NOT NULL,
            `category_id` int(11) unsigned NOT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `updated_by` int(12) DEFAULT NULL,
            `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`set_id`),
        KEY `exam_cat_fk_5` (`category_id`),
        CONSTRAINT `exam_cat_fk_5` FOREIGN KEY (`category_id`) REFERENCES `exam_category` (`category_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_category_audience` (
            `audience_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `category_id` int(11) unsigned NOT NULL,
            `proxy_id` int(11) unsigned NOT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `updated_by` int(12) DEFAULT NULL,
            `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`audience_id`),
        KEY `exam_cat_fk_6` (`category_id`),
        CONSTRAINT `exam_cat_fk_6` FOREIGN KEY (`category_id`) REFERENCES `exam_category` (`category_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DROP TABLE `exam_category_set`;
        DROP TABLE `exam_category_audience`;
        DROP TABLE `exam_category`;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Optional: PHP that verifies whether or not the changes outlined
     * in "up" are present in the active database.
     *
     * Return Values: -1 (not run) | 0 (changes not present or complete) | 1 (present)
     *
     * @return int
     */
    public function audit() {
        $migration = new Models_Migration();

        // Check for new field
        if ($migration->tableExists(DATABASE_NAME, "exam_category")) {
            if ($migration->tableExists(DATABASE_NAME, "exam_category_set")) {
                if ($migration->tableExists(DATABASE_NAME, "exam_category_audience")) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
