(function (mw, $) {
    'use strict';
    $('body').append('<div class="ajax-spinner hidden"><div class="loader"></div></div>');
    $('.result-item').on('change', function () {
        var textarea = $(this).parent('li').find('.comment-box');
        var checked = $(this).prop('checked');
        if (checked) {
            textarea.removeClass('hidden');
        } else {
            textarea.addClass('hidden');
        }
    });

    $('.delete-item').on('click', function () {
        if (confirm("Are you sure you want to delete this?")) {
            $('.ajax-spinner').removeClass('hidden');
            var item = $(this).closest('li');
            var id = item.data('id');

            mw.loader.using('mediawiki.api', function () {
                (new mw.Api()).get({
                    action: 'delete_link',
                    id: id
                }).done(function (data) {
                    $('.ajax-spinner').addClass('hidden');
                    window.location.reload();
                });
            });
        }
    });


    $('.update-item').on('click', function () {
        $('.ajax-spinner').removeClass('hidden');
        var item = $(this).closest('li');
        var id = item.data('id');
        var comment = item.find('.comment-box').val();
        mw.loader.using('mediawiki.api', function () {
            (new mw.Api()).post({
                action: 'update_link',
                id: id,
                comment: comment
            }).done(function (data) {
                $('.ajax-spinner').addClass('hidden');

            });
        });

    });
}(mediaWiki, jQuery));