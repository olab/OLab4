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

    function getPreference() {
        var preference = readCookie('community_' + COMMUNITY_ID + '_nav_preference');
        if (preference == 'collapsed') {
            collapseMenu();
        } else {
            expandMenu();
        }
    }

    function setPreference () {
        var preference = ($('#right-community-nav').hasClass('hide') ? 'expanded' : 'collapsed');
        createCookie('community_' + COMMUNITY_ID + '_nav_preference', preference, 365);
    }

    function collapseMenu() {
        $('#community-nav-collapse-toggle').removeClass('active');
        $('#main').removeClass('span6').addClass('span9');
        $('#right-community-nav').addClass('hide').removeClass('span3');
        $('#community-nav-menu-icon').removeClass('active');
    }

    function expandMenu() {
        $('#community-nav-collapse-toggle').addClass('active');
        $('#community-nav-menu-icon').addClass('active');
        $('#right-community-nav').addClass('span3').removeClass('hide');
        $('#main').removeClass('span9').addClass('span6');
    }

    $('.navbar .dropdown').hover(function() {
        $(this).find('.dropdown-menu').first().stop(true, true).delay().slideDown();
    }, function() {
        $(this).find('.dropdown-menu').first().stop(true, true).delay().slideUp();
    });

    $('.navbar .dropdown > a').click(function(){
        location.href = this.href;
    });

});