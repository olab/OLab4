<template>
    <card :id="id" class="role-card" :no-header-border="true" :content-toggle="true" :checkbox="true" @cardcheckboxupdate="updatingroleCardCheckbox" :checkbox-id="id" :checkbox-name="id" checkboxlabel="Select Role" :editmodetoggle="editMode">
        <header>
            <!-- Changed Titles To Show Keys -->
            <card-heading v-if="!editMode">{{ id }}</card-heading>
            <card-heading v-if="editMode">Editing {{ id }}</card-heading>
            <!-- <card-heading v-if="!editMode">{{ myTitle }}</card-heading>
            <card-heading v-if="editMode">Editing {{ myTitle }}</card-heading> -->
            <p v-if="!editMode &amp;&amp; isCourseSpecific">Contains course specific permissions</p>
        </header>

        <div slot="content">
            <div v-if="myDescription" class="role-description" v-html="myDescription"></div>
            <list-group :alt-title="true" empty-message="This role has no assigned permissions.">
                <div slot="title">Permissions</div>
                <list-item :content-toggle="true" v-for="permission in assignedPermissions" :key="permission.id, id | permissionKey | assignedKey">
                    <!-- Changed Titles To Show Keys -->
                    <strong>{{permission.id, id | permissionKey | assignedKey}}</strong>
                    <!-- <strong>{{ permission | permissionTitle }}</strong> -->
                    <div slot="content">
                        {{ permission.description }}
                        <div class="badge-wrap">
                            <label>Roles</label>
                            <badge v-for="role in permission.roles" :key="role">{{ role }}</badge>
                        </div>
                        <alert v-if="isCourseSpecific">This permission will only be applied when the user is assigned to a course.</alert>
                    </div>
                </list-item>
            </list-group>
        </div>

        <div slot="edit">
            <single-line-input v-model="myTitle" id="testingID" type="text">
                Role Title
            </single-line-input>

            <!-- Disabled Text Editor For Ease Of Rapid Testing -->
            <!-- <text-editor :content="myDescription" toolbar="simple" @updatingInput="bufferingDescription">Description</text-editor> -->

            <btn class="select-permission-toggle" type="primary" :small="true" :append-icon="permissionToggleIcon" @click.native="togglingPermissionSelector">Select Permissions</btn>

            <list-group v-if="permissionSelector" :max-height="200" class="selectable-permissions" empty-message="No matching permissions">
                <div slot="title">
                    <single-line-input v-model="search" id="search-permission" type="text" placeholder="Search for permission" append-icon="fa fa-search" :no-label="true"></single-line-input>
                </div>

                <list-item v-for="permission in filteredPermissions" :key="permission.id, id | permissionKey | filteredKey">
                    <!-- Changed Titles To Show Keys -->
                    <span>{{permission.id, id | permissionKey | filteredKey}}</span>
                    <!-- <span>{{ permission | permissionTitle }}</span> -->
                    <div class="btn-wrapper">
                        <btn :type="permission.type" :small="true" prepend-icon="fa fa-plus" @click.native="addingPermissionToRole(permission)">Add</btn>
                    </div>
                </list-item>
            </list-group>

            <list-group :alt-title="true" class="assigned-permissions" empty-message="This role has no assigned permissions.">
                <div slot="title">Permissions</div>

                <list-item v-for="permission in computedBufferPermissions" :key="permission.id, id | permissionKey | bufferedKey">
                    <!-- Changed Titles To Show Keys -->
                    <strong>{{permission.id, id | permissionKey | bufferedKey}}</strong>
                    <!-- <strong>{{ permission | permissionTitle}}</strong> -->
                    <div class="remove"><span class="fa fa-times"></span></div>
                </list-item>
            </list-group>

            <div class="action-buttons">
                <btn @click.native="cancellingEditMode" :small="true">Cancel</btn>
                <btn @click.native="confirmingEditMode" type="primary" prepend-icon="fa fa-check" :small="true">Confirm</btn>
            </div>
        </div>

        <footer v-if="!editMode" slot="footer">
            <div>
                <a href="#">{{ users }} assigned users</a>
            </div>
            <div>
                <btn v-show="computedCheckCount =='merge'" prepend-icon="fa fa-compress" :small="true">Merge Into This Role</btn>
                <btn v-show="computedCheckCount == 'copy'" prepend-icon="fa fa-files-o" :small="true">Copy</btn>
                <btn @click.native="enteringEditMode" type="primary" prepend-icon="fa fa-pencil" :small="true">Edit</btn>
                <btn v-show="this.users == 0" :small="true" type="danger" prepend-icon="fa fa-trash">Delete</btn>
            </div>
        </footer>
    </card>
</template>

<script>
'use strict';var Alert=use('./Alert.vue');var Badge=use('./Badge.vue');var Btn=use('./Button.vue');var Card=use('./Card.vue');var CardHeading=use('./CardHeading.vue');var Checkbox=use('./Checkbox.vue');var ListGroup=use('./ListGroup.vue');var ListItem=use('./ListItem.vue');var SingleLineInput=use('./SingleLineInput.vue');var TextEditor=use('./TextEditor.vue');module.exports={name:'RoleCard',components:{Alert:Alert,Badge:Badge,Btn:Btn,Card:Card,CardHeading:CardHeading,ListGroup:ListGroup,ListItem:ListItem,SingleLineInput:SingleLineInput,TextEditor:TextEditor},props:{id:{type:String,required:true},title:{type:String,required:true},description:{type:String,required:true},users:{type:Number,required:true},checkCount:{type:Number,required:true},permissions:{type:Array,required:true}},data:function data(){return{editMode:false,isCourseSpecific:false,permissionSelector:false,myTitle:'',myDescription:'',myPermissions:[],permissionData:[],bufferDescription:'',bufferPermissions:[],flaggedPermissions:[],search:'',roleCardCheckbox:false}},filters:{permissionTitle:function permissionTitle(value){if(value.courseSpecific==true){return value.title+' (Course Specific)'}else{return value.title};},permissionKey:function permissionKey(value,role){return role+'-permission-'+value},assignedKey:function assignedKey(value){return value+'-assigned'},filteredKey:function filteredKey(value){return value+'-filtered'},bufferedKey:function bufferedKey(value){return value+'-buffered'}},created:function created(){this.myTitle=this.title;this.myDescription=this.description;this.myPermissions=this.permissions;this.permissionData=this.$store.state.permissions;this.flaggedPermissions=this.permissionData.slice(0);this.flaggedPermissions.forEach(function(p){p.type='success'})},computed:{computedCheckCount:function computedCheckCount(){if(this.checkCount==1&&this.roleCardCheckbox){return'copy'}else if(this.checkCount>1&&this.roleCardCheckbox){return'merge'}else{return''}},assignedPermissions:function assignedPermissions(){var self=this;var length=self.myPermissions.length;return this.permissionData.filter(function(permission){for(var i=0;i<=length;i++){if(permission.courseSpecific==true&&permission.id==self.myPermissions[i]){self.isCourseSpecific=true};if(permission.id==self.myPermissions[i]){return true}};})},filteredPermissions:function filteredPermissions(){var self=this;return this.flaggedPermissions.filter(function(permission){return permission.title.toLowerCase().indexOf(self.search.toLowerCase())>=0})},permissionToggleIcon:function permissionToggleIcon(){if(this.permissionSelector==true){return'fa fa-chevron-up'}else{return'fa fa-chevron-down'}},computedBufferPermissions:function computedBufferPermissions(){var self=this;var length=self.bufferPermissions.length;return this.permissionData.filter(function(permission){for(var i=0;i<=length;i++){if(permission.courseSpecific==true&&permission.id==self.bufferPermissions[i]){self.isCourseSpecific=true};if(permission.id==self.bufferPermissions[i]){return true}};})}},methods:{updatingroleCardCheckbox:function updatingroleCardCheckbox(isChecked){this.roleCardCheckbox=isChecked;this.$emit('role-card-checkbox-update',isChecked)},enteringEditMode:function enteringEditMode(){this.bufferPermissions=this.myPermissions.slice(0);this.editMode=!this.editMode},cancellingEditMode:function cancellingEditMode(){this.bufferPermissions=[];this.editMode=!this.editMode;this.permissionSelector=false;this.search=''},confirmingEditMode:function confirmingEditMode(){this.myPermissions=this.bufferPermissions.slice(0);this.bufferPermissions=[];this.editMode=!this.editMode;this.permissionSelector=false;this.search=''},bufferingDescription:function bufferingDescription(html){this.bufferDescription=html},togglingPermissionSelector:function togglingPermissionSelector(){var self=this;this.flaggedPermissions.forEach(function(flag){self.bufferPermissions.forEach(function(id){if(flag.id==id){flag.type='disabled'}})});this.permissionSelector=!this.permissionSelector},addingPermissionToRole:function addingPermissionToRole(addedPermission){var exists=false;this.bufferPermissions.forEach(function(existingPermission){if(existingPermission==addedPermission.id){exists=true};});if(!exists){this.bufferPermissions.push(addedPermission.id);addedPermission.type='disabled'};}}};
</script>

<style>
.ejs .role-card .card-header p{margin:0}.ejs .role-card .role-description{margin-bottom:40px}.ejs .role-card .role-description>*:first-child{margin-top:0}.ejs .role-card .role-description>*:last-child{margin-bottom:0}.ejs .role-card .role-description>*:only-child{margin-bottom:0;margin-top:0}.ejs .role-card .remove{margin-left:auto;color:#f32753;cursor:pointer}.ejs .role-card .badge-wrap{margin-top:15px}.ejs .role-card .badge-wrap label{display:block;margin-bottom:5px}.ejs .role-card .badge-wrap::after{clear:both;content:"";display:block}.ejs .role-card .action-buttons{margin-top:30px}.ejs .role-card .action-buttons::after{clear:both;content:"";display:block}.ejs .role-card .action-buttons .btn{margin:0}.ejs .role-card .action-buttons .btn:first-child{float:left}.ejs .role-card .action-buttons .btn:last-child{float:right}.ejs .role-card .alert .alert-inner{margin:15px 0 0 0}.ejs .role-card .select-permission-toggle{margin:0}.ejs .role-card .list-group{margin-bottom:0}.ejs .role-card .list-group.assigned-permissions{margin-top:50px}.ejs .role-card .list-group.selectable-permissions{margin-top:10px}.ejs .role-card .list-group.selectable-permissions .btn-wrapper{margin-left:auto}.ejs .role-card .list-group.selectable-permissions .btn{margin:0}.ejs .role-card .list-group .input{margin:0;padding:0}.ejs .role-card .list-group strong{color:#17323c;font-family:"Lucida Grande","Lucida Sans","Lucida Sans Unicode","Geneva",Verdana,sans-serif;font-size:.9em}@media (max-width: 600px){.ejs .role-card .list-group.selectable-permissions .btn .btn-text{display:none}.ejs .role-card .list-group.selectable-permissions .btn .prepend-icon{padding:10px 15px}.ejs .role-card .list-group.selectable-permissions .btn .prepend-icon.fa{margin-top:3px}}
</style>
