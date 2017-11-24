<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * @author Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("sandbox", "read")) {
    add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

    echo display_error();

    application_log("error", "Group [" . $ENTRADA_USER->getActiveGroup() . "] and role [" . $ENTRADA_USER->getActiveRole() . "] do not have access to this module [" . $MODULE . "]");
} else {
    $PAGE_META["title"] = "Public Side: Sandbox";
    $PAGE_META["description"] = "";
    $PAGE_META["keywords"] = "";

    $BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/sandbox", "title" => "Public Side: Sandbox");

    $HEAD[]   = '<link rel="stylesheet" href="'.ENTRADA_URL.'/css/sandbox.css">';
    $JQUERY[] = '<script src="https://unpkg.com/vue@2.2.1"></script>';
    $JQUERY[] = '<script src="https://unpkg.com/axios@0.15.3/dist/axios.min.js"></script>';

    $sidebar = new Views_Sandbox_Sidebar();
    $sidebar->render();
    ?>
    <div id="module-sandbox">

      <div class="pagination clearfix">

        <p class="text-center summary">
          <?php echo $translate->_("Page {{ sandboxes.current_page }} / {{ sandboxes.last_page }} &mdash; {{ sandboxes.total }} Sandboxes Found"); ?>
        </p>

        <ul class="pull-right">
          <li v-bind:class="{ disabled:!sandboxes.prev_page_url }">
            <a href="#" aria-label="Previous" title="Previous" v-on:click.prevent="sandboxes.prev_page_url ? getSandboxes(sandboxes.prev_page_url) : null">
              <span aria-hidden="true">«</span>
            </a>
          </li>
          <li v-bind:class="{ disabled:!sandboxes.next_page_url }">
            <a href="#" aria-label="Next" v-on:click.prevent="sandboxes.next_page_url ? getSandboxes(sandboxes.next_page_url) : null">
              <span aria-hidden="true">»</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="table-responsive">
        <table class="table table-borderless">
          <tr>
            <th width="150"><?php echo $translate->_("Title"); ?></th>
            <th width="200"><?php echo $translate->_("Description"); ?></th>
            <th><?php echo $translate->_("Created By"); ?></th>
            <th><?php echo $translate->_("Updated By"); ?></th>
          </tr>
          <tr v-for="sandbox in sandboxes.data">
            <td>{{ sandbox.title }}</td>
            <td>{{ sandbox.description }}</td>
            <td><span v-if="sandbox.created_by">{{ sandbox.created_by.firstname }} {{ sandbox.created_by.lastname }}</span></td>
            <td><span v-if="sandbox.updated_by">{{ sandbox.updated_by.firstname }} {{ sandbox.updated_by.lastname }}</span></td>
          </tr>
        </table>
      </div>

      <?php

      // Create sandbox modal
      $create_sandbox_modal = new Views_Gradebook_Modal([
        "id" => "create-sandbox",
        "title" => $translate->_("Create New Sandbox"),
      ]);

      $create_sandbox_modal->setBody('
        <form class="form-horizontal" v-on:submit.prevent="createSandbox">
          <div class="control-group" v-bind:class="{ error: form_errors.title }">
            <label class="control-label" for="newTitle">'.$translate->_("Title").'</label>
            <div class="controls">
              <input type="text" id="newTitle" placeholder="'.$translate->_("Title").'" v-model="new_sandbox.title">
              <p v-if="form_errors.title" class="error text-error">
                {{ form_errors.title }}
              </p>
            </div>
          </div>
          <div class="control-group" v-bind:class="{ error: form_errors.description }">
            <label class="control-label" for="newDescription">'.$translate->_("Description").'</label>
            <div class="controls">
              <textarea id="newDescription" placeholder="'.$translate->_("Description").'" v-model="new_sandbox.description"></textarea>
              <p v-if="form_errors.description" class="error text-error">
                {{ form_errors.description }}
              </p>
            </div>
          </div>

          <div class="control-group">
            <button type="submit" class="btn btn-success pull-right">'.$translate->_("Create Sandbox").'</button>
          </div>
        </form>
      ');

      $create_sandbox_modal->render();

      // Edit sandbox modal
      $edit_sandbox_modal = new Views_Gradebook_Modal([
        "id" => "edit-sandbox",
        "title" => $translate->_("Edit")." ".'"{{ update_sandbox.title }}"',
      ]);

      $edit_sandbox_modal->setBody('
        <form class="form-horizontal" v-on:submit.prevent="updateSandbox(update_sandbox.id)">
          <div class="control-group" v-bind:class="{ error: form_errors.title }">
            <label class="control-label" for="updateTitle">'.$translate->_("Title").'</label>
            <div class="controls">
              <input type="text" id="updateTitle" placeholder="'.$translate->_("Title").'" v-model="update_sandbox.title">
              <p v-if="form_errors.title" class="error text-error">
                {{ form_errors.title }}
              </p>
            </div>
          </div>
          <div class="control-group" v-bind:class="{ error: form_errors.description }">
            <label class="control-label" for="updateDescription">'.$translate->_("Description").'</label>
            <div class="controls">
              <textarea id="updateDescription" placeholder="'.$translate->_("Description").'" v-model="update_sandbox.description"></textarea>
              <p v-if="form_errors.description" class="error text-error">
                {{ form_errors.description }}
              </p>
            </div>
          </div>

          <div class="control-group">
            <button type="submit" class="btn btn-success pull-right">'.$translate->_("Update Sandbox").'</button>
          </div>
        </form>
      ');

      $edit_sandbox_modal->render();

      ?>
        
    </div>
    <?php

    // sandbox.js needs to run after DOM content
    echo '<script src="'.ENTRADA_URL.'/javascript/sandbox.js?release='.html_encode(APPLICATION_VERSION).'"></script>';
}