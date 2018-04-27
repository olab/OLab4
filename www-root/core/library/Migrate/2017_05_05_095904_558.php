<?php
class Migrate_2017_05_05_095904_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_lu_methods`
            DROP `title`,
            DROP `description`,
            DROP `instructions`,
            DROP `button_text`;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_method_group_meta` (
            `amethod_meta_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `assessment_method_id` int(11) unsigned NOT NULL,
            `group` char(35) NOT NULL,
            `title` char(255) NOT NULL,
            `description` text,
            `instructions` text,
            `button_text` char(255) NOT NULL,
            `created_date` bigint(64) unsigned NOT NULL,
            `created_by` int(11) unsigned NOT NULL,
            `updated_date` bigint(64) unsigned DEFAULT NULL,
            `updated_by` int(11) unsigned DEFAULT NULL,
            `deleted_date` bigint(64) unsigned DEFAULT NULL,
            PRIMARY KEY (`amethod_meta_id`),
            KEY `amethod_group_id` (`assessment_method_id`),
            KEY `group` (`group`),
            KEY `assessment_method_id` (`assessment_method_id`)
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
        DROP TABLE `cbl_assessment_method_group_meta`;

        ALTER TABLE `cbl_assessment_lu_methods`
            ADD `title` char(255) NOT NULL,
            ADD `description` text,
            ADD `instructions` text,
            ADD `button_text` char(255) NOT NULL;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_method_group_meta")) {
            return 1;
        }

        return 0;
    }
}
