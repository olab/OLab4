<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Organisation: University of British Columbia
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 */

global $translate, $HEAD;

$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/quick-add-resource.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

?>

<div id="quick-add-resource">

    <div id="quick-add-resource-drop-container" class="clearfix">
        <div id="quick-add-resource-drop"></div>

        <div id="quick-add-resource-drop-overlay" class="hide">
            <p id="quick-add-resource-drop-msg"><?php echo $translate->_("Drop the selected file to quickly upload resource."); ?></p>
        </div>
    </div>

    <div class="responsive-modal">
        <div id="quick-add-resource-modal" class="modal modal-lg fade hide">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                        <h4 id="quick_add_resource_modal_title" class="modal-title"><?php echo $translate->_("Quick Add Resource(s)"); ?></h4>
                    </div>
                    <div class="modal-body">
                        <form id="quick-add-resource-form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/events?section=api-quick-add-resource" ?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="event_id" value="<?php echo $this->event_id; ?>" />
                            <input type="hidden" name="is_recurring_event" value="<?php echo $this->is_recurring_event; ?>" />
                            <input type="hidden" name="recurring_event_ids" value="<?php echo htmlentities(json_encode($this->recurring_event_ids)); ?>" />
                            <input type="hidden" name="event_resource_entity_id" value="" />
                            <input type="hidden" name="resource_id" value="" />
                            <?php
                                /* XXX: Passing step values to the wizard because we did not create a suitable API service for
                                 *      uploading a file without any steps and substeps. This tells the API that we are ready to
                                 *      proceed with uploading the file, and not just proceeding to the next step without actually
                                 *      persisting data.
                                 */
                            ?>
                            <input type="hidden" name="resource_step" value="5" />
                            <input type="hidden" name="resource_substep" value="3" />
                            <input type="hidden" name="resource_next_step" value="0" />
                            <input type="hidden" name="resource_previous_step" value="0" />
                            <input type="hidden" name="event_resource_type_value" value="11" />
                            <input type="hidden" name="event_resource_file_view_value" value="view" />
                            <input type="hidden" name="event_resource_required_value" value="no" />
                            <input type="hidden" name="event_resource_timeframe_value" value="none" />
                            <input type="hidden" name="event_resource_release_value" value="no" />
                            <input type="hidden" name="event_resource_release_start_value" value="" />
                            <input type="hidden" name="event_resource_release_start_time_value" value="" />
                            <input type="hidden" name="event_resource_release_finish_value" value="" />
                            <input type="hidden" name="event_resource_release_finish_time_value" value="" />
                            <input type="hidden" name="event_resource_attach_file" value="no" />
                            <input type="hidden" name="upload" value="upload" />
                            <input type="hidden" name="method" value="add" />
                            <div id="quick-add-resource-msgs"></div>
                            <div id="quick-add-resource-control-groups"></div>
                        </form>
                        <div id="quick-add-resource-loading">
                            <img src="<?php echo ENTRADA_URL."/images/loading.gif" ?>" />
                            <p id="quick-add-resource-loading-msg"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="quick-add-resource-close" type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></button>
                        <button id="quick-add-resource-cancel" type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></button>
                        <button id="quick-add-resource-submit" type="button" class="btn btn-primary"><?php echo $translate->_("Save"); ?></button>
                    </div>
                </div>
            </div>
        </div>

</div>
