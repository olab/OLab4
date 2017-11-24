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
 * View class for assessment form form information edit contorls.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Sections_FieldNoteResponses extends Views_Assessments_Forms_Controls_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("response_descriptors"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $response_descriptors = $options["response_descriptors"];
        $field_note_responses = @$options["field_note_responses"];
        $field_note_ardescriptor_id = @$options["field_note_ardescriptor_id"];
        $field_note_flag_response = @$options["field_note_flag_response"];
        $objective_name = @$options["objective_name"];
        ?>
        <div id="field-note-response" class="hide">
            <h2><?php echo $translate->_("Item Responses"); ?></h2>
            <?php /*
            <div class="btn-group space-below pull-right" id="field-note-response-grid-controls">
                <a href="#" class="btn add-field-note-response"><i class="icon-plus-sign"></i></a>
                <a href="#" class="btn remove-field-note-response"><i class="icon-minus-sign"></i></a>
            </div>*/ ?>
            <div class="clearfix"></div>
            <div id="field-note-objective">
                <div class="control-group">
                    <label for="field-note-objective-btn" class="control-label form-required"><?php echo $translate->_("Curriculum Tag"); ?></label>
                    <div class="controls">
                        <button id="field-note-objective-btn" class="btn" href="#"><?php echo $objective_name ? html_encode($objective_name) : $translate->_("Select Curriculum Tag"); ?></button>
                    </div>
                </div>
            </div>
            <?php if (isset($field_note_responses) && is_array($field_note_responses)): ?>
                <?php foreach ($field_note_responses as $key => $response): ?>
                    <div id="field-note-<?php echo html_encode($key); ?>" class="field-note-response">
                        <?php if ($response_descriptors): ?>
                            <div class="control-group">
                                <label for="descriptor-field-note-<?php echo html_encode($key); ?>" class="control-label"><?php echo $translate->_("Response Category"); ?></label>
                                <div class="controls">
                                    <select id="descriptor-field-note-<?php echo html_encode($key); ?>" name="field_note_ardescriptor_id[<?php echo html_encode($key); ?>]" class="field-note-category">
                                        <option value="0"><?php echo $translate->_("-- Select Descriptor --"); ?></option>
                                        <?php foreach ($response_descriptors as $descriptor): ?>
                                            <option value="<?php echo html_encode($descriptor->getID()); ?>" <?php echo(isset($field_note_ardescriptor_id[$key]) && $field_note_ardescriptor_id[$key] == $descriptor->getID() ? "selected=\"selected\"" : "") ?>><?php echo html_encode($descriptor->getDescriptor()); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="checkbox field-note-flag" for="flag-field-note-response-<?php echo html_encode($key); ?>">
                                        <input type="checkbox" id="flag-field-note-response-<?php echo html_encode($key); ?>" name="field_note_flag_response[<?php echo html_encode($key); ?>]" value="1" <?php echo(isset($field_note_flag_response[$key]) && $field_note_flag_response[$key] == "1" ? "checked=\"checked\"" : "") ?>><?php echo $translate->_("Flag this Response"); ?>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        <div class="control-group">
                            <label for="field-note-response-<?php echo html_encode($key); ?>" class="control-label form-required"><?php echo $translate->_("Level of Competency"); ?></label>
                            <div class="controls">
                                <textarea id="field-note-response-<?php echo html_encode($key); ?>" name="field_note_item_responses[<?php echo html_encode($key); ?>]"><?php echo html_encode($response); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
            <?php else: ?>
                <div id="field-note-1" class="field-note-response">
                    <?php if ($response_descriptors): ?>
                        <div class="control-group">
                            <label for="descriptor-field-note-1" class="control-label"><?php echo $translate->_("Response Category"); ?></label>
                            <div class="controls">
                                <select id="descriptor-field-note-1" name="field_note_ardescriptor_id[1]" class="field-note-category">
                                    <option value="0"><?php echo $translate->_("-- Select Descriptor --"); ?></option>
                                    <?php foreach ($response_descriptors as $descriptor): ?>
                                        <option value="<?php echo html_encode($descriptor->getID()); ?>"><?php echo html_encode($descriptor->getDescriptor()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="checkbox field-note-flag" for="flag-field-note-response-1">
                                    <input type="checkbox" id="flag-field-note-response-1" name="field_note_flag_response[1]" value="1"><?php echo $translate->_("Flag this Response"); ?>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                    <div class="control-group">
                        <label for="field-note-response-1" class="control-label form-required"><?php echo $translate->_("Level of Competency"); ?></label>
                        <div class="controls">
                            <textarea id="field-note-response-1" name="field_note_item_responses[1]"></textarea>
                        </div>
                    </div>
                </div>
                <div id="field-note-2" class="field-note-response">
                    <?php if ($response_descriptors): ?>
                        <div class="control-group">
                            <label for="descriptor-field-note-2" class="control-label"><?php echo $translate->_("Response Category"); ?></label>
                            <div class="controls">
                                <select id="descriptor-field-note-2" name="field_note_ardescriptor_id[2]" class="field-note-category">
                                    <option value="0"><?php echo $translate->_("-- Select Descriptor --"); ?></option>
                                    <?php foreach ($response_descriptors as $descriptor): ?>
                                        <option value="<?php echo html_encode($descriptor->getID()); ?>"><?php echo html_encode($descriptor->getDescriptor()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="checkbox field-note-flag" for="flag-field-note-response-2">
                                    <input type="checkbox" id="flag-field-note-response-2" name="field_note_flag_response[2]" value="1"><?php echo $translate->_("Flag this Response"); ?>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="control-group">
                        <label for="field-note-response-2" class="control-label form-required"><?php echo $translate->_("Level of Competency"); ?></label>
                        <div class="controls">
                            <textarea id="field-note-response-2" name="field_note_item_responses[2]"></textarea>
                        </div>
                    </div>
                </div>
                <div id="field-note-3" class="field-note-response">
                    <?php if ($response_descriptors): ?>
                        <div class="control-group">
                            <label for="descriptor-field-note-3" class="control-label"><?php echo $translate->_("Response Category"); ?></label>
                            <div class="controls">
                                <select id="descriptor-field-note-3" name="field_note_ardescriptor_id[3]" class="field-note-category">
                                    <option value="0"><?php echo $translate->_("-- Select Descriptor --"); ?></option>
                                    <?php foreach ($response_descriptors as $descriptor): ?>
                                        <option value="<?php echo html_encode($descriptor->getID()); ?>"><?php echo html_encode($descriptor->getDescriptor()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="checkbox field-note-flag" for="flag-field-note-response-3">
                                    <input type="checkbox" id="flag-field-note-response-3" name="field_note_flag_response[3]" value="1"><?php echo $translate->_("Flag this Response"); ?>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="control-group">
                        <label for="field-note-response-3" class="control-label form-required"><?php echo $translate->_("Level of Competency"); ?></label>
                        <div class="controls">
                            <textarea id="field-note-response-3" name="field_note_item_responses[3]"></textarea>
                        </div>
                    </div>
                </div>
                <div id="field-note-4" class="field-note-response">
                    <?php if ($response_descriptors): ?>
                        <div class="control-group">
                            <label for="descriptor-field-note-4" class="control-label"><?php echo $translate->_("Response Category"); ?></label>
                            <div class="controls">
                                <select id="descriptor-field-note-4" name="field_note_ardescriptor_id[4]" class="field-note-category">
                                    <option value="0"><?php echo $translate->_("-- Select Descriptor --"); ?></option>
                                    <?php foreach ($response_descriptors as $descriptor): ?>
                                        <option value="<?php echo html_encode($descriptor->getID()); ?>"><?php echo html_encode($descriptor->getDescriptor()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="checkbox field-note-flag" for="flag-field-note-response-4">
                                    <input type="checkbox" id="flag-field-note-response-4" name="field_note_flag_response[4]" value="1"><?php echo $translate->_("Flag this Response"); ?>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="control-group">
                        <label for="field-note-response-4" class="control-label form-required"><?php echo $translate->_("Level of Competency"); ?></label>
                        <div class="controls">
                            <textarea id="field-note-response-4" name="field_note_item_responses[4]"></textarea>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php 
    }
}
