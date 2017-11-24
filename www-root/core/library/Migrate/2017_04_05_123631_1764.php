<?php
class Migrate_2017_04_05_123631_1764 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `portfolios_lu_artifacts` (`artifact_id`, `title`, `description`, `handler_object`, `allow_learner_addable`, `order`, `active`, `updated_date`, `updated_by`)
        VALUES
        (1, 'Personal Reflection', '', 'Portfolio_Model_Artifact_Handler_PersonalReflection', 1, 1, 1, NOW(), 0),
        (2, 'Document Attachment', '', 'Portfolio_Model_Artifact_Handler_DocumentAttachment', 1, 2, 1, NOW(), 0);
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
        DELETE FROM `portfolios_lu_artifacts` WHERE `artifact_id` = 1;
        DELETE FROM `portfolios_lu_artifacts` WHERE `artifact_id` = 2;
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
        global $db;

        $query = "SELECT * FROM `portfolios_lu_artifacts`";
        $result = $db->GetRow($query);
        if ($result) {
            return 1;
        }

        return 0;
    }
}
