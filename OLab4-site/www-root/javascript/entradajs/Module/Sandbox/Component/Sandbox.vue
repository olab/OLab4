<template>
    <div class="sandbox-app">
        <h2 v-i18n>The Sandbox App</h2>

        <a href="#" v-if="permissions.create" @click.prevent="openCreateDialog()" class="btn btn-primary" v-i18n>new sandbox</a>

        <hr />

        <div class="sandbox-list" v-if="permissions.read">
            <div v-for="sandbox in sandboxes" class="sandbox">
                <header>ID#{{ sandbox.id }} - {{ sandbox.title }}</header>
                <main>{{ sandbox.description }}</main>
                <footer>
                    <a href="#" v-if="permissions.update" @click.prevent="openEditDialog(sandbox.id)" class="btn btn-warning" v-i18n>edit</a>
                    <a href="#" v-if="permissions.delete" @click.prevent="openDeleteDialog(sandbox.id)" class="btn btn-danger" v-i18n>delete</a>
                </footer>
            </div>
        </div>

        <div v-if="isLoading()">
            <p v-i18n>Loading, please wait...</p>
        </div>

        <simple-dialog v-if="isCreating()" :before-ok="this.validateForm" v-on:ok="createSandbox()" v-on:hide="resetState()" title="Create Sandbox">
            <div v-if="error" class="error-message">{{ error }}</div>

            <label>
                <span v-i18n>Sandbox Name</span><br />
                <input type="text" v-model="sandbox.title" placeholder="Sandbox Name" v-i18n />
            </label>

            <br />

            <label>
                <span v-i18n>Description</span><br />
                <textarea v-model="sandbox.description" placeholder="Description" v-i18n></textarea>
            </label>
        </simple-dialog>

        <simple-dialog v-if="isEditing()" :before-ok="this.validateForm" v-on:ok="editSandbox()" v-on:hide="resetState()" title="Edit Sandbox">
            <div v-if="error" class="error-message">{{ error }}</div>

            <label>
                <span v-i18n>Sandbox Name</span><br />
                <input type="text" name="name" v-model="sandbox.title" />
            </label>

            <br />

            <label>
                <span v-i18n>Description</span><br />
                <textarea name="description" v-model="sandbox.description"></textarea>
            </label>
        </simple-dialog>

        <simple-dialog v-if="isDeleting()" v-on:ok="deleteSandbox()" v-on:hide="resetState()" title="Delete Sandbox">
            <p v-i18n>Are you sure you want to delete this sandbox?</p>
        </simple-dialog>
    </div>
</template>

<script>
    const RestClient = use('EntradaJS/Http/RestClient');
    const SimpleDialog = use('./SimpleDialog.vue');
    const ValidationError = use('./../Model/ValidationError');

    const STATES = {
        Ready: 0,
        Loading: 1,
        Creating: 2,
        Editing: 3,
        Deleting: 4
    };

    module.exports = {
        name: 'sandbox-app',
        data() {
            return {
                state: 0,
                sandbox: {
                    id: 0,
                    title: null,
                    description: null
                },
                sandboxes: [],
                permissions: {
                    read: false,
                    create: false,
                    update: false,
                    delete: false
                },
                error: null
            };
        },
        created() {
            this.validators = {
                title: {
                    minLength: 3,
                    maxLength: 50,
                    required: true,
                    type: String
                },
                description: {
                    minLength: 5,
                    maxLength: 255,
                    required: true,
                    type: String
                }
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            this.fetchSandboxes();
        },
        methods: {
            setState(state) {
                this.state = state;
            },
            resetState() {
                this.state = STATES.Ready;
                this.sandbox = {
                    id: 0,
                    title: null,
                    description: null
                };
                this.error = null;
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
            openCreateDialog() {
                if(this.isReady()) {
                    this.setState(STATES.Creating);
                }
            },
            openEditDialog(sandbox_id) {
                if(this.isReady()) {
                    this.setState(STATES.Editing);

                    this.sandbox = Object.assign({}, this.getSandbox(sandbox_id));
                }
            },
            openDeleteDialog(sandbox_id) {
                if(this.isReady()) {
                    this.setState(STATES.Deleting);

                    this.sandbox = this.getSandbox(sandbox_id);
                }
            },
            fetchSandboxes() {
                this.state = STATES.Loading;

                this.api.get('/sandbox').then(response => {
                    this.permissions = response.json().current_user_can;
                    this.populateSandboxList(response.json().sandboxes.data);
                    this.resetState();
                });
            },
            populateSandboxList(sandboxes) {
                this.sandboxes = sandboxes;
            },
            createSandbox() {
                this.api.post('/sandbox', this.sandbox).then(result => {
                    this.fetchSandboxes();
                });
            },
            editSandbox() {
                this.api.put('/sandbox/' + this.sandbox.id, this.sandbox).then(result => {
                    this.fetchSandboxes();
                });
            },
            deleteSandbox() {
                this.api.delete('/sandbox/' + this.sandbox.id).then(result => {
                    this.fetchSandboxes();
                });
            },
            getSandbox(sandbox_id) {
                for(let sandbox of this.sandboxes) {
                    if(sandbox.id === sandbox_id) {
                        return sandbox;
                    }
                }

                return null;
            },
            validateForm() {
                try {
                    this.validate();
                }
                catch(error) {
                    if(error instanceof ValidationError) {
                        this.error = 'Oops, looks like an error in the ' + error.getFieldName() + ' field.';
                    }
                    else {
                        console.error('Unexpected error occurred.');
                    }

                    throw error;
                }
            },
            validate() {
                for(let name of Object.getOwnPropertyNames(this.validators)) {
                    let validator = this.validators[name];
                    let value = this.sandbox[name];

                    if(validator.required) {
                        if(!value) {
                            throw new ValidationError(name, 'required', value);
                        }
                    }

                    if(validator.type) {
                        if(typeof(value) !== validator.type.name.toLowerCase()) {
                            throw new ValidationError(name, 'type', value);
                        }
                    }

                    if(validator.minLength) {
                        if(value.toString().length < validator.minLength) {
                            throw new ValidationError(name, 'minLength', value);
                        }
                    }

                    if(validator.maxLength) {
                        if(value.toString().length > validator.maxLength) {
                            throw new ValidationError(name, 'maxLength', value);
                        }
                    }
                }
            }
        },
        components: {
            SimpleDialog
        }
    };
</script>

<style>
    .sandbox-app {

    }

    .sandbox-app .sandbox {
        border:1px solid #ccc;
        margin-bottom:15px;
    }

    .sandbox-app .sandbox > * {
        padding:5px;
    }

    .sandbox-app .sandbox > header {
        font-weight:bold;
    }

    .sandbox-app .sandbox > footer {
        text-align:right;
    }

    .sandbox-app input,
    .sandbox-app textarea {
        width:100%;
        box-sizing:border-box;
        font-size:1em;
        padding:8px;
    }

    .sandbox-app .error-message {
        background-color: rgba(255, 0, 0, 0.2);
        border:1px solid rgba(255, 0, 0, 0.52);
        border-radius:4px;
        font-size:12px;

        padding:3px 6px;
    }
</style>