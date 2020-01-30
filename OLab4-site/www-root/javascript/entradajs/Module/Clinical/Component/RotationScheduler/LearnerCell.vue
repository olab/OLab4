<template>
    <div class="learner-cell">
        <img :src="learner.photo" class="img-circle" width="42">
        <h3>
            {{ learner.lastname }}, {{ learner.firstname }}
        </h3>
        <span class="student-number"> {{learner.number}} </span>
        <span v-if="levelCbme" class="learner-level-badge pull-right cbme">{{learner.level.stage_code}} &bull; {{learner.level.title}}</span>
        <span v-if="levelNoCbme" class="learner-level-badge pull-right ">{{learner.level.title}}</span>
    </div>
</template>

<script>
    module.exports = {
        name: 'learner-cell',
        props: {
            learner: {
                type: Object,
                default: () => {}
            }
        },
        data() {
            return {
                ENTRADA_URL: ENTRADA_URL
            };
        },
        computed: {
            levelCbme() {
                return this.learner.level && this.learner.level.cbme === '1';
            },
            levelNoCbme() {
                return this.learner.level && this.learner.level.cbme === '0';
            }
        }
    };
</script>

<style>
    .learner-cell {
        padding-top: 10px;
    }

    .learner-cell img {
        float: left;
        margin-right: 15px;
        width:40px;
        height:40px;
    }

    .learner-cell h3 {
        line-height: 20px;
        margin-bottom: 2px;
        margin-top: 0;
    }

    span.student-number {
        color: #8b959d;
        display: inline-block;
        font-size: 12px;
        font-weight: normal;
        float: left;
    }

    span.learner-level-badge {
        -webkit-border-radius: 5px;
        -moz-border-radius:    5px;
        border-radius:         5px;
        color: white;
        background-color: #999999;
        display: inline-block;
        font-size: 11px;
        font-weight: normal;
        margin-top: 2px;
        padding-bottom: 0;
        padding-left: 8px;
        padding-right: 8px;
        padding-top: 0;
        text-shadow: none;
    }

    span.learner-level-badge.cbme {
        background-color: #00a8be;
    }

</style>
