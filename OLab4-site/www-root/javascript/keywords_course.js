jQuery(document).ready(function($){
    jQuery('div.icon').click(function(){
        jQuery('input#search').focus();
    });
    
    jQuery("input#search").keyup(function(e) {
        // Remove Timeout
        clearTimeout($.data(this, 'timer'));
        jQuery("input#search").removeClass("loading");

        // Set Search String
        var search_string = $(this).val();

        // Do Search
        if (search_string === '') {
            jQuery("div#results").fadeOut();
        } else {
            jQuery("div#results").fadeIn();
            jQuery("input#search").addClass("loading");
            jQuery(this).data('timer', setTimeout(search, 250));
        };
    });
    
    function search() {
        jQuery('#search_results').show();
        var search_term = $('input#search').val();
        var course_id = 0;
        if ($('input#course_id').val()) {
            course_id = $('input#course_id').val();
        }
        
        if(search_term !== ''){
            jQuery.ajax({
                type: "POST",
                url: "../api/keywords-course.api.php",
                data: { search_term: search_term,
                        course_id: course_id},
                cache: false,
                success: function(html){
                    $("div#results").html(html);
                    $("input#search").removeClass("loading");
                }
            });
        }
        return false;    
    }
        
    jQuery('.keywords').hide();

    // Toggle to show/hide the MeSH search field.
    jQuery('.keyword-toggle').click(function(){
            var state = $(this).attr('keyword-toggle');
            if(state == "show"){
                    $(this).attr('keyword-toggle','hide');
                    $(this).html('<i class="icon-minus-sign icon-white"></i> Hide Keyword Search');
                    jQuery('.mapped_keywords').animate({width:'60%'},400,'swing',function(){
                            jQuery('.keywords').css({width:'0%'});
                            jQuery('.keywords').show();
                            jQuery('.keywords').animate({width:'38%'},400,'linear');
                    });										
            }
            else{
                    $(this).attr('keyword-toggle','show');
                    $(this).html('<i class="icon-plus-sign icon-white"></i> Show Keyword Search');
                    jQuery('.keywords').animate({width:'0%'},400,'linear',function(){
                            jQuery('.keywords').hide();
                            jQuery('.mapped_keywords').animate({width:'100%'},400,'swing');
                    });																				
            }
    });
});

var add_keywords = new Array();
var delete_keywords = new Array();

function addval(x){
    var dui1 = jQuery(x).attr("data-dui");
    var dname1 = jQuery(x).attr("data-dname");
    var id1 = jQuery(x).attr("id");

    jQuery('#right1 ul').append("<li data-dui='"+ dui1 +"' id='tagged_keyword' data-dname='"+ dname1 + "' onclick='removeval(this, \"" + dui1 + "\")'><i class=\"icon-minus-sign \"></i> "+ dname1 +"</li>");
    jQuery(x).remove();

    position = jQuery.inArray(dui1, delete_keywords);
    if (position > -1) {
        delete_keywords.splice(position, 1);
        jQuery("#delete_keywords").val(delete_keywords);
    }
    else {
        add_keywords.push(dui1);
        jQuery("#add_keywords").val(add_keywords);
    }
}

function removeval(y,val){
    var dui2 = val;
    var dname2 = jQuery(y).attr("data-dname");
    var id2 = jQuery(y).attr("id");

    jQuery('#results ul').append("<li data-dui='"+ dui2 +"' id='keyword'" +"' data-dname='"+ dname2 +"' onclick='addval(this)'><i class=\"icon-plus-sign \"></i> "+ dname2 +"</li>");
    jQuery(y).remove();
    
    position = jQuery.inArray(dui2, add_keywords);
    if (position > -1) {
        add_keywords.splice(position, 1);
        jQuery("#add_keywords").val(add_keywords);
    }
    else {
        delete_keywords.push(dui2);
        jQuery("#delete_keywords").val(delete_keywords);
    }
}

function hide_results() {
    jQuery("div#results").hide();
}