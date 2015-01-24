;(function($){

    var product_gallery_frame;
    var product_featured_frame;
    var $image_gallery_ids = $('#product_image_gallery');
    var $product_images = $('#product_images_container ul.product_images');

    var Dokan_Editor = {

        /**
         * Constructor function
         */
        init: function() {

            product_type = 'simple';

            $('.product-edit-container').on('click', '._discounted_price', this.showDiscount);
            $('.product-edit-container').on('click', 'a.sale-schedule', this.showDiscountSchedule);
            $('.product-edit-container').on('click', 'input[type=checkbox]#_downloadable', this.downloadable);

            // gallery
            $('#dokan-product-images').on('click', 'a.add-product-images', this.gallery.addImages);
            $('#dokan-product-images').on( 'click', 'a.delete', this.gallery.deleteImage);
            this.gallery.sortable();

            // featured image
            $('.product-edit-container').on('click', 'a.dokan-feat-image-btn', this.featuredImage.addImage);
            $('.product-edit-container').on('click', 'a.dokan-remove-feat-image', this.featuredImage.removeImage);

            // download links
            $('.product-edit-container').on('click', 'a.upload_file_button', this.fileDownloadable);

            // post status change
            $('.dokan-toggle-sidebar').on('click', 'a.dokan-toggle-edit', this.sidebarToggle.showStatus);
            $('.dokan-toggle-sidebar').on('click', 'a.dokan-toggle-save', this.sidebarToggle.saveStatus);
            $('.dokan-toggle-sidebar').on('click', 'a.dokan-toggle-cacnel', this.sidebarToggle.cancel);

            // File inputs
            $('.product-edit-container').on('click', 'a.insert-file-row', function(){
                $(this).closest('table').find('tbody').append( $(this).data( 'row' ) );
                return false;
            });

            $('.product-edit-container').on('click', 'a.delete', function(){
                $(this).closest('tr').remove();
                return false;
            });
        },

        /**
         * Show hide product discount
         */
        showDiscount: function() {
            var self = $(this),
                checked = self.is(':checked'),
                container = $('.special-price-container');

            if (checked) {
                container.removeClass('dokan-hide');
            } else {
                container.addClass('dokan-hide');
            }
        },

        /**
         * Show/hide discount schedule
         */
        showDiscountSchedule: function(e) {
            e.preventDefault();

            $('.sale-schedule-container').slideToggle('fast');
        },
        downloadable: function() {
            if ( $(this).prop('checked') ) {
                $(this).closest('aside').find('.dokan-side-body').removeClass('dokan-hide');
            } else {
                $(this).closest('aside').find('.dokan-side-body').addClass('dokan-hide');
            }
        },

        gallery: {

            addImages: function(e) {
                e.preventDefault();

                var attachment_ids = $image_gallery_ids.val();

                if ( product_gallery_frame ) {
                    product_gallery_frame.open();
                    return;
                }

                // Create the media frame.
                product_gallery_frame = wp.media.frames.downloadable_file = wp.media({
                    // Set the title of the modal.
                    title: 'Add Images to Product Gallery',
                    button: {
                        text: 'Add to gallery',
                    },
                    multiple: true
                });

                // When an image is selected, run a callback.
                product_gallery_frame.on( 'select', function() {

                    var selection = product_gallery_frame.state().get('selection');

                    selection.map( function( attachment ) {

                        attachment = attachment.toJSON();

                        if ( attachment.id ) {
                            attachment_ids = attachment_ids ? attachment_ids + "," + attachment.id : attachment.id;

                            $product_images.append('\
                                <li class="image" data-attachment_id="' + attachment.id + '">\
                                    <img src="' + attachment.url + '" />\
                                    <ul class="actions">\
                                        <li><a href="#" class="delete" title="Delete image">Delete</a></li>\
                                    </ul>\
                                </li>');
                        }

                    } );

                    $image_gallery_ids.val( attachment_ids );
                });

                product_gallery_frame.open();
            },

            deleteImage: function(e) {
                e.preventDefault();

                $(this).closest('li.image').remove();

                var attachment_ids = '';

                $('#product_images_container ul li.image').css('cursor','default').each(function() {
                    var attachment_id = $(this).attr( 'data-attachment_id' );
                    attachment_ids = attachment_ids + attachment_id + ',';
                });

                $image_gallery_ids.val( attachment_ids );

                return false;
            },

            sortable: function() {
                // Image ordering
                $product_images.sortable({
                    items: 'li.image',
                    cursor: 'move',
                    scrollSensitivity:40,
                    forcePlaceholderSize: true,
                    forceHelperSize: false,
                    helper: 'clone',
                    opacity: 0.65,
                    placeholder: 'dokan-sortable-placeholder',
                    start:function(event,ui){
                        ui.item.css('background-color','#f6f6f6');
                    },
                    stop:function(event,ui){
                        ui.item.removeAttr('style');
                    },
                    update: function(event, ui) {
                        var attachment_ids = '';

                        $('#product_images_container ul li.image').css('cursor','default').each(function() {
                            var attachment_id = jQuery(this).attr( 'data-attachment_id' );
                            attachment_ids = attachment_ids + attachment_id + ',';
                        });

                        $image_gallery_ids.val( attachment_ids );
                    }
                });
            }

        },

        featuredImage: {

            addImage: function(e) {
                e.preventDefault();

                var self = $(this);

                if ( product_featured_frame ) {
                    product_featured_frame.open();
                    return;
                }

                product_featured_frame = wp.media({
                    // Set the title of the modal.
                    title: 'Upload featured image',
                    button: {
                        text: 'Set featured image',
                    }
                });

                product_featured_frame.on('select', function() {
                    var selection = product_featured_frame.state().get('selection');

                    selection.map( function( attachment ) {
                        attachment = attachment.toJSON();

                        console.log(attachment, self);
                        // set the image hidden id
                        self.siblings('input.dokan-feat-image-id').val(attachment.id);

                        // set the image
                        var instruction = self.closest('.instruction-inside');
                        var wrap = instruction.siblings('.image-wrap');

                        // wrap.find('img').attr('src', attachment.sizes.thumbnail.url);
                        wrap.find('img').attr('src', attachment.url);

                        instruction.addClass('dokan-hide');
                        wrap.removeClass('dokan-hide');
                    });
                });

                product_featured_frame.open();
            },

            removeImage: function(e) {
                e.preventDefault();

                var self = $(this);
                var wrap = self.closest('.image-wrap');
                var instruction = wrap.siblings('.instruction-inside');

                instruction.find('input.dokan-feat-image-id').val('0');
                wrap.addClass('dokan-hide');
                instruction.removeClass('dokan-hide');
            }
        },

        fileDownloadable: function(e) {
                e.preventDefault();

                var self = $(this),
                    downloadable_frame;

                if ( downloadable_frame ) {
                    downloadable_frame.open();
                    return;
                }

                downloadable_frame = wp.media({
                    title: 'Choose a file',
                    button: {
                        text: 'Insert file URL',
                    },
                    multiple: true
                });

                downloadable_frame.on('select', function() {
                    var selection = downloadable_frame.state().get('selection');

                    selection.map( function( attachment ) {
                        attachment = attachment.toJSON();

                        self.closest('tr').find('input.wc_file_url').val(attachment.url);
                    });
                });

                downloadable_frame.on( 'ready', function() {
                    downloadable_frame.uploader.options.uploader.params = {
                        type: 'downloadable_product'
                    };
                });

                downloadable_frame.open();
        },

        sidebarToggle: {
            showStatus: function(e) {
                var container = $(this).siblings('.dokan-toggle-select-container');

                if (container.is(':hidden')) {
                    container.slideDown('fast');

                    $(this).hide();
                }

                return false;
            },

            saveStatus: function(e) {
                var container = $(this).closest('.dokan-toggle-select-container');

                container.slideUp('fast');
                container.siblings('a.dokan-toggle-edit').show();

                // update the text
                var text = $('option:selected', container.find('select.dokan-toggle-select')).text();
                container.siblings('.dokan-toggle-selected-display').html(text);

                return false;
            },

            cancel: function(e) {
                var container = $(this).closest('.dokan-toggle-select-container');

                container.slideUp('fast');
                container.siblings('a.dokan-toggle-edit').show();

                return false;
            }
        }
    };

    // On DOM ready
    $(function() {
        Dokan_Editor.init();
    });

})(jQuery);