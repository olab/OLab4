<?php
class Migrate_2016_04_28_111323_29 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `tweets` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `last_update` TIMESTAMP NOT NULL,
            `tweets_handle` char(16) NULL,
            `tweets_hashtag` char(100) NULL,
            `tweets` longtext NOT NULL,
        PRIMARY KEY (`id`),
            KEY `tweets_handle` (`tweets_handle`,`tweets_hashtag`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

        ALTER TABLE `<?php echo AUTH_DATABASE ?>`.`organisations`
            ADD  `organisation_twitter` VARCHAR( 16 ) NULL DEFAULT NULL AFTER  `organisation_url` ,
            ADD  `organisation_hashtags` TEXT NULL DEFAULT NULL AFTER  `organisation_twitter`;

        ALTER TABLE  `courses`
            ADD  `course_twitter_handle` VARCHAR( 16 ) NULL DEFAULT NULL ,
            ADD  `course_twitter_hashtags` TEXT NULL DEFAULT NULL;


        ALTER TABLE  `communities`
            ADD  `community_twitter_handle` VARCHAR( 16 ) NULL DEFAULT NULL ,
            ADD  `community_twitter_hashtags` TEXT NULL DEFAULT NULL;

        INSERT INTO `settings` (`setting_id`, `shortname`, `organisation_id`, `value`) VALUES
            (NULL, 'twitter_consumer_key', NULL, ''),
            (NULL, 'twitter_consumer_secret', NULL, ''),
            (NULL, 'twitter_language', NULL, 'en'),
            (NULL, 'twitter_sort_order', NULL, 'recent'),
            (NULL, 'twitter_update_interval', NULL, '5');

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
        DROP TABLE `tweets`;

        ALTER TABLE `<?php echo AUTH_DATABASE ?>`.`organisations`
            DROP  `organisation_twitter`,
            DROP  `organisation_hashtags`;

        ALTER TABLE  `courses`
            DROP  `course_twitter_handle`,
            DROP  `course_twitter_hashtags`;


        ALTER TABLE  `communities`
            DROP  `community_twitter_handle`,
            DROP  `community_twitter_hashtags`;

        DELETE
            FROM `settings`
            WHERE `shortname` IN ('twitter_consumer_key', 'twitter_consumer_secret', 'twitter_language', 'twitter_sort_order', 'twitter_update_interval');
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
    public function audit()
    {
        $migration = new Models_Migration();
        $up2date = true;

        if (!$migration->tableExists(DATABASE_NAME, "tweets")) {
            $up2date = false;
        }

        if (!$migration->columnExists(DATABASE_NAME, "courses", "course_twitter_handle")) {
            $up2date = false;
        }

        if (!$migration->columnExists(DATABASE_NAME, "courses", "course_twitter_hashtags")) {
            $up2date = false;
        }

        if (!$migration->columnExists(DATABASE_NAME, "communities", "community_twitter_handle")) {
            $up2date = false;
        }

        if (!$migration->columnExists(DATABASE_NAME, "communities", "community_twitter_hashtags")) {
            $up2date = false;
        }

        if (!$migration->columnExists(AUTH_DATABASE, "organisations", "organisation_twitter")) {
            $up2date = false;
        }

        if (!$migration->columnExists(AUTH_DATABASE, "organisations", "organisation_hashtags")) {
            $up2date = false;
        }

        if ((Entrada_Settings::fetchValueByShortname("twitter_consumer_key") === false) ||
            (Entrada_Settings::fetchValueByShortname("twitter_consumer_secret") === false) ||
            (Entrada_Settings::fetchValueByShortname("twitter_language") === false) ||
            (Entrada_Settings::fetchValueByShortname("twitter_sort_order") === false) ||
            (Entrada_Settings::fetchValueByShortname("twitter_update_interval") === false)) {
                $up2date = false;
        }

        if ($up2date) {
            return 1;
        }

        return 0;
    }
}
