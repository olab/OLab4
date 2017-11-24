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
 * Loads the Course file wizard when a course director wants to add / edit
 * a file on the Manage Courses > Content page.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

ob_start("on_checkout");

if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	echo "<div id=\"scripts-on-open\" style=\"display: none;\">\n";
	echo "alert('It appears as though your session has expired; you will now be taken back to the login page.');\n";
	echo "if (window.opener) {\n";
	echo "	window.opener.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "	top.window.close();\n";
	echo "} else {\n";
	echo "	window.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "}\n";
	echo "</div>\n";
	exit;
} else {

	$ACTION				= "add";
	$IDENTIFIER			= 0;
	$FILE_ID			= 0;
	$JS_INITSTEP		= 1;

	if (isset($_POST["action"])) {
		$ACTION	= trim($_POST["action"]);
	}
    
    $Bookmarks = new Models_Bookmarks();
    
    switch ($ACTION) {
        case "add":
            
            ?>
            <div class="panel-head bookmark-title"><h3>Add Bookmark</h3></div>
                <form class="form-horizontal bookmark-form" id="bookmark-form-add">
                    <div class="control-group">
                      <label class="control-label" for="page-uri">Page URL:</label>
                      <div class="controls">
                        <span class="input-xlarge uneditable-input"><?php echo $_POST['id']; ?></span>
                        <input type="hidden" name="pageUri" value="<?php echo $_POST['id']; ?>" id="pageUri" />
                      </div>
                    </div>
                    <div class="control-group">
                      <label class="control-label" for="inputPassword">Bookmark Title</label>
                      <div class="controls">
                        <input class="input-xlarge" type="text" name="bookmarkTitle" id="bookmark-title"
                               placeholder="Please enter a title" autofocus>
                      </div>
                    </div>
                    <input type="hidden" name="ajax" value="ajax" id="ajax" />
                    <input type="hidden" name="method" value="add-bookmark" id="method" />
                    
            <?php
            break;
        case "remove":
            $Bookmark = $Bookmarks->fetchBookmarkById ($ENTRADA_USER->getID(), $_POST['id']);
            
            ?>
                <div class="panel-head bookmark-title"><h3>Remove Bookmark</h3></div>
                <form class="form-horizontal bookmark-form" id="bookmark-form-remove">
                    <div class="alert alert-warning">
                        Are you sure you would like to <strong>remove</strong> this page from your Bookmarks?
                    </div>
                    <div class="control-group">
                      <label class="control-label" for="page-uri">Bookmark URL:</label>
                      <div class="controls form-control-static">
                        <?php echo ($Bookmark) ? $Bookmark->getUri() : 'Not bookmarked'; ?>
                      </div>
                    </div>
                    <div class="control-group">
                      <label class="control-label" for="page-uri">Bookmark Title:</label>
                      <div class="controls form-control-static">
                        <?php echo ($Bookmark) ? $Bookmark->getBookmarkTitle() : 'Not bookmarked'; ?>
                      </div>
                    </div>
                    
                    <input type="hidden" name="ajax" value="ajax" id="ajax" />
                    <input type="hidden" name="pageUri" value="<?php echo ($Bookmark) ? $Bookmark->getUri() : 'Not bookmarked'; ?>" id="pageUri" />
                    <input type="hidden" name="bookmarkTitle" value="<?php echo ($Bookmark) ? $Bookmark->getBookmarkTitle() : 'Not bookmarked'; ?>" id="bookmark-title" />
                    <input type="hidden" id="bookmark-id-value" name="bookmarkId" value="<?php echo ($Bookmark) ? $Bookmark->getId() : 'Not bookmarked'; ?>" id="bookmark-id" />
                    <input type="hidden" name="method" value="remove-bookmark" id="method" />
                  
            <?php 
            break;
    }
    
    ?>
                    <div class="row-fluid">
						<button class="btn btn-primary pull-right" id="submit-bookmark">Submit</button>
                        <button class="btn" id="close-bookmark-form">Close</button>
					</div>
                  </form>           
    <?php
	
}