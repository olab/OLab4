<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("olab", "delete")) {
    add_error("Your account does not have the permissions required to use this module.");

    echo display_error();

    application_log("error", "Group [" . $ENTRADA_USER->getActiveGroup() . "] and role [" . $ENTRADA_USER->getActiveRole() . "] do not have access to this module [" . $MODULE . "]");
} else {
    $PAGE_META["title"] = $translate->_("Delete Sandboxes");

    $BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/admin/olab?section=delete", "title" => $translate->_("Delete Sandboxes"));

    if (isset($_POST["delete"]) && is_array($_POST["delete"]) && !empty($_POST["delete"])) {
        /*
         * An empty array of ids to delete.
         */
        $delete_ids = array();

        foreach ($_POST["delete"] as $id) {
            if ($id = (int) $id) {
                $delete_ids[] = $id;
            }
        }

        if ($delete_ids) {
            /*
             * Mark whether or not the user has already confirmed that these should be deleted.
             */
            $confirmed = ((isset($_POST["confirmed"]) && $_POST["confirmed"] == 1) ? 1 : 0);

            if ($confirmed) {
                /*
                 * Using the Models_Sandbox's delete() method, delete the array of ids.
                 */
                $sandbox = new Models_OLab();
                if ($sandbox->delete($delete_ids)) {
                    /*
                     * Adds a success message that will display on the next page.
                     */
                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("The selected sandboxes have been deleted successfully."), "success", $MODULE);

                    /*
                     * Logs the successful deletion of the sandboxes.
                     */
                    application_log("success", "Successfully deleted sandbox IDS [" . implode(", ", $delete_ids) . "].");
                } else {
                    /*
                     * Sets an error message that will show to the user.
                     */
                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("We failed to delete the selected sandboxes. Please try again later."), "error", $MODULE);

                    /*
                     * Logs the error message along with any error returned by the database server.
                     */
                    application_log("error", "Failed to delete the sandbox records. Database said:" . $db->ErrorMsg());
                }

                /*
                 * Redirects the user to the admin page.
                 */
                header("Location: " . ENTRADA_URL . "/admin/olab");
                exit;
            } else {
                ?>

                <h1><?php echo $translate->_("Delete Sandboxes"); ?></h1>

                <?php
                echo display_notice($translate->_("Please confirm that you wish to delete the following sandboxes."));

                $results = Models_OLab::fetchRecords($delete_ids);
                if ($results) {
                    ?>
                    <form action="<?php echo ENTRADA_RELATIVE; ?>/admin/olab?section=delete" method="post">
                        <input type="hidden" name="confirmed" value="1" />
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <td colspan="3">
                                        <button class="btn btn-danger"><?php echo $translate->_("Confirm Removal"); ?></button>
                                    </td>
                                </tr>
                            </tfoot>
                            <tbody>
                            <?php foreach ($results as $result) : ?>
                                <tr id="sandbox-<?php echo $result->getID(); ?>">
                                    <td>
                                        <input type="checkbox" name="delete[]" value="<?php echo $result->getID(); ?>" checked="checked" />
                                    </td>
                                    <td><a href="<?php echo ENTRADA_RELATIVE; ?>/admin/olab?section=edit&amp;id=<?php echo $result->getID(); ?>"><?php echo html_encode($result->getTitle()); ?></a></td>
                                    <td><?php echo html_encode($result->getDescription()); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                    <?php
                }
            }
        } else {
            header("Location: " . ENTRADA_URL . "/admin/olab");
            exit;
        }
    } else {
        header("Location: " . ENTRADA_URL . "/admin/olab");
        exit;
    }
}
