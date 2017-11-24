Vue.component('olab-brand',
{
  props: ['webroot'],
  template: `<div>
               <a v-bind:href='webroot'>
                 <img v-bind:src='imageUrl' height='20' width='118' alt='OpenLabyrinth' border='0' />
               </a>
               <h5>OpenLabyrinth is an open source educational pathway system</h5>
             </div>`,
  computed: {

    imageUrl: function() {
      return this.webroot + '/images/olab/openlabyrinth-powerlogo-wee.jpg';
    }
  }

});

Vue.component('olab-paragraph',
{
  props: {
    header: { default: '' }
  },
  template: `<div class="olab-headed-paragraph">
               <h4 v-show="header.length>0">{{header}}</h4>
               <slot></slot>
               <br/>
             </div>`
});

Vue.component('olab-review-pathway', {

});

// multi-line text entry question
Vue.component('olab-question-multilinetext', {
  template: `<div class='questions olab-question-area' >
               <p>{{question.stem}}</p>
               <textarea
                  autocomplete="off"
                  class="lightning-multi"
                  v-bind:cols="question.width"
                  v-bind:rows="question.height"
                  v-bind:id="'QU_' + question.id"
                  v-bind:name="'QU_' + question.id">
                </textarea>
                <p v-if='question.show_submit'>
                   <span v-bind:id="'questionSubmit' + question.id" style='display: none; font-size: 12px;'>Answer has been sent.</span>
                   <button onclick="jQuery(this).hide();jQuery("#questionSubmit2911").show();jQuery("#qresponse_2911").attr("readonly", "readonly");">Submit</button>
                </p>
            </div>`,
  props: ['question']
});

// multiple choice question
Vue.component('olab-question-multiplechoice', {
  template: `<div class='questions olab-question-multiplechoice' >
               <p>{{question.stem}}</p>
               <div class="questionResponses">
                 <ul class="navigation">

                   <li v-for='response in question.QuestionResponses'>
                     <span v-bind:id="'click' + response.id">
                       <input class="lightning-choice"
                              v-bind:id="'QU_' + question.id + '_' + response.id"
                              v-bind:question="response.question_id"
                              v-bind:response="response.id"
                              v-on:change="changed"
                              data-tries="-1"
                              v-bind:data-val="response.response"
                              type="checkbox">
                     </span>
                     <span class="text">{{response.response}}</span>
                     <span v-bind:id="'AJAXresponse' + response.id"></span>
                   </li>

                </ul>
              </div>
            </div>`,
  props: ['question'],
  methods: {
    changed: function (event) { 

      var payload = {};

      var idParts = event.target.id.split("_");
      payload.responseId = idParts[2];
      payload.questionId = this.question.id;
      payload.value = event.target.checked;

      this.$parent.onMultichoiceResponseChanged(payload);
    }
  }
});

// radio button question
Vue.component('olab-question-radio', {
  template: `<div v-bind:id="'QU_' + question.id" class="questions olab-question-radio" >
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
                  </li>

                  <input type="hidden"
                         v-bind:id="'QU_' + question.id + '_previous'"
                         v-bind:name="'QU_' + question.id + '_previous'" />

                </ul>
              </div>
            </div>`,
  props: ['question'],
  methods: {

    changed: function (event) {

      var prevId = "#QU_" + this.question.id + "_previous";

      var payload = {};

      var idParts = event.target.id.split("_");
      payload.responseId = idParts[2];
      payload.questionId = this.question.id;
      payload.value = event.target.value;
      payload.previousId = null;

      // test if no 'previous' radio selection, if not then save the id of 
      // the response to the hidden input tag
      if (jQuery(prevId).val() !== "") {

        payload.previousId = jQuery(prevId).val();

      }

      // set previous value to current selection
      jQuery(prevId).val(payload.responseId);

      this.$parent.onRadioResponseChanged( payload );
    }
  }
});

// constant
Vue.component('olab-constant', {
  template: `<span v-html='constant.value'></span>`,
  props: ['constant']
});

// counter
Vue.component('olab-counter', {
  template: `<span v-html='counter.value'></span>`,
  props: ['counter']
});

// counters
Vue.component('olab-counters', {
  template: `<ul class="navigation">
              <li v-for='counter in server'>
                <a data-toggle="modal" href="#" data-target="#counter-debug">{{counter.name}}</a>&nbsp;{{counter.value}}
              </li>
              <li v-for='counter in map'>
                <a data-toggle="modal" href="#" data-target="#counter-debug">{{counter.name}}</a>&nbsp;{{counter.value}}
              </li>
              <li v-for='counter in node'>
                <a data-toggle="modal" href="#" data-target="#counter-debug">{{counter.name}}</a>&nbsp;{{counter.value}}
              </li>

            </ul>`,
  props: ['server', 'map', 'node']
});

// audio
Vue.component('olab-audio', {
  template: `<audio v-bind:src="file.content" controls="" preload="auto" autoplay="autoplay" autobuffer=""></audio>`,
  props: ['file']
});

// image
Vue.component('olab-image', {
  template: `<img v-bind:src='src' v-bind:alt='file.name'></img>`,
  props: ['src', 'file']
});

// image
Vue.component('olab-video', {
  template: `<video controls><source v-bind:type='file.mimi' v-bind:src='file.content'></video>`,
  props: ['file']
});

// file
Vue.component('olab-download', {
  template: `<div>
               <div style="display:none">
                 <a v-bind:id="'file-link-' + file.id"></a>
               </div>
               <a v-bind:onclick="'olab.downloadFile(' + file.id + ')'">
                {{file.name}}
               </a>
             </div>`,
  props: ['file']
});

// drop down question
Vue.component('olab-question-dropdown', {
  template: `<div class="questions olab-question-dropdown">
              <p>{{question.stem}}</p>
              <select v-bind:id="'QU_' + question.id" v-on:change="changed">
                <option></option>
                <option v-for='response in question.QuestionResponses'
                        v-bind:value="'QU_' + question.id + '_' + response.id">{{response.response}}</option>
              </select>

              <input type="hidden"
                      v-bind:id="'QU_' + question.id + '_previous'"
                      v-bind:name="'QU_' + question.id + '_previous'" />
            </div>`,
  props: ['question'],
  methods: {
    changed: function (event) {

      var prevId = "#QU_" + this.question.id + "_previous";

      var payload = {};

      var idParts = event.target.value.split("_");
      payload.responseId = idParts[2];
      payload.questionId = this.question.id;
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
Vue.component('olab-question-draganddrop', {
  template: `<div class="questions olab-question-draganddrop">
              <p>{{question.stem}}</p>
              <ul class="drag-question-container ui-sortable navigation" v-bind:id="'qresponse_' + question.id" v-bind:questionid="question.id">
                <li v-for='response in question.QuestionResponses' class='sortable' v-bind:responseid='response.id'>{{response.response}}</li>
              </ul>
            </div>`,
  props: ['question'],
  mounted: function() {

    var sortableSettings = {
      axis:'y',
      cursor:'move'
  };

    // enable sorting capability
    jQuery("#qresponse_" + this.question.id).sortable( sortableSettings );
  }
});

// slider question
Vue.component('olab-question-slider', {

  template: `<div class='questions olab-question-slider'><p>{{question.stem}}</p><div v-bind:id='id' ></div></div>`,
  props:['id','question'],
  mounted:function() {

    // convert the question settings from a string to an object
    var questionSettings = JSON.parse(this.question.settings);

    // build the slider settings object
    var sliderSettings = {
      value: Number(questionSettings.defaultValue),
      min: Number(questionSettings.minValue),
      max: Number(questionSettings.maxValue),
      step: Number(questionSettings.stepValue),
      orientation: questionSettings.orientation
    };
    
    // create the slider
    jQuery('#' + this.id).slider( sliderSettings );
  }
});

// node link
Vue.component('olab-node-link', {

  template: `<div class='olab-node-link'>
               <a v-bind:class ='classes' v-bind:onclick='"olab.navigate(" + link.DestinationNode.id + ");"'>{{link.DestinationNode.title}}</a>
             </div>`,
  props: ['link'],
  computed: {
    classes: function() {
      var classes= 'olab-node-link';
      if (this.link.link_style_id === 5) {
        classes += " btn";
      }
      return classes;
    }
  },
  data() {

    return {
      isButton: this.type === 'button'
    };

  }

});