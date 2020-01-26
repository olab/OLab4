<?php
class Migrate_2016_07_18_163204_175 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        global $db;
        $post_query = "
            SELECT *
            FROM `exam_posts`
            WHERE `target_type` = 'event'";
        $posts = $db->GetAll($post_query);
        if ($posts && is_array($posts) && !empty($posts)) {
            foreach ($posts as $post) {
                ?>
                INSERT INTO `event_resource_entities` (`event_id`, `entity_type`, `entity_value`, `release_date`, `release_until`, `updated_date`, `active`)
                Values ('<?php echo $post["target_id"];?>', 12,'<?php echo $post["post_id"];?>','<?php echo $post["start_date"];?>','<?php echo $post["release_end_date"];?>','<?php echo $post["updated_date"];?>', 1);
                <?php
            }
        }
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DELETE FROM `event_resource_entities` WHERE `entity_type` = 12;
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
            SELECT *
            FROM `exam_posts`
            WHERE `target_type` = 'event'";

        $posts = $db->GetAll($post_query);
        if ($posts && is_array($posts) && !empty($posts)) {
            $event_resource = Models_Event_Resource_Entity::fetchAllByEntityType("Exam");
            if ($event_resource && is_array($event_resource) && !empty($event_resource)) {
                return 1;
            }
        } else {
            return 1;
        }

        return 0;
    }
}
