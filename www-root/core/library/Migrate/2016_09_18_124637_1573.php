<?php
class Migrate_2016_09_18_124637_1573 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>

        CREATE TABLE `exam_category_result` (
            `result_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` int(11) unsigned NOT NULL,
            `exam_id` int(11) unsigned NOT NULL,
            `objective_id` int(12) NOT NULL,
            `set_id` int(12) NOT NULL,
            `average` decimal(10,2) DEFAULT NULL,
            `min` decimal(10,2) DEFAULT NULL,
            `max` decimal(10,2) DEFAULT NULL,
            `possible_value` decimal(10,2) DEFAULT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`result_id`),
        KEY `exam_cat_fk_1` (`exam_id`),
        KEY `exam_cat_fk_2` (`post_id`),
        CONSTRAINT `exam_cat_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
        CONSTRAINT `exam_cat_fk_2` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_category_result_detail` (
            `detail_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `proxy_id` int(11) NOT NULL,
            `exam_progress_id` int(12) unsigned NOT NULL,
            `post_id` int(11) unsigned NOT NULL,
            `exam_id` int(11) unsigned NOT NULL,
            `objective_id` int(12) NOT NULL,
            `set_id` int(12) NOT NULL,
            `score` decimal(10,2) DEFAULT NULL,
            `value` decimal(10,2) DEFAULT NULL,
            `possible_value` decimal(10,2) DEFAULT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `updated_by` int(12) DEFAULT NULL,
            `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`detail_id`),
        KEY `exam_cat_detail_fk_1` (`exam_id`),
        KEY `exam_cat_detail_fk_2` (`post_id`),
        KEY `exam_cat_detail_fk_3` (`exam_progress_id`),
        CONSTRAINT `exam_cat_detail_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
        CONSTRAINT `exam_cat_detail_fk_2` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`),
        CONSTRAINT `exam_cat_detail_fk_3` FOREIGN KEY (`exam_progress_id`) REFERENCES `exam_progress` (`exam_progress_id`)
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
        DROP TABLE `exam_category_result`;
        DROP TABLE `exam_category_result_detail`;
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
        if ($migration->tableExists(DATABASE_NAME, "exam_category_result")) {
            if ($migration->tableExists(DATABASE_NAME, "exam_category_result_detail")) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
