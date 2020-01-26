<?php
class Migrate_2016_06_27_090833_949 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS  `assessment_grade_form_comments` (
        `agfcomment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `gafelement_id` int(11) unsigned DEFAULT NULL,
        `assessment_id` int(11) unsigned DEFAULT NULL,
        `proxy_id` int(12) unsigned DEFAULT NULL,
        `comment` text DEFAULT NULL,
        PRIMARY KEY (`agfcomment_id`),
        KEY `gafelement_id` (`gafelement_id`),
        KEY `assessment_id` (`assessment_id`),
        CONSTRAINT `assessment_grade_form_comments_ibfk_1` FOREIGN KEY (`gafelement_id`) REFERENCES `gradebook_assessment_form_elements` (`gafelement_id`),
        CONSTRAINT `assessment_grade_form_comments_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`assessment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE `assessment_grade_form_comments`
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

        if ($migration->tableExists(DATABASE_NAME, "assessment_grade_form_comments")) {
            return 1;
        }
        
        return 0;
    }
}
