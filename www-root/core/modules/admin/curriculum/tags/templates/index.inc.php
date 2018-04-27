<div id="msgs"></div>

<!-- UO buttons -->
<div id="actions" class="curriculum-actions pull-right">
    <form id="export-form" action="<?php echo ENTRADA_URL; ?>/api/api-reporting.inc.php" method="post">
        <input type="hidden" id="table-data" name="data" value=""/>
        <input type="hidden" id="method" name="method" value="csv"/>
        <?php if ($ENTRADA_ACL->amIAllowed("objective", "create", false)) { ?>
            <button class="btn btn-success btn-add" id="btn-add">
                <i class="fa fa-plus-circle" aria-hidden="true"></i>
                <?php echo($translate->_("Add Tag Set")); ?>
            </button>
        <?php } ?>
        <button class="btn btn-primary btn-export" id="exportbtn" name="exportbtn">
            <i	class="fa fa-download" aria-hidden="true"></i>
            <?php echo($translate->_("Export")); ?>
        </button>
    </form>
</div>
<!-- END UO buttons -->

<div id="filter-accordion-alerts" data-msg="Objectives"></div>

<table class="hide curriculum-tags-table" id="TableManageObj" data-toggle="table"
    data-show-multi-sort="true" data-striped="true"
	data-show-columns="true" data-unique-id="objective_set_id"
	data-pagination="true" data-sortable="true" data-sort-name="title"
	data-striped="true" data-page-list="[15, 25, 50, All]">
	<thead>
		<tr>
			<!-- Check box Row -->
			<th data-field="selectall" id="selectall" data-checkbox="true"></th>

			<th data-field="objective_set_id" data-sortable="true" data-formatter="linkFormatterObjective" data-visible="false">
            	<?php echo($translate->_("ID"));?>
            </th>
            <th data-field="title" data-sortable="true" data-formatter="linkFormatterObjective">
                <?php echo($translate->_("Title"));?>
            </th>
            <th data-field="shortname" data-sortable="true" data-formatter="linkFormatterObjective" data-visible="false">
                <?php echo($translate->_("Shortname"));?>
            </th>
            <th data-field="description" data-sortable="true" data-formatter="linkFormatterObjective" data-visible="false">
                <?php echo($translate->_("Description"));?>
            </th>
		</tr>
	</thead>
</table>
<div class="loading-curriculum-tags hide">
    <p class="text-center muted space-above"><i class="fa fa-spin fa-spinner"></i> Loading results, please wait</p>
</div>


<script type="text/javascript">
    var $j = jQuery.noConflict();

    $j(document).ready(function(event) {
        UIEventSpace.init(parent_module);
        parent_module = {
            name:"objectives",
            base_url:window.location.href
        };
    });
    
    function linkFormatterObjective(value,row,index) {
        return '<a href="' + parent_module.base_url + '/objectives?set_id='+row.objective_set_id+'">'+value+'</a>';
    };
    
    function formatObjectiveList(value,row,index) {
        if (value == null || !$j.isArray(value)) {
            return "";
        }
        
        output = "<ul>";
        $j.each(value, function(id,val) {output = output + "<li>" +val+"</li>";});
        output = output + "</ul>";
        return output;  
    };
    
</script>
<div class="space-above">
    <?php if ($ENTRADA_ACL->amIAllowed("objective", "delete", false)) {?>
        <button class="btn btn-danger btn-delete" id="btn-delete"	data-toggle="modal" data-target="#delete-courses-modal" disabled>
            <i	class="fa fa-trash" aria-hidden="true"></i>
            <?php echo($translate->_("Delete Items")); ?>
        </button>
    <?php } if ($ENTRADA_ACL->amIAllowed("objective", "create", false)) { ?>
        <button style="display:none;" class="btn btn-default btn-duplicate" id="btn-duplicate" disabled>
            <i class="fa fa-copy" aria-hidden="true"></i>
            <?php echo($translate->_("Duplicate")); ?>
        </button>
    <?php } ?>
</div>
