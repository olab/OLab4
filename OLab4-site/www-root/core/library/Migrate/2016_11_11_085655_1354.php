<?php
class Migrate_2016_11_11_085655_1354 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE <?php echo DATABASE_NAME ?>.`cbl_assessments_lu_itemtypes` AS a SET a.`shortname` = 'horizontal_multiple_choice_multiple' WHERE a.`itemtype_id` = '4';
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
        UPDATE <?php echo DATABASE_NAME ?>.`cbl_assessments_lu_itemtypes` AS a SET a.`shortname` = 'horizontal_multile_choice_multiple' WHERE a.`itemtype_id` = '4';
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
        $itemtype_model = new Models_Assessments_Itemtype();
        if ($itemtype = $itemtype_model->fetchRowByID(4)) {
            if ($itemtype->getShortname() == "horizontal_multiple_choice_multiple") {
                return 1;
            }
        }
        return 0;
    }
}
