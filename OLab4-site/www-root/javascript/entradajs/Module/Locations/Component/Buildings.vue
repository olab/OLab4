<template>
    <div class="locations-app">
        <h2 v-if="!isEditingBuilding">{{ building.building_name }}  <a class="btn btn-small space-left" @click="isEditingBuilding = true"><i class="fa fa-pencil"></i></a></h2>

        <div v-if="isEditingBuilding" class="form-horizontal" id="building_add_form">
            <h2>{{form_title}}</h2>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.site_id }">
                <label class="control-label form-required" v-i18n>Site</label>
                <div class="controls">
                    <select class="input-large" v-model="building.site_id" v-if="sites.length > 0">
                        <option v-for="site in sites" :value="site.site_id">{{ site.site_name }}</option>
                    </select>
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.building_name }">
                <label class="control-label form-required" v-i18n>Building Name</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="building.building_name" maxlength="60" />
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.building_code }">
                <label class="control-label form-required" v-i18n>Building Code</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="building.building_code" maxlength="10" />
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.building_address1 }">
                <label class="control-label form-required" v-i18n>Address Line 1</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="building.building_address1" maxlength="255" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label form-nrequired" v-i18n>Address Line 2</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="building.building_address2"/>
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.building_city }">
                <label class="control-label form-required" v-i18n>City</label>
                <div class="controls">
                    <input class="input-large" type="text" v-model="building.building_city" maxlength="35" />
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.building_country_id }">
                <label class="control-label form-required" v-i18n>Country</label>
                <div class="controls">
                    <select class="input-large" v-model="building.building_country_id" v-if="countries.length > 0" @change="getProvinces()">
                        <option v-for="country in countries" :value="country.countries_id">{{ country.country }}</option>
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" v-i18n>{{translations.Province}}</label>
                <div class="controls">
                    <select class="input-large" v-model="building.building_province_id" v-if="provinces.length > 0">
                        <option v-for="province in provinces" :value="province.province_id">{{ province.province }}</option>
                    </select>
                    <small class="muted" v-if="provinces.length == 0" v-i18n>(Select a country)</small>
                </div>
            </div>

            <div :class="{ 'control-group' : true, 'error' : validation_errors.building_postcode }">
                <label class="control-label form-required" v-i18n>{{translations.PostalCode}}</label>
                <div class="controls">
                    <input class="input-large" placeholder="123456" type="text" v-model="building.building_postcode" maxlength="7" />
                </div>
            </div>

            <div class="alert alert-error" v-show="error_msg">
                {{ error_msg }}
            </div>

            <div class="control-group">
                <div class="controls">
                    <button class="btn" @click="cancelBuilding" v-i18n>Cancel</button>
                    <button class="btn btn-primary" @click="saveBuilding" v-i18n>Save</button>
                </div>
            </div>
        </div>

        <div v-if="building.building_id !== 0">
            <div class="row-fluid space-above space-below">
                <a :href="index_url" class="btn btn-default pull-left">Back</a>
                <button type="button" v-if="permissions.create" class="btn btn-success pull-right space-below" @click="show_room_modal = true;"><i class="fa fa-plus"></i> Add New Room</button>
            </div>
            <h2>Rooms</h2>

            <table class="table table-bordered table-striped" cellspacing="0" cellpadding="1" border="0" summary="">
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
                    <th class="span1" v-i18n>Number</th>
                    <th class="title span3" v-i18n>Name</th>
                    <th class="span5" v-i18n>Description</th>
                    <th class="span2" v-i18n>Max Occupancy</th>
                </tr>
                </thead>
                <tbody class="room-list" v-if="permissions.read">
                <tr v-show="isLoading()">
                    <td colspan="5"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i> Loading</p></td>
                </tr>
                <tr v-show="!isLoading()" v-for="room in rooms" class="room">
                    <td><input type="checkbox" name="remove_ids[]" :value="`${ room.room_id }`" v-model="selected_room" /></td>
                    <td class="text-center"><a @click="getRoom(room.room_id)">{{ room.room_number }}</a></td>
                    <td><a @click="getRoom(room.room_id)">{{ room.room_name }}</a></td>
                    <td><p>{{ room.room_description }}</p></td>
                    <td class="text-center">{{ room.room_max_occupancy }}</td>
                </tr>
                <tr v-show="!isLoading()" v-if="rooms.length == 0">
                    <td colspan="5" v-i18n>There are no rooms created for this building</td>
                </tr>
                </tbody>
            </table>

            <a @click="show_delete_modal = true;" v-if="permissions.delete && !isLoading() && selected_room.length > 0" class="btn btn-danger" role="button" data-toggle="modal"><i class="fa fa-trash"></i> Delete Selected</a>

            <modal v-if="show_delete_modal" title="Delete Rooms" v-on:ok="deleteRoom" v-on:hide="show_delete_modal = false" savebutton="Delete" saveclass="btn-danger">
                <slot>
                    <p v-i18n>Are you sure to delete the selected rooms?</p>
                </slot>
            </modal>

            <modal v-if="show_room_modal" :title="room_form_title" v-on:ok="onSubmitRoom" v-on:hide=hideRoomModal savebutton="Save">
                    <slot>
                        <div v-if="isLoading()">
                            <p v-i18n>Loading, please wait...</p>
                        </div>

                        <form class="form-horizontal" v-if="isReady()" id="room_add_form" method="post" @submit.prevent="onSubmitRoom">
                            <div :class="{ 'control-group' : true, 'error' : validation_errors.room_name }">
                                <label class="control-label form-required" v-i18n>Room Name</label>
                                <div class="controls">
                                    <input class="input-large" type="text" v-model="room.room_name" maxlength="100" value=""/>
                                    <p v-if="validation_errors.room_name" class="error">{{validation_errors.room_name[0]}}</p>
                                </div>
                            </div>

                            <div :class="{ 'control-group' : true, 'error' : validation_errors.room_number }">
                                <label class="control-label form-required" v-i18n>Room Number</label>
                                <div class="controls">
                                    <input class="input-large" type="text" v-model="room.room_number" maxlength="20" value="" />
                                    <p v-if="validation_errors.room_number" class="error">{{validation_errors.room_number[0]}}</p>
                                </div>
                            </div>

                            <div :class="{ 'control-group' : true, 'error' : validation_errors.room_description }">
                                <label class="control-label form-required" v-i18n>Room Description</label>
                                <div class="controls">
                                    <textarea class="input-large" cols="40" rows="2" v-model="room.room_description" maxlength="255"></textarea>
                                    <p v-if="validation_errors.room_description" class="error">{{validation_errors.room_description[0]}}</p>
                                </div>
                            </div>

                            <div :class="{ 'control-group' : true, 'error' : validation_errors.room_max_occupancy }">
                                <label class="control-label" v-i18n>Room Max Occupancy</label>
                                <div class="controls">
                                    <input class="input-large" type="number" min="0" v-model="room.room_max_occupancy" value="" maxlength="4"/>
                                    <p v-if="validation_errors.room_max_occupancy" class="error">{{validation_errors.room_max_occupancy[0]}}</p>
                                </div>
                            </div>
                            <input type="hidden" v-model="room.building_id">
                            <div class="alert alert-error" v-show="modal_error_msg">
                                {{ modal_error_msg }}
                            </div>
                        </form>
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
                    building_address1: null,
                    building_address2: null,
                    building_city: null,
                    building_province_id: 0,
                    building_country_id: 0,
                    building_postcode: null
                },
                room: {
                    room_id: 0,
                    building_id: 0,
                    room_number: null,
                    room_name: null,
                    room_description: null,
                    room_max_occupancy: 0
                },
                rooms: [],
                sites: [],
                countries: [],
                provinces: [],
                permissions: {
                    read: true,
                    create: true,
                    update: true,
                    delete: true
                },
                form_title: "Add Building",
                room_form_title: "Add Room",
                selected_building: false,
                selected_room: [],
                index_url: "#" + this.$generatePath('locations.add_site'),
                validation_errors: [],
                error_msg: false,
                modal_error_msg: false,
                show_delete_modal: false,
                show_room_modal: false,
                translations: {},
                isEditingBuilding: false
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            this.getCountries();
            this.getSites();

            if (typeof this.$getRoute().getParameter('building_id') !== "undefined") {
                this.building.building_id = this.$getRoute().getParameter('building_id');
                this.room.building_id = this.building.building_id;
                this.form_title = "Edit Building";
                this.getBuilding();
                this.getRooms();
            }
            if (typeof this.$getRoute().getParameter('site_id') !== "undefined") {
                this.building.site_id = this.$getRoute().getParameter('site_id');
                this.index_url = "#" + this.$generatePath('locations.edit_site', {site_id : this.building.site_id});
                this.getSite();
            }
            if (this.building.building_id === 0) {
                this.isEditingBuilding = true;
            }
            //GLOBAL DEPENDENCIES
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
                this.modal_error_msg = false;
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
            hideRoomModal() {
                this.show_room_modal = false;
                this.resetState();
            },
            cancelBuilding() {
                if (this.building.building_id === 0) {
                    window.location = this.index_url;
                } else {
                    this.resetState();
                    this.getBuilding();
                    this.isEditingBuilding = false;
                }
            },
            saveBuilding() {
                if(this.isReady()) {
                    if (this.building.building_id === 0) {
                        this.createBuilding();
                    } else {
                        this.editBuilding();
                    }
                }
            },
            onSubmitRoom() {
                if(this.isReady()) {
                    if (this.room.room_id === 0) {
                        this.createRoom();
                    } else {
                        this.editRoom();
                    }
                }
            },
            getBuilding() {
                this.state = STATES.Loading;
                this.api.get('/locations/buildings/' + this.building.building_id).then(response => {
                    this.building = response.json();
                    this.getProvinces();
                    this.resetState();
                });
            },
            createBuilding() {
                this.state = STATES.Creating;
                this.api.post('/locations/buildings/', this.building).then(result => {
                    let response = result.json();
                    this.building = response.data;
                    this.resetState();
                    this.isEditingBuilding = false;
                }).catch(error => {
                    this.catchError(error);
                });
            },
            editBuilding() {
                this.isEditing();
                this.api.put('/locations/buildings/' + this.building.building_id, this.building).then(result => {
                    this.resetState();
                    this.isEditingBuilding = false;
                }).catch(error => {
                    this.catchError(error);
                });
            },
            createRoom() {
                this.state = STATES.Loading;
                this.api.post('/locations/rooms/', this.room).then(result => {
                    this.selected_room = [];
                    this.rooms = [];
                    this.room = {
                        building_id: this.building.building_id,
                        room_id: 0,
                        room_number: null,
                        room_name: null,
                        room_description: null,
                        room_max_occupancy: 0
                    };
                    this.show_room_modal = false;
                    this.getRooms();
                }).catch(error => {
                    this.catchError(error);
                });
            },
            editRoom() {
                this.state = STATES.Loading;
                this.api.put('/locations/rooms/' + this.room.room_id, this.room).then(result => {
                    this.selected_room = [];
                    this.room = {
                        building_id: this.building.building_id,
                        room_id: 0,
                        room_number: null,
                        room_name: null,
                        room_description: null,
                        room_max_occupancy: 0
                    };
                    this.show_room_modal = false;
                    this.getRooms();
                    this.room_form_title = "Add Room";
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
                this.api.get('/locations/provinces/country/' + this.building.building_country_id).then(response => {
                    if (response.json().success) {
                        this.provinces = response.json().data;
                        this.resetState();
                    }
                });
            },
            getRooms() {
                this.state = STATES.Loading;
                this.api.get('/locations/rooms/building/' + this.building.building_id).then(response => {
                    if (response.json().success) {
                        this.rooms = response.json().data;
                        this.resetState();
                    }
                });
            },
            getRoom(id) {
                this.show_room_modal = true;
                this.state = STATES.Loading;
                this.room_form_title = "Edit Room";
                this.api.get('/locations/rooms/' + id).then(response => {
                    if (response.json().success) {
                        this.room = response.json().data;
                        this.resetState();
                    } else {
                        this.room_form_title = "Add Room";
                    }
                });
            },
            deleteRoom() {
                this.state = STATES.Loading;
                this.api.delete('/locations/rooms/' + this.selected_room).then(result => {
                    this.room = {
                        building_id: this.building.building_id,
                        room_id: 0,
                        room_number: null,
                        room_name: null,
                        room_description: null,
                        room_max_occupancy: 0
                    };
                    this.selected_room = [];
                    this.show_delete_modal = false;
                    this.getRooms();
                    this.resetState();
                });
            },
            getSites() {
                this.state = STATES.Loading;

                this.api.get('/locations/sites/org/' + organisation_id).then(response => {
                    this.sites = response.json().sites;
                    this.resetState();
                });
            },
            getSite() {
                this.state = STATES.Loading;
                if (this.building.site_id) {
                    this.api.get('/locations/sites/' + this.building.site_id).then(response => {
                        this.site = response.json();
                        if (this.building.building_id === 0) {
                            this.building.building_city = this.site.site_city;
                            this.building.building_province_id = this.site.site_province_id;
                            this.building.building_country_id = this.site.site_country_id;
                            this.getProvinces();
                        }
                    });
                }
            },
            catchError(error) {
                // Failed response (status >= 400)
                // Check reason for failure
                switch(error.constructor) {
                    // API rejected the request or threw an error (e.g. 500)
                    case RestClient.Errors.RestError:
                        console.log('Caught RestError: ', error.response.json());
                        if (error.response.json()[0] === "validation_error") {
                            if (this.show_room_modal) {
                                this.modal_error_msg = error.response.json()[1];
                            } else {
                                this.error_msg = error.response.json()[1];
                            }
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
                this.state = STATES.Ready;
            }
        },
        components: {
            Modal,
        }
    };
</script>