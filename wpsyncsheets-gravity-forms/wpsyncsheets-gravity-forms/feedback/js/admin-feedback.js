(function($) {
    $(document).ready(function() {
        let plugin_name = 'wpsyncsheets-gravity-forms';
        let plugin_slug = 'wpssg';
        $target = $('#the-list').find('[data-slug="' + plugin_name + '"] span.deactivate a');

        var plugin_deactivate_link = $target.attr('href');

        $($target).on('click', function(event) {
            event.preventDefault();
            $('#wpwrap').css('opacity', '0.5');  //remove single comment from this line need to check.

            $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").animate({
                opacity: 1
            }, 0, function() {
                $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").removeClass('hide-feedback-popup');
                $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper " + "." + plugin_slug + "-deactivation-container").addClass('deactivation-container-background');
                $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").find('#' + plugin_slug + '-plugin-submitNdeactivate').addClass(plugin_slug);
                $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").find('#' + plugin_slug + '-plugin-skipNdeactivate').addClass(plugin_slug);
            });
        });

        

        $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper ." + plugin_slug + "-deactivate-feedback-dialog-close").on('click', function(ev) {
            if ($("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper.hide-feedback-popup").length == 0) {
                ev.preventDefault();
                $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").animate({
                    opacity: 0
                }, 0, function() {
                    $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").addClass("hide-feedback-popup");
                    $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper " + "." + plugin_slug + "-deactivation-container").removeClass('deactivation-container-background');
                    $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").find('#' + plugin_slug + '-plugin-submitNdeactivate').removeClass(plugin_slug);
                    $('#wpwrap').css('opacity', '1');
                });

            }
        });
        $(document).on('click', function(e) {
            // If the click is NOT inside the popup
            if (
                !$(e.target).closest('.' + plugin_slug + '-deactivation-response').length &&
                $(e.target).closest('.' + plugin_slug + '-deactivation-container').length
            ) {
                if ($("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper.hide-feedback-popup").length == 0) {
                    e.preventDefault();
                    $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").animate({
                        opacity: 0
                    }, 0, function() {
                        $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").addClass("hide-feedback-popup");
                        $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper " + "." + plugin_slug + "-deactivation-container").removeClass('deactivation-container-background');
                        $("#" + plugin_slug + "-deactivate-feedback-dialog-wrapper").find('#' + plugin_slug + '-plugin-submitNdeactivate').removeClass(plugin_slug);
                        $('#wpwrap').css('opacity', '1');
                    });

                }
            }
        });

        $(document).on('click', '#' + plugin_slug + '-plugin-submitNdeactivate.' + plugin_slug, function(event) {
            let nonce = $("input[name='"+ plugin_slug +"-wpnonce']").val();
            let reason = $('.' + plugin_slug + '-deactivate-feedback-dialog-input:checked').val();
            let errormessage = '';
            if(reason == undefined){
                //errormessage = '<p class="">Please choose a reason to deactivate the plugin.</p>';
            }
            let message = '';            

            if (reason != undefined && reason.length > 0 && $('textarea[name="reason_' + reason + '"]').length > 0) {
                if ($('textarea.wpssg-feedback-text[name="reason_' + reason + '"]').val() == '') {
                    //errormessage += '<p class="">Please provide some extra information!</p>';
                } else {
                    message = $('textarea.wpssg-feedback-text[name="reason_' + reason + '"]').val();
                }
            }

            if (! $('#' + plugin_slug + '-GDPR-data-notice').is(":checked") ){
                errormessage += '<p class="">Please provide your consent to proceed.</p>';
            }

            if(errormessage){
                $('.' + plugin_slug + '-required-field-messages').html(errormessage);
                return false;
            }

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    'action': plugin_slug + '_submit_deactivation_response',
                    '_wpnonce': nonce,
                    'reason': reason,
                    'message': message,
                },
                beforeSend: function(data) {
                    $('#' + plugin_slug + '-plugin-submitNdeactivate').text('Deactivating...');
                    $('#' + plugin_slug + '-plugin-submitNdeactivate').attr('id', 'deactivating-plugin');
                    $('#' + plugin_slug + '-loader-wrapper').show();
                    $('#' + plugin_slug + '-plugin-skipNdeactivate').remove();
                },
                success: function(res) {
                    $('#' + plugin_slug + '-loader-wrapper').hide();
                    window.location = plugin_deactivate_link;
                    $('#deactivating-plugin').text('Deactivated');
                }
            })

        });

        $(document).on('click', '#' + plugin_slug + '-plugin-skipNdeactivate.' + plugin_slug , function() {
            $('#' + plugin_slug + '-plugin-skipNdeactivate').text('Deactivating...');
            $('#' + plugin_slug + '-plugin-skipNdeactivate').attr('id', 'deactivating-plugin');            
            window.location = plugin_deactivate_link;
        });

    });
})(jQuery);