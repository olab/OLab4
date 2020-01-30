jQuery(function($) {
    getPreference();

    $('#community-nav-collapse-toggle').on('click', function (e) {
        setPreference();
        e.preventDefault();
        if ($('#right-community-nav').hasClass('hide')) {
            expandMenu();
        } else {
            collapseMenu();
        }
    });

    $('.navbar .dropdown').hover(function() {
        $(this).find('.dropdown-menu').first().stop(true, true).delay().slideDown();
    }, function() {
        $(this).find('.dropdown-menu').first().stop(true, true).delay().slideUp();
    });

    $('.navbar .dropdown > a').click(function(){
        location.href = this.href;
    });
});

function getPreference() {
    var preference = readCookie('community_' + COMMUNITY_ID + '_nav_preference');
    if (preference == 'collapsed') {
        collapseMenu();
    } else {
        expandMenu();
    }
}

function setPreference () {
    var preference = (jQuery('#right-community-nav').hasClass('hide') ? 'expanded' : 'collapsed');
    createCookie('community_' + COMMUNITY_ID + '_nav_preference', preference, 365);
}

function collapseMenu() {
    jQuery('#community-nav-collapse-toggle').removeClass('active');
    jQuery('.sideblock-content').removeClass('span6').addClass('span9');
    jQuery('.content').removeClass('span9').addClass('span12');
    jQuery('#right-community-nav').addClass('hide').removeClass('span3');
    jQuery('#community-nav-menu-icon').removeClass('active');
}

function expandMenu() {
    jQuery('#community-nav-collapse-toggle').addClass('active');
    jQuery('#community-nav-menu-icon').addClass('active');
    jQuery('#right-community-nav').addClass('span3').removeClass('hide');
    jQuery('.sideblock-content').removeClass('span9').addClass('span6');
    jQuery('.content').removeClass('span12').addClass('span9');
}