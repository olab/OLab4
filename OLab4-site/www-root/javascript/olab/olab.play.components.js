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
