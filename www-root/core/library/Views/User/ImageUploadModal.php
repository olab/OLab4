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
 * View class for rendering a user image uploader modal.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

class Views_User_ImageUploadModal extends Views_HTML {

    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Generate the html for user card
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $action_url = Entrada_Utilities::arrayValueOrDefault($options, "action_url", ENTRADA_URL . "/profile");
        ?>
        <div id="upload-image" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="label"><?php echo $translate->_("Upload Photo"); ?></h3>
            </div>
            <div class="modal-body">
                <div class="preview-img"></div>
                <div class="description alert" style="height:264px;width:483px;padding:20px;">
                    <strong><?php echo $translate->_("To upload a new profile image you can drag and drop it on this area, or use the Browse button to select an image from your computer."); ?></strong>
                </div>
            </div>
            <div class="modal-footer">
                <form name="upload_profile_image_form" id="upload_profile_image_form" action="<?php echo $action_url; ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="coordinates" id="coordinates" value="" />
                    <input type="hidden" name="dimensions" id="dimensions" value="" />
                    <input type="hidden" name="proxy_id" id="proxy_id" value="" />
                    <input type="file" name="image" id="image" />
                </form>
                <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo $translate->_("Cancel"); ?></button>
                <button id="upload-image-button" class="btn btn-primary"><?php echo $translate->_("Upload"); ?></button>
            </div>
        </div>
        <?php
    }

}