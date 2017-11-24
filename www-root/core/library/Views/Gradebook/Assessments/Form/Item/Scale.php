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
 * Renders an assessment scale item
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item_Scale extends Views_Gradebook_Assessments_Form_Item_HorizontalMultipleChoiceSingleResponse {

	protected function renderItemResponseLabel($item_response) {
        return '<label for="item-'.html_encode($this->item_id).'-response-'.html_encode($item_response['iresponse_id']).'">'.html_encode($item_response['descriptor']).'</label>';
    }
}