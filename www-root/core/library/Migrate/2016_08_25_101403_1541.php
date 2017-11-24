<?php
class Migrate_2016_08_25_101403_1541 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $post_query = "
            SELECT `posts`.`post_id`, `posts`.`target_id`
            FROM `exam_posts` as `posts`
            WHERE `posts`.`target_type` = 'event'

            AND `posts`.`target_id` NOT IN (
            	SELECT `entities`.`event_id`
            	FROM `event_resource_entities` as `entities`
            	WHERE `entities`.`entity_type` = '12'
            )
            ";
        $posts = $db->GetAll($post_query);

        if ($posts && is_array($posts) && !empty($posts)) {
            foreach ($posts as $post) {
                $this->record();
                ?>
                UPDATE `event_resource_entities`
                SET `event_id` = '<?php echo $post["target_id"];?>'
                WHERE `entity_value` = '<?php echo $post["post_id"];?>'
                AND `entity_type` = '12';
                <?php

                $this->stop();
            }
        }
        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        -- SQL Downgrade Queries Here;
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

        $post_query = "
            SELECT `posts`.`post_id`, `posts`.`target_id`
            FROM `exam_posts` as `posts`
            WHERE `posts`.`target_type` = 'event'

            AND `posts`.`target_id` NOT IN (
            	SELECT `entities`.`event_id`
            	FROM `event_resource_entities` as `entities`
            	WHERE `entities`.`entity_type` = '12'
            )
            ";
        $posts = $db->GetAll($post_query);
        if ($posts) {
            return 0;
        }
        return 1;

    }
}
