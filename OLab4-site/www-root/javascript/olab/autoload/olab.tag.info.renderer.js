// node link
Vue.component('olab-node-info',
    {
        template:`<div style='white-space:nowrap; display:inline;'>                    
                    <a href="#" id="infolink" onclick="">
                        <img v-bind:src="urlroot+'/images/olab/info_lblu.gif'" v-bind:alt='target' border='0'>
                    </a>
                  </div>`,
        props:['target','urlroot'],
        computed:{
            classes:function() {
                var classes = 'olab-node-link';
                return classes;
            }
        },
        data() {

            return {
                isButton:this.type === 'button'
            };

        },
        mounted: function() {

            var vm = this;
            jQuery("#infolink").on("click", function() {
                window.open(vm.target,
                            'info',
                            'toolbar=no, directories=no, location=no, status=no, menubat=no, resizable=no, scrollbars=yes, width=500, height=400');
                return false;
            });

        }

    });

var OlabINFOTag = function(olabNodePlayer) {

    var vm = this;
    vm.olabNodePlayer = olabNodePlayer;
    vm.OLAB_HTML_TAG = "olab-node-info";

    var service = {
        render:render
    };

    return service;

    function render(wikiTagParts) {

        var element = "";

        try {

            var id = wikiTagParts[2];

            // build the vue.js component tag markup
            element = "<" +
                      vm.OLAB_HTML_TAG +
                      " class='" + vm.OLAB_HTML_TAG + "'" +
                      " target='" + olabNodePlayer.moduleUrl +
                                  "/info#" +
                                  olabNodePlayer.map.id + ":" +
                                  olabNodePlayer.node.id + "'" +
                      " urlroot='" + olabNodePlayer.websiteUrl + 
                      "'></" + vm.OLAB_HTML_TAG + ">";

            vm.olabNodePlayer.log.debug(element);

        } catch (e) {
            element = "[[" + wikiTagParts.join("") + " ERROR: '" + e.message + "']]";
            vm.olabNodePlayer.log.error(element);
        }

        return element;
    }

};

jQuery(window).ready(function($) {

    try {
        //alert("OlabCRTag");
    } catch (e) {
        alert(e.message);
    }

});