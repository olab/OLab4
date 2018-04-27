<?php
class Migrate_2016_08_04_124811_1023 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`course_units` MODIFY `unit_description` TEXT NULL;
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
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`course_units` MODIFY `unit_description` VARCHAR(128) NOT NULL DEFAULT '';
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
        $query = "
            SELECT `DATA_TYPE`, `CHARACTER_MAXIMUM_LENGTH`, `IS_NULLABLE`
            FROM `information_schema`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = ?
            AND `TABLE_NAME` = 'course_units'
            AND `COLUMN_NAME` = 'unit_description'";
        $result = $db->GetRow($query, array(DATABASE_NAME));
        if ($result) {
            $data_type = $result["DATA_TYPE"];
            $length = $result["CHARACTER_MAXIMUM_LENGTH"];
            if ($result["IS_NULLABLE"] == "NO") {
                $nullable = false;
            } else if ($result["IS_NULLABLE"] == "YES") {
                $nullable = true;
            } else {
                $nullable = $result["IS_NULLABLE"];
            }
            if (strtoupper($data_type) == "TEXT" && $nullable) {
                return 1;
            } else if (strtoupper($data_type) == "VARCHAR" && $length == 128 && !$nullable) {
                return 0;
            } else {
                return -1;
            }
        } else {
            return 0;
        }
    }
}
