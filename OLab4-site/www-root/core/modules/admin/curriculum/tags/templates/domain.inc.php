<?php
    $code = DOMAIN_OBJECTIVE_CODE;
    $parent = Models_Objective::fetchRowByCode($code, 1, $ENTRADA_USER->getActiveOrganisation());
    echo '<h2>Domain</h2>';
    $additionalItem = '';

    if (empty($parent)) {
        echo $translate->_('Error: The Administrator needs to add a root node for this objective.');
        
    } else if ($ENTRADA_ACL->amIAllowed('objectiveattributes', 'update', false)) {

        // 'Admin'
        $additionalItem .= "<p class='form-required" . $errorDomain . "'><em>" . $translate->_('Select one only.') . "</em></p>";
        $additionalItem .= "<div id='" . $code . "'>";

        $objectives = builtDataObjectives($parent->getID());

        // Remembering values when click on save & get errors
        if (!empty($_POST)) {
            $html = "<ul style='list-style-type: none'>";

            foreach ($objectives as $obj) {
                $li = "<li id='" . $obj['objective_id'] . "'>";
                $input = "<input type='checkbox' name='" . $obj['objective_id'] . "' value='" . $parent->getID() . "'> ";
                // $postedDomain_ids is array built in objectives/edit.inc.php
                if (!empty($postedDomain_ids)) {
                    foreach ($postedDomain_ids as $id) {
                        if ($obj["objective_id"] == $id) {
                            $input = "<input checked type='checkbox' name='" . $obj['objective_id'] . "' value='" . $parent->getID() . "'> ";
                        }
                    }
                }
                $li .= $input;
                $li .= formatObjective($obj, $format);
                $html .= $li;
                $html .= "</li>";
            }
            $html .= "</ul>";
            $view = $html;
        } else {
            // Add Screen
            if ($objectiveId == 0) {
                $view = builtObjectivesView($objectives, false, $parent->getID(), $format);
            // Edit Screen
            } else {
                $linkedObjectives = Models_Objective::fetchObjectivesMappedTo($objectiveId, 1, $ENTRADA_USER->getActiveOrganisation());
                $dataLinkedObjectives = buildDataLinkedObjectives($linkedObjectives, $parent->getID(), $format);
                $view = builtObjectivesView($objectives, $dataLinkedObjectives, $parent->getID(), $format);
            }
        }

        $view = $additionalItem . $view;
        $view .= "</div>";
        echo $view;
    } else {
        // 'Leadership' - 'NonAdmin'
        $linkedObjectives = Models_Objective::fetchObjectivesMappedTo($objectiveId, 1, $ENTRADA_USER->getActiveOrganisation());
        $objectives = builtDataObjectives($parent->getID());
        $dataLinkedObjectives = buildDataLinkedObjectives($linkedObjectives, $parent->getID(), $format);

        // Check if No Mapped objectives
        if (empty($dataLinkedObjectives)) {
            $additionalItem .= "<p>" . $translate->_("No") . " " . $code . " " . $translate->_("have been mapped to this objective"). "</p>";
            $view = $additionalItem;
        } else {

            $additionalItem .= "<p>" . $translate->_('This Faculty Objective is linked to the following Domain') . ":</p>";
            $additionalItem .= "<div id='" . $code . "'>";

            $view = buildLinkedObjectivesView($objectives, $code, $dataLinkedObjectives, $format);
            $view = $additionalItem . $view;
            $view .= "</div>";
        }
        echo $view;
    }
?>
