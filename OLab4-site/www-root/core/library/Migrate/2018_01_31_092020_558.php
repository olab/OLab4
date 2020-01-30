<?php
class Migrate_2018_01_31_092020_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $form_type_model = new Models_Assessments_Form_Type();
        $ppa_form = $form_type_model->fetchRowByShortname("cbme_ppa_form");
        $rubric_form = $form_type_model->fetchRowByShortname("cbme_rubric");
        $rubric_type_id = $rubric_form->getID();
        $ppa_type_id = $ppa_form->getID();
        $this->record();
        ?>
        INSERT INTO `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`)
        VALUES
        (NULL, <?php echo $ppa_type_id ?>, 'item', 'cbme_ppa_feedback', 'Have feedback about this form? (eg, \"Missing Dx\", etc.)', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo $ppa_type_id ?>, 'rubric', 'cbme_ppa_concerns', 'Concerns', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo $ppa_type_id ?>, 'item', 'cbme_ppa_concerns_item_1', 'Do you have concerns regarding this resident\'s professionalism?', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo $ppa_type_id ?>, 'item', 'cbme_ppa_concerns_item_2', 'Do you have patient safety concerns related to this resident\'s performance?', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo $ppa_type_id ?>, 'item', 'cbme_ppa_concerns_item_3', 'Are there other reasons to flag this assessment?', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo  $rubric_type_id ?>, 'item', 'cbme_rubric_feedback', 'Have feedback about this form? (eg, \"Missing Dx\", etc.)', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo  $rubric_type_id ?>, 'rubric', 'cbme_rubric_concerns', 'Concerns', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo  $rubric_type_id ?>, 'item', 'cbme_rubric_concerns_item_1', 'Do you have concerns regarding this resident\'s professionalism?', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo  $rubric_type_id ?>, 'item', 'cbme_rubric_concerns_item_2', 'Do you have patient safety concerns related to this resident\'s performance?', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (NULL, <?php echo  $rubric_type_id ?>, 'item', 'cbme_rubric_concerns_item_3', 'Are there other reasons to flag this assessment?', '', 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL);
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
        DELETE FROM `cbl_assessments_lu_item_groups`
        WHERE `shortname` = 'cbme_ppa_feedback'
        OR `shortname` = 'cbme_ppa_concerns'
        OR `shortname` = 'cbme_ppa_concerns_item_1'
        OR `shortname` = 'cbme_ppa_concerns_item_2'
        OR `shortname` = 'cbme_ppa_concerns_item_3'
        OR `shortname` = 'cbme_rubric_concerns'
        OR `shortname` = 'cbme_rubric_feedback'
        OR `shortname` = 'cbme_rubric_concerns_item_1'
        OR `shortname` = 'cbme_rubric_concerns_item_2'
        OR `shortname` = 'cbme_rubric_concerns_item_3';
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

        $query = "SELECT *
                  FROM `cbl_assessments_lu_item_groups`
                  WHERE `shortname` = 'cbme_ppa_feedback'
                  OR `shortname` = 'cbme_ppa_concerns'
                  OR `shortname` = 'cbme_ppa_concerns_item_1'
                  OR `shortname` = 'cbme_ppa_concerns_item_2'
                  OR `shortname` = 'cbme_ppa_concerns_item_3'
                  OR `shortname` = 'cbme_rubric_concerns'
                  OR `shortname` = 'cbme_rubric_feedback'
                  OR `shortname` = 'cbme_rubric_concerns_item_1'
                  OR `shortname` = 'cbme_rubric_concerns_item_2'
                  OR `shortname` = 'cbme_rubric_concerns_item_3'";
        $results = $db->GetAll($query);
        if ($results) {
            return 1;
        }
        return 0;
    }
}
