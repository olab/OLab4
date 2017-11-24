<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("olab", "update", false)) {
    add_error("Your account does not have the permissions required to use this module.");

    echo display_error();

    application_log("error", "Group [" . $ENTRADA_USER->getActiveGroup() . "] and role [" . $ENTRADA_USER->getActiveRole() . "] do not have access to this module [" . $MODULE . "]");
} else {
    /*
     * Updates the <title></title> of the page.
     */
    $PAGE_META["title"] = $translate->_("Sandbox Admin Dashboard");

    /*
     * Adds a breadcrumb to the breadcrumb trail.
     */
    $BREADCRUMB[] = array("title" => "Dashboard");
    ?>

    <h1><?php echo $translate->_("Sandbox Admin Dashboard"); ?></h1>

    <?php
    /*
     * Displays any flash messenger entries that exist.
     */
    Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

    /*
     * Checks the Entrada_ACL to ensure this current user can create new sandboxes. If they can then it will
     * display the button.
     */
    if ($ENTRADA_ACL->amIAllowed("olab", "create")) {
        echo "<a class=\"btn btn-success pull-right space-below\" href=\"" . ENTRADA_RELATIVE . "/admin/olab?section=add\"><i class=\"icon-plus-sign icon-white\"></i> " . $translate->_("Add New Sandbox") . "</a>";
    }

    /*
     * Models_Sandbox::fetchAllRecords() will return all non-deleted records from the sandboxes table as an array of objects.
     */
    $results = Models_OLab::fetchAllRecords();
    if ($results) {
        /*
         * Checks the Entrada_ACL to ensure this current user can delete sandboxes.
         */
        $show_delete = ($ENTRADA_ACL->amIAllowed("olab", "delete") ? true : false);
        ?>
        <form action="<?php echo ENTRADA_RELATIVE; ?>/admin/olab?section=delete" method="post">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Title</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <?php if ($show_delete) : ?>
                <tfoot>
                    <tr>
                        <td colspan="3">
                            <button class="btn btn-danger"><?php echo $translate->_("Delete Selected"); ?></button>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
                <tbody>
                <?php foreach ($results as $result) : ?>
                    <tr id="sandbox-<?php echo $result->getID(); ?>">
                        <td>
                            <?php if ($show_delete) : ?>
                            <input type="checkbox" name="delete[]" value="<?php echo $result->getID(); ?>" />
                            <?php else : ?>
                            &nbsp;
                            <?php endif; ?>
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
