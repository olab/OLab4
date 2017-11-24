<?php
class Migrate_2017_02_09_145025_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_notifications` CHANGE `assessment_type` `assessment_type` ENUM('assessment','delegation', 'approver') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'assessment';
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
        ALTER TABLE `cbl_assessment_notifications` CHANGE `assessment_type` `assessment_type` ENUM('assessment','delegation') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'assessment';
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

        $meta = $migration->fieldMetadata(DATABASE_NAME, "cbl_assessment_notifications", "assessment_type");
        if (empty($meta)) {
            return 0;
        } else {
            if (!strstr($meta["Type"], "'approver'")) {
                return 0;
            }
        }

        return 1;
    }
}
