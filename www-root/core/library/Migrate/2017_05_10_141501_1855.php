<?php
class Migrate_2017_05_10_141501_1855 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `settings` (`shortname`, `organisation_id`, `value`) VALUES
        ('eportfolio_entry_is_assessable_by_default', NULL, '1'),
        ('eportfolio_entry_is_assessable_set_by_learner', NULL, '1'),
        ('eportfolio_entry_is_assessable_set_by_advisor', NULL, '1'),
        ('eportfolio_can_attach_to_gradebook_assessment', NULL, '1'),
        ('eportfolio_show_comments_in_gradebook_assessment', NULL, '0');
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
        DELETE FROM `settings` WHERE `shortname` IN (
        'eportfolio_entry_is_assessable_by_default',
        'eportfolio_entry_is_assessable_set_by_learner',
        'eportfolio_entry_is_assessable_set_by_advisor',
        'eportfolio_can_attach_to_gradebook_assessment',
        'eportfolio_show_comments_in_gradebook_assessment')
        AND `organisation_id` IS NULL;
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
        $settings = new Entrada_Settings;
        if (($settings->read("eportfolio_entry_is_assessable_by_default") !== false)
            && ($settings->read("eportfolio_entry_is_assessable_set_by_learner") !== false)
            && ($settings->read("eportfolio_entry_is_assessable_set_by_advisor") !== false)
            && ($settings->read("eportfolio_can_attach_to_gradebook_assessment") !== false)
            && ($settings->read("eportfolio_show_comments_in_gradebook_assessment") !== false)) {
            return 1;
        }

        return 0;
    }
}
