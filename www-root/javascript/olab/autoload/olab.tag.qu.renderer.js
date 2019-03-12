// drop down question
Vue.component('olab-question-dropdown',
    {
        template:`<div class="questions olab-question-dropdown">
              <p>{{question.stem}}</p>
              <span>
                  <select v-bind:id="'QU_' + question.id" v-bind:name="'QU_' + question.id" v-on:change="changed">
                    <option></option>
                    <option v-for='response in question.QuestionResponses'
                            v-bind:value="'QU_' + question.id + '_' + response.id">{{response.response}}</option>
                  </select>
              </span>
              <span style='display:none' v-if='question.show_submit' v-bind:id="'submit_' + question.id">
                  <img v-bind:src="webRoot(null) + '/images/loading_small.gif'"/>
              </span>
              <input type="hidden"
                      v-bind:id="'QU_' + question.id + '_previous'"
                      v-bind:name="'QU_' + question.id + '_previous'" />
            </div>`,
        props:['question'],
        methods:{
            // provides the website root url from vue.js
            webRoot:function(event) {
                return this.$parent.websiteRoot;
            },
            changed:function(event) {

                var prevId = "#QU_" + this.question.id + "_previous";

                var payload = {};

                var idParts = event.target.value.split("_");
                payload.responseId = idParts[2];
                payload.questionId = this.question.id;
                payload.questionShowSubmit = this.question.show_submit;
                payload.previousId = null;

                // test if no 'previous' radio selection, if not then save the id of 
                // the response to the hidden input tag
                if (jQuery(prevId).val() !== "") {
                    payload.previousId = jQuery(prevId).val();
                }

                // set previous value to current selection
                jQuery(prevId).val(payload.responseId);

                this.$parent.onDropdownResponseChanged(payload);
            }
        }
    });

// drag and drop question
Vue.component('olab-question-draganddrop',
    {
        template:`<div class="questions olab-question-draganddrop">
              <p>{{question.stem}}</p>
              <ul class="drag-question-container ui-sortable navigation" v-bind:id="'qresponse_' + question.id" v-bind:name="'qresponse_' + question.id" v-bind:questionid="question.id">
                <li v-for='response in question.QuestionResponses' class='sortable' v-bind:responseid='response.id'>{{response.response}}</li>
              </ul>
            </div>`,
        props:['question'],
        mounted:function() {

            var sortableSettings = {
                axis:'y',
                cursor:'move'
            };

            // enable sorting capability
            jQuery("#qresponse_" + this.question.id).sortable(sortableSettings);
        }
    });

// slider question
Vue.component('olab-question-slider',
    {
        template:
            `<div class='questions olab-question-slider'><p>{{question.stem}}</p><div v-bind:id='id' v-bind:name='id'></div></div>`,
        props:['id', 'question'],
        mounted:function() {

            // convert the question settings from a string to an object
            var questionSettings = JSON.parse(this.question.settings);

            // build the slider settings object
            var sliderSettings = {
                value:Number(questionSettings.defaultValue),
                min:Number(questionSettings.minValue),
                max:Number(questionSettings.maxValue),
                step:Number(questionSettings.stepValue),
                orientation:questionSettings.orientation
            };

            // create the slider
            jQuery('#' + this.id).slider(sliderSettings);
        }
    });

// multi-line text entry question
Vue.component('olab-question-multilinetext',
    {
        template:`<div class='questions olab-question-area' >
               <p>{{question.stem}}</p>
               <textarea
                  autocomplete="off"
                  class="lightning-multi"
                  v-bind:cols="question.width"
                  v-bind:rows="question.height"
                  v-bind:id="'QU_' + question.id"
                  v-bind:name="'QU_' + question.id"> 
                </textarea>
            </div>`,
        props:['question']
    });

// multiple choice question
Vue.component('olab-question-multiplechoice',
    {
        template:`<div class='questions olab-question-multiplechoice' >
               <p>{{question.stem}}</p>
               <div class="questionResponses">
                 <ul class="navigation">

                   <li v-for='response in question.QuestionResponses'>
                     <span v-bind:id="'click' + response.id">
                       <input class="lightning-choice"
                              v-bind:id="'QU_' + question.id + '_' + response.id"
                              v-bind:name="'QU_' + question.id + '_' + response.id"
                              v-bind:question="response.question_id"
                              v-bind:response="response.id"
                              v-on:change="changed"
                              data-tries="-1"
                              v-bind:data-val="response.response"
                              type="checkbox">
                     </span>
                     <span class="text">{{response.response}}</span>
                     <span v-bind:id="'AJAXresponse' + response.id"></span>
                     <span style='display:none' v-if='question.show_submit' v-bind:id="'submit_' + question.id + '_' + response.id">
                         <img v-bind:src="webRoot(null) + '/images/loading_small.gif'"/>
                     </span>
                   </li>

                </ul>
              </div>
            </div>`,
        props:['question'],
        methods:{
            // provides the website root url from vue.js
            webRoot:function(event) {
                return this.$parent.websiteRoot;
            },
            changed:function(event) {

                var payload = {};

                var idParts = event.target.id.split("_");
                payload.responseId = idParts[2];
                payload.questionId = this.question.id;
                payload.questionShowSubmit = this.question.show_submit;
                payload.value = event.target.checked;

                this.$parent.onMultichoiceResponseChanged(payload);
            }
        }
    });

// radio button question
Vue.component('olab-question-radio',
    {
        template:`<div v-bind:id="'QU_' + question.id" class="questions olab-question-radio" >
              <p>{{question.stem}}</p>
              <div v-bind:class="'questionResponses questionForm_' + question.id + ' horizontal'">
                <ul class="navigation">

                  <li v-for='response in question.QuestionResponses'>
                    <span v-bind:id="'click' + response.id">
                      <input class="lightning-choice"
                             v-bind:id="'QU_' + question.id + '_' + response.id"
                             v-bind:name="'QU_' + question.id"
                             v-bind:response="response.id"
                             v-on:click="changed"
                             data-tries="1"
                             v-bind:data-val="response.response"
                             type="radio">
                    </span>
                    <span>{{response.response}}</span>
                    <span v-bind:id="'AJAXresponse' + response.id"></span>
                     <span style='display:none' v-if='question.show_submit' v-bind:id="'submit_' + question.id + '_' + response.id">
                         <img v-bind:src="webRoot(null) + '/images/loading_small.gif'"/>
                     </span>
                  </li>

                  <input type="hidden"
                         v-bind:id="'QU_' + question.id + '_previous'"
                         v-bind:name="'QU_' + question.id + '_previous'" />

                </ul>
              </div>
            </div>`,
        props:['question'],
        methods:{
            // provides the website root url from vue.js
            webRoot:function(event) {
                return this.$parent.websiteRoot;
            },
            changed:function(event) {

                var prevId = "#QU_" + this.question.id + "_previous";

                var payload = {};

                var idParts = event.target.id.split("_");
                payload.responseId = idParts[2];
                payload.questionId = this.question.id;
                payload.questionShowSubmit = this.question.show_submit;
                payload.value = event.target.value;
                payload.previousId = null;

                // test if no 'previous' radio selection, if not then save the id of 
                // the response to the hidden input tag
                if (jQuery(prevId).val() !== "") {
                    payload.previousId = jQuery(prevId).val();
                }

                // set previous value to current selection
                jQuery(prevId).val(payload.responseId);

                this.$parent.onRadioResponseChanged(payload);
            }
        }
    });


var OlabQUTag = function(olabNodePlayer) {

    var vm = this;
    vm.olabNodePlayer = olabNodePlayer;
    vm.OLAB_HTML_TAG = "olab-question-";

    var service = {
        render:render
    };

    return service;

    function render(wikiTagParts) {

        var element = "";

        try {

            var id = wikiTagParts[2];
            // get the view variable name so vue.js can bind to it
            var question = vm.olabNodePlayer.getQuestion(id);

            if (question == null) {
                throw new Error("not found.");
            }

            var questionType = question.item['QuestionTypes']['value'];
            var questionTag = vm.OLAB_HTML_TAG + questionType;
            var changeHandler = "";

            switch (questionType) {
                case "radio":
                    changeHandler = " v-bind:onChanged='onRadioResponseChanged' ";
                    break;
                default:
            }
            // build the vue.js component tag markup
            element = "<" +
                questionTag +
                " class='question " +
                questionTag +
                "'" +
                " id='" +
                questionType +
                id +
                "'" +
                changeHandler +
                " v-bind:question='question(\"" +
                id +
                "\")'></" +
                questionTag +
                ">";

            vm.olabNodePlayer.log.debug(element);

        } catch (e) {
            element = "[[" + wikiTagParts.join("") + " ERROR: '" + e.message + "']]";
            vm.olabNodePlayer.log.error(element);
        }

        return element;
    }

}

jQuery(window).ready(function($) {

    try {
        //alert("OlabCRTag");
    } catch (e) {
        alert(e.message);
    }

});