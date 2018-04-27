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
* @author Unit: Faculty of Medicine
* @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
* @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
*
*/

class Views_UI_PickList
{
    public static function render($element_id, $name, $label, array $data, array $values, $get_name)
    {
        if (!is_callable($get_name)) {
            throw new InvalidArgumentException("Expected seventh parameter to be callable");
        }
        ?>
        <div class="control-group">
        <label class="control-label"><?php echo $label; ?></label>
            <div class="controls">
                <div style="width: 50%; float: left;">
                    <select class="multi-picklist" id="<?php echo $element_id; ?>_picklist" name="<?php echo $name; ?>[]" multiple="multiple" size="5" style="margin-bottom: 5px; height: 130px">
                    <?php foreach ($data as $value => $row): ?>
                        <?php if (in_array($value, $values)): ?>
                            <option value="<?php echo (int) $value; ?>"><?php echo html_encode($get_name($row)); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </select>
                </div>
                <div id="<?php echo $element_id; ?>_list" style="float: right; width: 50%; display: none">
                    <select class="multi-picklist" id="<?php echo $element_id; ?>_select_list" multiple="multiple" size="5" style="height: 130px">
                    <?php foreach ($data as $value => $row): ?>
                        <?php if (!in_array($value, $values)): ?>
                            <option value="<?php echo (int) $value; ?>"><?php echo html_encode($get_name($row)); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </select>
                </div>
                <div style="clear: both; float: left; display: inline">
                    <input type="button" id="<?php echo $element_id; ?>_list_state_btn" class="btn" value="Show List" onclick="toggle_list('<?php echo $element_id; ?>_list')" />
                    <input type="button" id="<?php echo $element_id; ?>_list_remove_btn" class="btn" onclick="del_<?php echo $element_id; ?>()" value="Remove" />
                </div>
                <div style="float: right; display: inline">
                    <input type="button" id="<?php echo $element_id; ?>_list_add_btn" class="btn" onclick="add_<?php echo $element_id; ?>()" style="display: none" value="Add" />
                </div>
                <script type="text/javascript">
                function del_<?php echo $element_id; ?>() {
                    moveIt(document.getElementById('<?php echo $element_id; ?>_picklist'), document.getElementById('<?php echo $element_id; ?>_select_list'));
                }
                function add_<?php echo $element_id; ?>() {
                    moveIt(document.getElementById('<?php echo $element_id; ?>_select_list'), document.getElementById('<?php echo $element_id; ?>_picklist'));
                }
                function sel_<?php echo $element_id; ?>() {
                    var pickList = document.getElementById("<?php echo $element_id; ?>_picklist");
                    var pickOptions = pickList.options;
                    var pickOLength = pickOptions.length;

                    for (var i = 0; i < pickOLength; i++) {
                        pickOptions[i].selected = true;
                    }

                    return true;
                }
                $('<?php echo $element_id; ?>_picklist').observe('keypress', function(event) {
                    if (event.keyCode == Event.KEY_DELETE) {
                        del_<?php echo $element_id; ?>();
                    }
                });
                $('<?php echo $element_id; ?>_select_list').observe('keypress', function(event) {
                    if (event.keyCode == Event.KEY_RETURN) {
                        add_<?php echo $element_id; ?>();
                    }
                });
                </script>
            </div>
        </div>
        <?php
    }
}
