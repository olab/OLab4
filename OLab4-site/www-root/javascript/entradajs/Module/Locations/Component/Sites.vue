<template>
    <div class="locations-app">
        <h2 v-if="!isEditingSite">{{ site.site_name }}  <a class="btn btn-small space-left" @click="isEditingSite = true"><i class="fa fa-pencil"></i></a></h2>

        <div v-if="isEditingSite" class="form-horizontal" id="site_add_form">
            <h2>{{form_title}}</h2>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.site_name }">
                <label class="control-label form-required" v-i18n>Site Name</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="site.site_name" maxlength="60" />
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.site_code }">
                <label class="control-label form-required" v-i18n>Site Code</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="site.site_code" maxlength="10" />
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.site_address1 }">
                <label class="control-label form-required" v-i18n>Address Line 1</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="site.site_address1" maxlength="255" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label form-nrequired" v-i18n>Address Line 2</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="site.site_address2"/>
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.site_city }">
                <label class="control-label form-required" v-i18n>City</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="site.site_city" maxlength="35" />
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.site_country_id }">
                <label class="control-label form-required" v-i18n>Country</label>
                <div class="controls">
                    <select class="input-large" v-model="site.site_country_id" v-if="countries.length > 0" @change="getProvinces()">
                        <option v-for="country in countries" :value="country.countries_id">{{ country.country }}</option>
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" v-i18n>{{translations.Province}}</label>
                <div class="controls">
                    <select class="input-large" v-model="site.site_province_id" v-if="provinces.length > 0">
                        <option v-for="province in provinces" :value="province.province_id">{{ province.province }}</option>
                    </select>
                    <small class="muted" v-if="provinces.length == 0" v-i18n>(Select a country)</small>
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.site_postcode }">
                <label class="control-label form-required" v-i18n>{{translations.PostalCode}}</label>
                <div class="controls">
                    <input class="input-large" placeholder="123456" type="text" v-model="site.site_postcode" maxlength="7" />
                </div>
            </div>

            <input type="hidden" v-model="site.organisation_id"/>
            <div class="alert alert-error" v-show="error_msg">
                {{ error_msg }}
            </div>

            <div class="control-group">
                <div class="controls">
                    <button class="btn" @click="cancelSite" v-i18n>Cancel</button>
                    <button class="btn btn-primary" @click="saveSite" v-i18n>Save</button>
                </div>
            </div>
        </div>

        <div v-if="site.site_id !== 0">
            <div class="row-fluid space-above space-below">
                <a :href="index_url" class="btn btn-default pull-left">Back</a>
                <a :href="new_building_url" v-if="permissions.create" class="btn btn-success pull-right" role="button"><i class="fa fa-plus"></i> Add New Building</a>
            </div>
            <h3>Buildings</h3>

            <table class="table table-bordered table-striped" v-if="!isLoading()" cellspacing="0" cellpadding="1" border="0" summary="">
                <colgroup>
                    <col class="modified"/>
                    <col />
                    <col />
                    <col />
                    <col />
                </colgroup>
                <thead>
                <tr>
                    <th class="modified span1">&nbsp;</th>
                    <th class="span1" v-i18n>Code</th>
                    <th class="title span3" v-i18n>Name</th>
                    <th class="span5" v-i18n>Address</th>
                </tr>
                </thead>
                <tbody class="room-list" v-if="permissions.read">
                <tr v-show="isLoading()">
                    <td colspan="4"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i> Loading</p></td>
                </tr>
                <tr v-show="!isLoading()" v-for="building in buildings" class="building">
                    <td><input type="checkbox" name="remove_ids[]" :value="`${ building.building_id }`" v-model="selected_building" /></td>
                    <td class="text-center"><a :href="building.edit_url">{{ building.building_code }}</a></td>
                    <td><a :href="building.edit_url">{{ building.building_name }}</a></td>
                    <td><p>{{ building.building_address1 }}</p></td>
                </tr>
                <tr v-show="!isLoading()" v-if="buildings.length == 0">
                    <td colspan="5" v-i18n>There are no buildings created for this site</td>
                </tr>
                </tbody>
            </table>
            <button v-if="permissions.delete && !isLoading() && selected_building.length > 0" class="btn btn-danger" @click="show_delete_modal = true"><i class="fa fa-trash"></i> Delete Selected</button>

            <modal v-if="show_delete_modal" title="Delete Buildings" v-on:ok="deleteBuilding" v-on:hide="show_delete_modal = false" savebutton="Delete" saveclass="btn-danger">
                <slot>
                    <p v-i18n>Are you sure to delete the selected buildings?</p>
                </slot>
            </modal>
        </div>
    </div>
</template>

<script>
    Array.prototype.filter = window.filter;
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
                    site_address1: null,
                    site_address2: null,
                    site_city: null,
                    site_province_id: 0,
                    site_country_id: 0,
                    site_postcode: null
                },
                building: {
                    building_id: 0,
                    site_id: 0,
                    building_name: null,
                    building_code: null,
                    edit_url: "#"
                },
                buildings: [],
                countries: [],
                provinces: [],
                permissions: {
                    read: true,
                    create: true,
                    update: true,
                    delete: true
                },
                form_title: "Add Site",
                room_form_title: "Add Building",
                selected_building: [],
                index_url: "#" + this.$generatePath('locations.index'),
                new_building_url: "#",
                validation_errors: [],
                error_msg: false,
                show_delete_modal: false,
                translations: {},
                isEditingSite: false,
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            this.getCountries();

            if (typeof this.$getRoute().getParameter('site_id') !== "undefined") {
                this.site.site_id = this.$getRoute().getParameter('site_id');
                this.building.site_id = this.site.site_id;
                this.form_title = "Edit Site";
                this.getSite();
                this.getBuildings();
                this.new_building_url = "#" + this.$generatePath('locations.add_building', { site_id : this.site.site_id });
                this.building.edit_url = "#" + this.$generatePath('locations.add_building', { site_id : this.site.site_id });
            }
            if (this.site.site_id === 0) {
                this.isEditingSite = true;
            }
            //GLOBAL DEPENDENCIES
            this.site.organisation_id = organisation_id;
            this.translations = global_translations;
        },
        methods: {
            setState(state) {
                this.state = state;
            },
            resetState() {
                this.state = STATES.Ready;
                this.validation_errors = [];
                this.error_msg = false;
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
            cancelSite() {
                if (this.site.site_id === 0) {
                    window.location = this.index_url;
                } else {
                    this.resetState();
                    this.getSite();
                    this.isEditingSite = false;
                }
            },
            saveSite() {
                if(this.isReady()) {
                    if (this.site.site_id === 0) {
                        this.createSite();
                    } else {
                        this.editSite();
                    }
                }
            },
            getSite() {
                this.state = STATES.Loading;
                this.api.get('/locations/sites/' + this.site.site_id).then(response => {
                    this.site = response.json();
                    this.getProvinces();
                    this.resetState();
                });
            },
            createSite() {
                this.state = STATES.Creating;
                this.api.post('/locations/sites/', this.site).then(result => {
                    let response = result.json();
                    this.site = response.data;
                    this.resetState();
                    this.isEditingSite = false;
                }).catch(error => {
                    this.catchError(error);
                });
            },
            editSite() {
                this.isEditing();
                this.api.put('/locations/sites/' + this.site.site_id, this.site).then(result => {
                    this.resetState();
                    this.isEditingSite = false;
                }).catch(error => {
                    this.catchError(error);
                });
            },
            getCountries() {
                this.state = STATES.Loading;
                this.api.get('/locations/countries').then(response => {
                    if (response.json().success) {
                        this.countries = response.json().data;
                        this.resetState();
                    }
                });
            },
            getProvinces() {
                this.state = STATES.Loading;
                this.api.get('/locations/provinces/country/' + this.site.site_country_id).then(response => {
                    if (response.json().success) {
                        this.provinces = response.json().data;
                        this.resetState();
                    }
                });
            },
            getBuildings() {
                this.state = STATES.Loading;
                this.api.get('/locations/buildings/site/' + this.site.site_id).then(response => {
                    if (response.json().success) {
                        this.buildings = response.json().data;
                        for (let building of this.buildings) {
                            building.edit_url = "#" + this.$generatePath('locations.edit_building', { site_id : building.site_id, building_id: building.building_id });
                        }
                        this.resetState();
                    }
                });
            },
            deleteBuilding() {
                this.state = STATES.Loading;

                this.api.delete('/locations/buildings/' + this.selected_building).then(result => {
                    this.selected_building = [];
                    this.getBuildings();
                    this.resetState();
                    this.show_delete_modal = false
                }).catch(error => {
                    this.catchError(error);
                });
            },
            catchError(error) {
                // Failed response (status >= 400)
                // Check reason for failure
                switch(error.constructor) {
                    // API rejected the request or threw an error (e.g. 500)
                    case RestClient.Errors.RestError:
                        console.log('Caught RestError: ', error.response.json());
                        if (error.response.json()[0] === "validation_error") {
                            this.error_msg = error.response.json()[1];
                            this.validation_errors = error.response.json()[2];
                        }
                        // Check the reason for rejection
                        if(error.response.status === 404) {
                            console.log('Page not found!');
                        }
                        else if(error.response.status === 500) {
                            console.log('The server threw an error.');
                        }

                        break;

                    // HTTP request failed (e.g. could not connect to server)
                    case RestClient.Errors.NetworkError:
                        console.log('Caught NetworkError: ', error);
                        break;
                }
            }
        },
        components: {
            Modal,
        }
    };
</script>