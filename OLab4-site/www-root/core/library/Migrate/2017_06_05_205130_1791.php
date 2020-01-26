<?php
class Migrate_2017_06_05_205130_1791 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $this->record();
        ?>
        INSERT INTO `<?php echo DATABASE_NAME; ?>`.`exam_bank_folders`
        (`parent_folder_id`, `folder_title`, `folder_description`, `folder_order`, `image_id`, `folder_type`, `organisation_id`, `created_date`, `created_by`, `updated_date`, `updated_by`)
        VALUES ('0', 'Default Folder', 'Folder create automatically', '0', '3', 'exam', '1', '<?php echo time(); ?>', '1', '<?php echo time(); ?>', '1');
        <?php
        $this->stop();
        if ($this->run()) {
            $query = " SELECT * FROM `" . DATABASE_NAME . "`.`exam_bank_folders`
                    WHERE `folder_type` = \"exam\"";
            $folders = $db->GetAll($query);
            $folder_id = 0;
            if ($folders) {
                $folder_id = $folders[0]["folder_id"];
            }
        }

        $this->record();
        ?>

        INSERT INTO `<?php echo DATABASE_NAME; ?>`.`exam_bank_folder_organisations`
        (`folder_id`, `organisation_id`, `updated_date`, `updated_by`)
        VALUES ('<?php echo $folder_id; ?>', 1, <?php echo time(); ?>, 1);

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
        DELETE FROM `<?php echo DATABASE_NAME; ?>`.`exam_bank_folders`
        WHERE `parent_folder_id` = '0'
        AND `folder_title` = 'Default Folder'
        AND `folder_type` = 'exam';
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
        $query = " SELECT * FROM `" . DATABASE_NAME . "`.`exam_bank_folders`
                    WHERE `folder_type` = \"exam\"";
        $folders = $db->GetAll($query);
        if ($folders) {
            return 1;
        }
        return 0;
    }
}
