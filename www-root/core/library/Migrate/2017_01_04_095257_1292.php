<?php
class Migrate_2017_01_04_095257_1292 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO <?php echo DATABASE_NAME ?>.`medbiq_resources` (`resource`, `resource_description`, `updated_date`, `updated_by`) VALUES ('Animation', '', 0, 0);
        INSERT INTO <?php echo DATABASE_NAME ?>.`medbiq_resources` (`resource`, `resource_description`, `updated_date`, `updated_by`) VALUES ('Medical Images', '', 0, 0);
        INSERT INTO <?php echo DATABASE_NAME ?>.`medbiq_resources` (`resource`, `resource_description`, `updated_date`, `updated_by`) VALUES ('Mobile Application', '', 0, 0);
        INSERT INTO <?php echo DATABASE_NAME ?>.`medbiq_resources` (`resource`, `resource_description`, `updated_date`, `updated_by`) VALUES ('Scenario', '', 0, 0);
        INSERT INTO <?php echo DATABASE_NAME ?>.`medbiq_resources` (`resource`, `resource_description`, `updated_date`, `updated_by`) VALUES ('Ultrasound', '', 0, 0);
        INSERT INTO <?php echo DATABASE_NAME ?>.`medbiq_resources` (`resource`, `resource_description`, `updated_date`, `updated_by`) VALUES ('Virtual Reality', '', 0, 0);
        UPDATE <?php echo DATABASE_NAME ?>.`medbiq_resources` SET `resource` = 'Clinical Correlation' WHERE `resource` = 'Clinical Cases';
        UPDATE <?php echo DATABASE_NAME ?>.`medbiq_resources` SET `resource` = 'Real Patient' WHERE `resource` = 'Patient – Receiving Clinical Care';
        UPDATE <?php echo DATABASE_NAME ?>.`medbiq_resources` SET `resource` = 'Printed Materials (or Digital Equivalent)' WHERE `resource` = 'Written or Visual Media (or Digital Equivalent)';
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
        DELETE FROM <?php echo DATABASE_NAME ?>.`medbiq_resources` WHERE `resource` = 'Animation';
        DELETE FROM <?php echo DATABASE_NAME ?>.`medbiq_resources` WHERE `resource` = 'Medical Images';
        DELETE FROM <?php echo DATABASE_NAME ?>.`medbiq_resources` WHERE `resource` = 'Mobile Application';
        DELETE FROM <?php echo DATABASE_NAME ?>.`medbiq_resources` WHERE `resource` = 'Scenario';
        DELETE FROM <?php echo DATABASE_NAME ?>.`medbiq_resources` WHERE `resource` = 'Ultrasound';
        DELETE FROM <?php echo DATABASE_NAME ?>.`medbiq_resources` WHERE `resource` = 'Virtual Reality';
        UPDATE <?php echo DATABASE_NAME ?>.`medbiq_resources` SET `resource` = 'Clinical Cases' WHERE `resource` = 'Clinical Correlation';
        UPDATE <?php echo DATABASE_NAME ?>.`medbiq_resources` SET `resource` = 'Patient – Receiving Clinical Care' WHERE `resource` = 'Real Patient';
        UPDATE <?php echo DATABASE_NAME ?>.`medbiq_resources` SET `resource` = 'Written or Visual Media (or Digital Equivalent)' WHERE `resource` = 'Printed Materials (or Digital Equivalent)';
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

        $continue = true;

        $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Animation';";
        $rowresult = $db->GetRow($query);
        $continue = !empty($rowresult);

        if ($continue) {
            $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Medical Images';";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }

        if ($continue) {
            $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Mobile Application';";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }

        if ($continue) {
            $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Scenario';";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }

        if ($continue) {
            $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Ultrasound';";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }

        if ($continue) {
            $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Virtual Reality';";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }

        if ($continue) {
            $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Clinical Correlation';";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }

        if ($continue) {
            $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Real Patient';";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }

        if ($continue) {
            $query = "SELECT * FROM " . DATABASE_NAME . ".`medbiq_resources` WHERE `resource` = 'Printed Materials (or Digital Equivalent)';";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }

        if ($continue) {
            return 1;
        }

        return 0;
    }
}
