<template>
    <div class="cohort-select" v-show="!isLocked">
        <div class="control-group">
            <div class="controls">
                <select id="learner-curriculum-period-select" class="span12" @change="changeList()" v-model="selectCohort">
                    <option value="0">All</option>
                    <optgroup v-for="(curriculum_period, curriculum_layout) in cohorts" :label="curriculum_layout">
                        <option v-for="period in curriculum_period" :value="period.cperiod_id">
                            {{ period.display_title }}
                        </option>
                    </optgroup>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
    Array.prototype.filter = window.filter;
    const RestClient = use('EntradaJS/Http/RestClient');

    module.exports = {
        name: 'cohort-select',
        props: ['value'],
        data() {
            return {
                cohorts: [],
                selectCohort: "0",
                isLocked: true,
                organisation_id: 0,
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);
            this.fetchCohorts();
        },
        methods: {
            fetchCohorts() {
                this.api.get('/clinical/curriculum_period').then(response => {
                    this.isLocked = false;
                    this.cohorts = response.json()["cperiods"];
                    this.organisation_id = response.json()["organisation"];

                    let preference = sessionStorage.getItem("clinical/" + this.organisation_id + "/cperiod_preference");
                    if (preference) {
                        this.selectCohort = preference;
                    }

                    this.$emit('input', this.selectCohort);
                    this.$emit('change');
                });

            },
            changeList() {
                sessionStorage.setItem("clinical/" + this.organisation_id + "/cperiod_preference", this.selectCohort);
                this.$emit('input', this.selectCohort);
                this.$emit('change');
            }
        }
    };
</script>

<style>
</style>