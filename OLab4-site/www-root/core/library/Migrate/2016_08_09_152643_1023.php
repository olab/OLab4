<?php
class Migrate_2016_08_09_152643_1023 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `course_units` ENGINE = MyISAM;
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
        ALTER TABLE `course_units` ENGINE = InnoDB;
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
        $query = "SELECT `ENGINE` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?";
        $engine = $db->GetOne($query, array(DATABASE_NAME, "course_units"));
        if ($engine == "MyISAM") {
            return 1;
        } else if ($engine == "InnoDB") {
            return 0;
        } else {
            return -1;
        }
    }
}
