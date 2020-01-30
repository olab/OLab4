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

if (!isset($this)) {
    throw new Exception("You cannot visit this file directly because it is an include.");
}

?>
<a name="<?php echo $this->anchor_name; ?>"></a>
<?php if (isset($this->heading_title)): ?>
    <h2 title="<?php echo $this->translate->_($this->section_title); ?>"><?php echo $this->translate->_($this->heading_title); ?></h2>
<?php elseif (isset($this->tag_set_name)): ?>
    <h2 title="<?php echo $this->tag_set_name; ?>"><?php echo $this->tag_set_name; ?></h2>
<?php endif; ?>
<div id="<?php echo $this->element_id; ?>">
    <style>
        ul.objective-list ul {
            background: none !important;
        }
        .objective-list {
            padding-left: 5px !important;
        }
        .objective-description {
            width: 90%;
        }
        .objective-title {
            font-weight:bold;
            cursor:pointer;
            line-height: 20px;
        }
        .objective-title-no-children {
            cursor:default !important;
        }
        .objective-children {
            margin-top:5px;
        }
        .objective-container {
            position:relative;
            padding-right:0px!important;
            margin-right:0px!important;
        }
        .objective-controls {
            position:absolute;
            top:5px;
            right:0px;
        }
        .objective-controls i {
            display:block;
            width:16px;
            height:16px;
            cursor:pointer;
            float:left;
        }
        .objective-title-children  {
            transition:.6s all;
            position: relative;
            padding-left:15px;
        }

        .objective-open:before{
            transition:.6s all ease;
            transform:rotate(90deg);
        }
        .objective-title-children:before{
            transition:.6s all;
            content: ' ';
            background:#002145;
            height:10px;
            display:inline-block;
            border-radius:0;
            width:2px;
            padding:0;
            position:absolute;
            left:2px;
            top:10px;
        }

        @media screen {
            .objective-title-children:after{
                transition:.6s all;
                content: ' ';
                height:10px;
                display:inline-block;
                border-radius:0;
                width:2px;
                padding:0;
                position:absolute;
                left:2px;
                top:10px;
                background:#002145;
                transform:rotate(90deg);
            }
        }

        @media print {
            .objective-title-children:after{
                content: '+';
                position:absolute;
                left:2px;
                top:10px;
            }
        }

    </style>
    <ul class="objective-list mapped-list">
        <?php if (!isset($this->tag_set_name)): ?>
            <?php foreach ($this->objectives as $tag_set => $objectives_for_tag_set): ?>
                <h3><?php echo $tag_set; ?></h3>
                <?php
                $objectives_list_view = new Zend_View();
                $objectives_list_view->setScriptPath(dirname(__FILE__));
                $objectives_list_view->objectives = $objectives_for_tag_set;
                $objectives_list_view->direction = $this->direction;
                $objectives_list_view->event_id = isset($this->event_id) ? $this->event_id : null;
                $objectives_list_view->cunit_id = isset($this->cunit_id) ? $this->cunit_id : null;
                $objectives_list_view->course_id = isset($this->course_id) ? $this->course_id : null;
                echo $objectives_list_view->render("objectives-list.inc.php");
                ?>
            <?php endforeach; ?>
        <?php else: ?>
            <?php
            $objectives_list_view = new Zend_View();
            $objectives_list_view->setScriptPath(dirname(__FILE__));
            $objectives_list_view->objectives = $this->objectives;
            $objectives_list_view->direction = $this->direction;
            $objectives_list_view->event_id = isset($this->event_id) ? $this->event_id : null;
            $objectives_list_view->cunit_id = isset($this->cunit_id) ? $this->cunit_id : null;
            $objectives_list_view->course_id = isset($this->course_id) ? $this->course_id : null;
            echo $objectives_list_view->render("objectives-list.inc.php");
            ?>
        <?php endif; ?>
    </ul>
    <script type="text/javascript">var EXCLUDE_TAG_SET_IDS = <?php echo json_encode($this->exclude_tag_set_ids); ?>;</script>
    <script type="text/javascript">var VERSION_ID = <?php echo json_encode($this->version_id); ?>;</script>
    <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/objective-links.js"></script>
</div>
