<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("olab", "update")) {
    add_error("Your account does not have the permissions required to use this module.");

    echo display_error();

    application_log("error", "Group [" . $ENTRADA_USER->getActiveGroup() . "] and role [" . $ENTRADA_USER->getActiveRole() . "] do not have access to this module [" . $MODULE . "]");
} else {
    $PAGE_META["title"] = $translate->_("Edit Sandbox");

    $BREADCRUMB[] = array("title" => $translate->_("Edit Sandbox"));

    if (isset($_GET["id"]) && ($id = clean_input($_GET["id"], "int"))) {
        $PROCESSED = Models_OLab::fetchRowByID($id)->toArray();
    }

    if ($PROCESSED) {
        /*
         * Error checking portion of the edit page.
         */
        switch ($STEP) {
            case 2 :
                /*
                 * Required: title
                 * Input cleaning includes trimming, removing HTML, ensuring field is between 1 and 255 characters.
                 */
                if (isset($_POST["title"]) && ($title = clean_input($_POST["title"], array("trim", "nohtml", "min:1", "max:255")))) {
                    $PROCESSED["title"] = $title;
                } else {
                    $PROCESSED["title"] = "";

                    add_error($translate->_("Please provide a title, which should be between 1 and 255 characters."));
                }

                /*
                 * Not Required: description
                 * Input cleaning includes trimming, removing HTML, and ensuring field is at least 1 character.
                 */
                if (isset($_POST["description"]) && ($description = clean_input($_POST["description"], array("trim", "nohtml", "min:1")))) {
                    $PROCESSED["description"] = $description;
                } else {
                    $PROCESSED["description"] = "";
                }

                if (!has_error()) {
                    /*
                     * Adding a created_date and created_by record for the sandbox table.
                     */
                    $PROCESSED["updated_date"] = time();
                    $PROCESSED["updated_by"] = $ENTRADA_USER->getID();

                    /*
                     * Instantiates a new Models_Sandbox, update the row into the sandbox table, and returns
                     * the new auto-incremented id of this sandbox record.
                     */
                    $sandbox = new Models_OLab($PROCESSED);
                    $record = $sandbox->update();
                    if ($record) {
                        /*
                         * Adds a success message that will display on the next page.
                         */
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("The %s sandbox has been updated successfully."), $title), "success", $MODULE);

                        /*
                         * Logs the successful update of this sandbox.
                         */
                        application_log("success", "Successfully updated sandbox ID [" . $record->getID() . "].");

                        /*
                         * Redirects the user to the admin page.
                         */
                        header("Location: " . ENTRADA_URL . "/admin/olab");
                        exit;
                    } else {
                        /*
                         * Sets an error message that will show to the user.
                         */
                        add_error($translate->_("We failed to update the %s sandbox. Please try again later."));

                        /*
                         * Logs the error message along with any error returned by the database server.
                         */
                        application_log("error", "Failed to update a sandbox record. Database said:" . $db->ErrorMsg());
                    }
                }
                break;
            case 1 :
            default :
                continue;
                break;
        }
        ?>

        <h1><?php echo $translate->_("Edit Sandbox"); ?></h1>

        <?php
        /*
         * Displays any error messages that have been set.
         */
        if (has_error()) {
            echo display_error();
        }

        /*
         * Required options used by the form renderer.
         */
        $options = array(
            "action_url" => ENTRADA_RELATIVE . "/admin/olab?section=edit&id=" . $PROCESSED["id"],
            "cancel_url" => ENTRADA_RELATIVE . "/admin/olab",
        );

        /*
         * Pushes the safely sanitized $PROCESSED array into options, which is passed to the form renderer.
         */
        $options = array_merge($options, $PROCESSED);

        /*
         * Renders the sandbox sidebar View Helper.
         */
        $sandbox_form = new Views_OLab_Form();
        $sandbox_form->render($options);
    } else {
        header("Location: " . ENTRADA_URL . "/admin/olab");
        exit;
    }
}
