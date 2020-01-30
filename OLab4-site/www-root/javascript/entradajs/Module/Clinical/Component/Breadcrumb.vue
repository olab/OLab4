<template>
    <ul class="breadcrumb">
        <li><span class="divider">/</span> <a :href="parameters.root_path">{{ parameters.root_name }}</a></li>
        <li v-for="route in routes" class="active">
            <span class="divider">/</span> <a v-if="route.path != '#'" :href="route.path">{{ route.title }}</a><span v-if="route.path == '#'">{{ route.title }}</span>
        </li>
    </ul>
</template>

<script>
    Array.prototype.filter = window.filter;
    const RestClient = use('EntradaJS/Http/RestClient');

    module.exports = {
        name: 'breadcrumb',
        props: {
            root_name: {
                type: String,
                required: true
            },
            root_path: {
                type: String,
                required: true
            },
            route_name: {
                type: String,
                required: true
            }
        },
        data () {
            return {
                parameters: {
                    root_name: "",
                    root_path: "",
                    route_name: ""
                },
                routes: [],
                route: {
                    path: "",
                    title: ""
                }
            }
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            this.parameters.root_name = this.root_name;
            this.parameters.root_path = "#" + this.$generatePath(this.root_path);
            this.parameters.route_name = this.route_name;
            this.generateRoutes();
        },
        methods: {
            generateRoutes() {
                let current_path = this.$getRoute().getPath().split("/");
                let tmp_name = "";
                let tmp_requirements = {};
                let tmp_routes = this.parameters.route_name.split(".");
                let tmp_pattern = "";
                let routes_length = tmp_routes.length;
                for (var i = 1; i  < routes_length; i++) {
                    tmp_name = tmp_routes.join(".");
                    tmp_pattern = this.$findRouteByName(tmp_name).getPattern();
                    tmp_requirements = this.$findRouteByName(tmp_name).getRequirements();
                    if (Object.keys(tmp_requirements).length === 0 && tmp_requirements.constructor === Object) {
                        this.routes.push({
                            path: "#" + (i != 1 ? this.$generatePath(tmp_name) : ""),
                            title: this.$findRouteByName(tmp_name).getMeta().page_name
                        });
                    } else {
                        this.routes.push({
                            path: "#" + (i != 1 ? this.$generatePath(tmp_name, this.findRequirements(tmp_requirements, tmp_pattern, current_path)) : ""),
                            title: this.$findRouteByName(tmp_name).getMeta().page_name
                        });
                    }
                    tmp_routes.pop();
                }
                this.routes.reverse();
            },
            findRequirements(requirements, pattern, current_path) {
                let data = {};
                let tmp_value = 0;
                for (let requirement in requirements) {
                    pattern.split("/").forEach(function(value, i){
                        if (value == "{" + requirement + "}") {
                            tmp_value = current_path[i+1];
                        }
                    });
                    data["" + requirement] = parseInt(tmp_value);
                }
                return data;
            }
        },
    };
</script>

<style>
</style>