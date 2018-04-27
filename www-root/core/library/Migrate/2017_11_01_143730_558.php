<?php
class Migrate_2017_11_01_143730_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {

        $this->record();

        ?>
        ALTER TABLE `global_lu_likelihoods` ADD `shortname` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '' AFTER `title`;
        <?php
        $this->stop();
        if ($status = $this->run()) {
            $likelihood_model = new Models_Likelihood();
            $likelihoods = $likelihood_model->fetchAllRecords();
            if ($likelihoods) {
                foreach ($likelihoods as $likelihood) {
                    $shortname = clean_input($likelihood->getTitle(), array("lower", "trim", "notags", "underscores"));
                    $likelihood->setShortName($shortname);
                    $likelihood->update();
                }
            }
        }
        return $status;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        ALTER TABLE `global_lu_likelihoods` DROP `shortname`;
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
        if ($migration->columnExists(DATABASE_NAME, "global_lu_likelihoods", "shortname")) {
            return 1;
        }
        return 0;
    }
}
