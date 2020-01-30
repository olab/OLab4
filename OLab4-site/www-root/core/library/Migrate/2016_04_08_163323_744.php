<?php
class Migrate_2016_04_08_163323_744 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `curriculum_lu_tracks` (
        `curriculum_track_id` INT(12) UNSIGNED NOT NULL AUTO_INCREMENT,
        `curriculum_track_name` VARCHAR(60) NOT NULL,
        `curriculum_track_description` TEXT NULL,
        `curriculum_track_url` VARCHAR(255) NULL DEFAULT NULL,
        `curriculum_track_order` INT(1) NOT NULL DEFAULT '0',
        `created_date` BIGINT(64) NOT NULL,
        `created_by` INT(11) NOT NULL,
        `updated_date` BIGINT(64) NULL DEFAULT NULL,
        `updated_by` INT(11) NULL DEFAULT NULL,
        `deleted_date` BIGINT(64) NULL DEFAULT NULL,
        PRIMARY KEY (`curriculum_track_id`)
        )
        COLLATE='utf8_general_ci'
        ENGINE=InnoDB;

        CREATE TABLE `curriculum_lu_track_organisations` (
        `curriculum_track_id` INT(11) NOT NULL,
        `organisation_id` INT(11) NOT NULL,
        PRIMARY KEY (`curriculum_track_id`, `organisation_id`)
        )
        COLLATE='utf8_general_ci'
        ENGINE=InnoDB;

        CREATE TABLE `course_tracks` (
        `curriculum_track_id` INT(11) NOT NULL,
        `course_id` INT(11) NOT NULL,
        `track_mandatory` INT(1) NULL DEFAULT '0',
        PRIMARY KEY (`curriculum_track_id`, `course_id`),
        UNIQUE INDEX `curriculum_track_id` (`curriculum_track_id`, `course_id`)
        )
        COLLATE='utf8_general_ci'
        ENGINE=InnoDB;

        ALTER TABLE `courses`
        ADD COLUMN `course_mandatory` INT(1) NOT NULL DEFAULT '0' AFTER `course_description`;

        ALTER TABLE `course_contacts`
        CHANGE COLUMN `contact_type` `contact_type` VARCHAR(15) NOT NULL DEFAULT 'director' AFTER `proxy_id`;

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
        DROP TABLE `curriculum_lu_tracks`;
        DROP TABLE `curriculum_lu_track_organisations`;
        DROP TABLE `course_tracks`;

        ALTER TABLE `courses`
        DROP COLUMN `course_mandatory`;

        ALTER TABLE `course_contacts`
        CHANGE COLUMN `contact_type` `contact_type` VARCHAR(12) NOT NULL DEFAULT 'director' AFTER `proxy_id`;

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
        $migrate = new Models_Migration();
        if ($migrate->tableExists(DATABASE_NAME, "curriculum_lu_tracks") && $migrate->tableExists(DATABASE_NAME, "curriculum_lu_track_organisations") && $migrate->tableExists(DATABASE_NAME, "course_tracks")) {
            return 1;
        }

        return 0;
    }
}
