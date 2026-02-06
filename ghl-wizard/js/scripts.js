(function($){
    $("#lcw-reset-password-form").on("submit", function(e){
        e.preventDefault();
        form = $(this);
        msg = $('#lcw-reset-password-message');
        msg.html('');

        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        const nonce = $('#preset_nonce').val();
        const action = $('#action').val();
        const setTags = $('#set_tags').val();
        const removeTags = $('#remove_tags').val();
        const successMessage = $('#success_message').val();
        const redirectTo = $('#redirect_to').val();

        data = {
            action: action,
            nonce: nonce,
            password: password,
            confirm_password: confirmPassword,
            set_tags: setTags,
            remove_tags: removeTags,
            success_message: successMessage,
            redirect_to: redirectTo
        };

        console.log(data);

        $.ajax({
            url: hlwpw_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                console.log(response);
                msg.html(response.message);

                if (response.redirect) {
                    setTimeout(function () {
                        window.location.href = response.redirect;
                    }, 2000);
                }
            },
            error: function() {
                console.log('Something went wrong.');
            }
        });
        
    });

    /* // Autologin by AJAX on page load if the URL contains ?lcw_auto_login=1
    if (window.location.search.includes('lcw_auto_login=1')) {

        const email = window.location.search.includes('email=') ? window.location.search.split('email=')[1].split('&')[0] : '';
        const redirect_to = window.location.search.includes('redirect_to=') ? window.location.search.split('redirect_to=')[1].split('&')[0] : '';
        const lcw_auth_key = window.location.search.includes('lcw_auth_key=') ? window.location.search.split('lcw_auth_key=')[1].split('&')[0] : '';
        const first_name = window.location.search.includes('first_name=') ? window.location.search.split('first_name=')[1].split('&')[0] : '';
        const last_name = window.location.search.includes('last_name=') ? window.location.search.split('last_name=')[1].split('&')[0] : '';
        const id = window.location.search.includes('id=') ? window.location.search.split('id=')[1].split('&')[0] : '';
        const set_tags = window.location.search.includes('set_tags=') ? window.location.search.split('set_tags=')[1].split('&')[0] : '';
        const remove_tags = window.location.search.includes('remove_tags=') ? window.location.search.split('remove_tags=')[1].split('&')[0] : '';
        
        const data = {
            action: 'lcw_auto_login_ajax',
            email: email,
            redirect_to: redirect_to,
            lcw_auth_key: lcw_auth_key,
            first_name: first_name,
            last_name: last_name,
            id: id,
            set_tags: set_tags,
            remove_tags: remove_tags
        };
        console.log('Autologin data:', data);

        $.ajax({
            url: hlwpw_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('Autologin response:', response);
                if (response.success) {
                    window.location.href = response.data.redirect || '/';
                }
                if (false === response.success) {
                    $('body').prepend('<div class="hlwpw-error">' + response.data.message + '</div>');                
                }
                if( true === response.success ) {
                    $('body').prepend('<div class="hlwpw-success">' + response.data.message + '</div>');                
                }
            },
            error: function(response) {
                console.log('Autologin error:', response);                
            }
        });
    } */
})(jQuery);
