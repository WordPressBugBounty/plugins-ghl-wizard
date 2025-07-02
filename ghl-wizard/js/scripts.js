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
})(jQuery);
