<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 */

class Views_OLab_Form extends Views_HTML {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url", "cancel_url", "title", "description"));
    }

    protected function renderView($options = array()) {
        global $translate;

        /*
         * $options["action_url"] is specified as a required in the validateOptions() method
         * defined above. We can safely use it here.
         */
        $action_url = $options["action_url"];
        $cancel_url = $options["cancel_url"];

        $title = $options["title"];
        $description = $options["description"];
        ?>
        <form class="form-horizontal" action="<?php echo $action_url ?>" method="POST">
            <input type="hidden" name="step" value="2" />
            <div class="control-group">
                <label class="control-label form-required" for="sandbox-title"><?php echo $translate->_("Sandbox Title"); ?></label>
                <div class="controls">
                    <input type="text" class="input-xxlarge" name="title" id="sandbox-title" value="<?php echo html_encode($title); ?>" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label form-nrequired" for="sandbox-description"><?php echo $translate->_("OLab Description"); ?></label>
                <div class="controls">
                    <textarea class="input-xxlarge expandable" name="description" id="sandbox-description"><?php echo html_encode($description); ?></textarea>
                </div>
            </div>
            <div class="row-fluid">
                <a href="<?php echo $cancel_url; ?>" class="btn btn-default pull-left"><?php echo $translate->_("Cancel"); ?></a>
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Submit"); ?>" />
            </div>
        </form>
        <?php
    }
}
