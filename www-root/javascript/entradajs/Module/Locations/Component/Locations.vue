<template>
    <div class="locations-app">
        <h1 v-i18n>Location Management</h1>
        <h2 v-i18n>Sites</h2>
        <div class="clearfix">
             <a href="#/locations/site/" v-if="permissions.create" class="btn btn-success pull-right space-below space-right"><i class="fa fa-plus"></i> Add New Site</a>
        </div>

        <table class="table table-bordered table-striped" v-if="sites.length > 0" cellspacing="0" cellpadding="1" border="0" summary="">
        <colgroup>
            <col class="modified"/>
            <col class="title" />
            <col />
            <col />
        </colgroup>
        <thead>
        <tr>
            <th class="modified span1">&nbsp;</th>
            <th class="title span7" v-i18n>Name</th>
            <th class="span3" v-i18n>Address</th>
        </tr>
        </thead>
        <tbody class="site-list" v-if="permissions.read">
        <tr v-show="isLoading()">
            <td colspan="3"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i> Loading</p></td>
        </tr>
        <tr v-show="!isLoading()" v-for="site in sites" class="site">
            <td><input type="checkbox" name="remove_ids[]" :value="`${ site.site_id }`" v-model="selected_site" /></td>
            <td><a :href="site.edit_url">{{ site.site_name }}</a></td>
            <td>{{ site.site_address1 }}</td>
        </tr>
        <tr v-show="!isLoading()" v-if="sites.length == 0">
            <td colspan="3" v-i18n>There are no sites created for this organisation</td>
        </tr>
        </tbody>
        </table>

        <div class="alert" v-if="!isLoading() && sites.length <= 0" v-i18n>
            There are no sites
        </div>

        <!-- It is missing the text translation -->
        <a v-if="permissions.delete && selected_site.length > 0" class="btn btn-danger" @click="show_delete_modal = true"><i class="fa fa-trash"></i> Delete Selected</a>

        <div v-if="isLoading()">
            <p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i> Loading</p>
        </div>

        <modal v-if="show_delete_modal" title="Delete Buildings" v-on:ok="deleteSite" v-on:hide="show_delete_modal = false" savebutton="Delete" saveclass="btn-danger">
            <slot>
                <p v-i18n>Are you sure to delete the selected sites?</p>
            </slot>
        </modal>
    </div>
</template>

<script>
    const RestClient = use('EntradaJS/Http/RestClient');
    const Modal = use('./Modal.vue');

    const STATES = {
        Ready: 0,
        Loading: 1,
        Creating: 2,
        Editing: 3,
        Deleting: 4
    };

    module.exports = {
        name: 'locations-app',
        data() {
            return {
                state: 0,
                site: {
                    site_id: 0,
                    organisation_id: 0,
                    site_name: null,
                    site_code: null,
                    edit_url: this.$generatePath('locations.add_site')
                },
                sites: [],
                permissions: {
                    read: true,
                    create: true,
                    update: true,
                    delete: true
                },
                selected_site: [],
                show_delete_modal: false
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            this.fetchSites();
        },
        methods: {
            setState(state) {
                this.state = state;
            },
            resetState() {
                this.state = STATES.Ready;
                this.show_delete_modal = false;
            },
            isReady() {
                return this.state === STATES.Ready;
            },
            isLoading() {
                return this.state === STATES.Loading;
            },
            isCreating() {
                return this.state === STATES.Creating;
            },
            isEditing() {
                return this.state === STATES.Editing;
            },
            isDeleting() {
                return this.state === STATES.Deleting;
            },
            fetchSites() {
                this.state = STATES.Loading;

                this.api.get('/locations/sites/org/' + organisation_id).then(response => {
                    this.populateSiteList(response.json().sites);
                    this.resetState();
                });
            },
            populateSiteList(sites) {

                for(let site of sites) {
                    site.edit_url =  "#" + this.$generatePath('locations.edit_site', {site_id: site.site_id});
                }

                this.sites = sites;
            },
            deleteSite() {
                this.state = STATES.Loading;

                this.api.delete('/locations/sites/' + this.selected_site).then(result => {
                    this.selected_site = [];
                    this.fetchSites();
                    this.resetState();
                });
            }
        },
        components: {
            Modal,
        }
    };
</script>