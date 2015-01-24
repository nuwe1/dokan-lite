<?php
/**
 * Dokan settings Class
 *
 * @ weDves
 */


class Dokan_Template_Settings {

    public static function init() {
        static $instance = false;

        if ( !$instance ) {
            $instance = new Dokan_Template_Settings();
        }

        return $instance;
    }

    function ajax_settings() {

        if ( !wp_verify_nonce( $_POST['_wpnonce'], 'dokan_settings_nonce' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'dokan' ) );
        }

        $_POST['dokan_update_profile'] = '';

        $ajax_validate =  $this->validate();

        if ( is_wp_error( $ajax_validate ) ) {
            wp_send_json_error( $ajax_validate->errors );
        }

        // we are good to go
        $save_data = $this->insert_settings_info();

        wp_send_json_success( __( 'Your information has been saved successfully', 'dokan' ) );
    }


    function validate() {

        if ( !isset( $_POST['dokan_update_profile'] ) ) {
            return false;
        }

        if ( !wp_verify_nonce( $_POST['_wpnonce'], 'dokan_settings_nonce' ) ) {
            wp_die( __( 'Are you cheating?', 'dokan' ) );
        }

        $error = new WP_Error();

        $dokan_name = sanitize_text_field( $_POST['dokan_store_name'] );

        if ( empty( $dokan_name ) ) {
            $error->add( 'dokan_name', __( 'Dokan name required', 'dokan' ) );
        }

        if ( !empty( $_POST['setting_paypal_email'] ) ) {
            $email = filter_var( $_POST['setting_paypal_email'], FILTER_VALIDATE_EMAIL );
            if ( empty( $email ) ) {
                $error->add( 'dokan_email', __( 'Invalid email', 'dokan' ) );
            }
        }

        if ( $error->get_error_codes() ) {
            return $error;
        }

        return true;

    }

    function insert_settings_info() {

        $dokan_settings = array(
            'store_name'   => sanitize_text_field( $_POST['dokan_store_name'] ),
            'payment'      => array(),
            'phone'        => sanitize_text_field( $_POST['setting_phone'] ),
            'show_email'   => sanitize_text_field( $_POST['setting_show_email'] ),
            'address'      => strip_tags( $_POST['setting_address'] ),
            'banner'       => absint( $_POST['dokan_banner'] ),
            'gravatar'     => absint( $_POST['dokan_gravatar'] ),
        );

        if ( isset( $_POST['settings']['bank'] ) ) {
            $bank = $_POST['settings']['bank'];

            $dokan_settings['payment']['bank'] = array(
                'ac_name'   => sanitize_text_field( $bank['ac_name'] ),
                'ac_number' => sanitize_text_field( $bank['ac_number'] ),
                'bank_name' => sanitize_text_field( $bank['bank_name'] ),
                'bank_addr' => sanitize_text_field( $bank['bank_addr'] ),
                'swift'     => sanitize_text_field( $bank['swift'] ),
            );
        }

        if ( isset( $_POST['settings']['paypal'] ) ) {
            $dokan_settings['payment']['paypal'] = array(
                'email' => filter_var( $_POST['settings']['paypal']['email'], FILTER_VALIDATE_EMAIL )
            );
        }

        $store_id = get_current_user_id();
        update_user_meta( $store_id, 'dokan_profile_settings', $dokan_settings );

        do_action( 'dokan_store_profile_saved', $store_id, $dokan_settings );

        if ( ! defined( 'DOING_AJAX' ) ) {
            $_GET['message'] = 'profile_saved';
        }
    }

    function setting_field( $validate = '' ) {
        global $current_user;

        if ( isset( $_GET['message'] ) ) {
            ?>
            <div class="dokan-alert dokan-alert-success">
                <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
                <strong><?php _e( 'Your profile has been updated successfully!', 'dokan' ); ?></strong>
            </div>
            <?php
        }

        $profile_info   = dokan_get_store_info( $current_user->ID );

        $banner         = isset( $profile_info['banner'] ) ? absint( $profile_info['banner'] ) : 0;
        $storename      = isset( $profile_info['store_name'] ) ? esc_attr( $profile_info['store_name'] ) : '';
        $gravatar       = isset( $profile_info['gravatar'] ) ? absint( $profile_info['gravatar'] ) : 0;


        // bank
        $phone          = isset( $profile_info['phone'] ) ? esc_attr( $profile_info['phone'] ) : '';
        $show_email     = isset( $profile_info['show_email'] ) ? esc_attr( $profile_info['show_email'] ) : 'no';
        $address        = isset( $profile_info['address'] ) ? esc_textarea( $profile_info['address'] ) : '';


        if ( is_wp_error( $validate ) ) {
            $storename    = $_POST['dokan_store_name'];

            $phone        = $_POST['setting_phone'];
            $address      = $_POST['setting_address'];
        }
        ?>

            <div class="dokan-ajax-response"></div>

            <?php do_action( 'dokan_settings_before_form', $current_user, $profile_info ); ?>

            <form method="post" id="settings-form"  action="" class="dokan-form-horizontal">

                <?php wp_nonce_field( 'dokan_settings_nonce' ); ?>

                <div class="dokan-banner">

                    <div class="image-wrap<?php echo $banner ? '' : ' dokan-hide'; ?>">
                        <?php $banner_url = $banner ? wp_get_attachment_url( $banner ) : ''; ?>
                        <input type="hidden" class="dokan-file-field" value="<?php echo $banner; ?>" name="dokan_banner">
                        <img class="dokan-banner-img" src="<?php echo esc_url( $banner_url ); ?>">

                        <a class="close dokan-remove-banner-image">&times;</a>
                    </div>

                    <div class="button-area<?php echo $banner ? ' dokan-hide' : ''; ?>">
                        <i class="fa fa-cloud-upload"></i>

                        <a href="#" class="dokan-banner-drag dokan-btn dokan-btn-info"><?php _e( 'Upload banner', 'dokan' ); ?></a>
                        <p class="help-block"><?php _e( '(Upload a banner for your store. Banner size is (825x300) pixel. )', 'dokan' ); ?></p>
                    </div>
                </div> <!-- .dokan-banner -->

                <?php do_action( 'dokan_settings_after_banner', $current_user, $profile_info ); ?>

                <div class="dokan-form-group">
                    <label class="dokan-w3 dokan-control-label" for="dokan_store_name"><?php _e( 'Store Name', 'dokan' ); ?></label>

                    <div class="dokan-w5 dokan-text-left">
                        <input id="dokan_store_name" required value="<?php echo $storename; ?>" name="dokan_store_name" placeholder="store name" class="dokan-form-control" type="text">
                    </div>
                </div>

                <div class="dokan-form-group">
                    <label class="dokan-w3 dokan-control-label" for="dokan_gravatar"><?php _e( 'Profile Picture', 'dokan' ); ?></label>

                    <div class="dokan-w5 dokan-gravatar">
                        <div class="dokan-left gravatar-wrap<?php echo $gravatar ? '' : ' dokan-hide'; ?>">
                            <?php $gravatar_url = $gravatar ? wp_get_attachment_url( $gravatar ) : ''; ?>
                            <input type="hidden" class="dokan-file-field" value="<?php echo $gravatar; ?>" name="dokan_gravatar">
                            <img class="dokan-gravatar-img" src="<?php echo esc_url( $gravatar_url ); ?>">
                            <a class="dokan-close dokan-remove-gravatar-image">&times;</a>
                        </div>
                        <div class="gravatar-button-area<?php echo $gravatar ? ' dokan-hide' : ''; ?>">
                            <a href="#" class="dokan-gravatar-drag dokan-btn dokan-btn-default"><i class="fa fa-cloud-upload"></i> <?php _e( 'Upload Photo', 'dokan' ); ?></a>
                        </div>
                    </div>
                </div>

                <!-- payment tab -->
                <div class="dokan-form-group">
                    <label class="dokan-w3 dokan-control-label" for="dokan_setting"><?php _e( 'Payment Method', 'dokan' ); ?></label>
                    <div class="dokan-w6">

                        <?php $methods = dokan_withdraw_get_active_methods(); ?>
                        <div id="payment_method_tab">
                            <ul class="dokan_tabs" style="margin-bottom: 10px; margin-left:0px;">
                                <?php
                                $count = 0;
                                foreach ( $methods as $method_key ) {
                                    $method = dokan_withdraw_get_method( $method_key );
                                    ?>
                                    <li<?php echo ( $count == 0 ) ? ' class="active"' : ''; ?>><a href="#dokan-payment-<?php echo $method_key; ?>" data-toggle="tab"><?php echo $method['title']; ?></a></li>
                                    <?php
                                    $count++;
                                } ?>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tabs_container">

                                <?php
                                $count = 0;
                                foreach ( $methods as $method_key ) {
                                    $method = dokan_withdraw_get_method( $method_key );
                                    ?>
                                    <div class="tab-pane<?php echo ( $count == 0 ) ? ' active': ''; ?>" id="dokan-payment-<?php echo $method_key; ?>">
                                        <?php if ( is_callable( $method['callback'] ) ) {
                                            call_user_func( $method['callback'], $profile_info );
                                        } ?>
                                    </div>
                                    <?php
                                    $count++;
                                } ?>
                            </div> <!-- .tabs_container -->
                        </div> <!-- .payment method tab -->
                    </div> <!-- .dokan-w4 -->
                </div> <!-- .dokan-form-group -->

                <div class="dokan-form-group">
                    <label class="dokan-w3 dokan-control-label" for="setting_phone"><?php _e( 'Phone No', 'dokan' ); ?></label>
                    <div class="dokan-w5 dokan-text-left">
                        <input id="setting_phone" value="<?php echo $phone; ?>" name="setting_phone" placeholder="+123456.." class="dokan-form-control input-md" type="text">
                    </div>
                </div>

                <div class="dokan-form-group">
                    <label class="dokan-w3 dokan-control-label" for="setting_phone"><?php _e( 'Email', 'dokan' ); ?></label>
                    <div class="dokan-w5 dokan-text-left">
                        <div class="checkbox">
                            <label>
                                <input type="hidden" name="setting_show_email" value="no">
                                <input type="checkbox" name="setting_show_email" value="yes"<?php checked( $show_email, 'yes' ); ?>> <?php _e( 'Show email address in store', 'dokan' ); ?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="dokan-form-group">
                    <label class="dokan-w3 dokan-control-label" for="setting_address"><?php _e( 'Address', 'dokan' ); ?></label>
                    <div class="dokan-w5 dokan-text-left">
                        <textarea class="dokan-form-control" rows="4" id="setting_address" name="setting_address"><?php echo $address; ?></textarea>
                    </div>
                </div>

                <?php do_action( 'dokan_settings_form_bottom', $current_user, $profile_info ); ?>

                <div class="dokan-form-group">

                    <div class="dokan-w4 ajax_prev dokan-text-left" style="margin-left:24%;">
                        <input type="submit" name="dokan_update_profile" class="btn btn-primary" value="<?php esc_attr_e( 'Update Settings', 'dokan' ); ?>">
                    </div>
                </div>

            </form>

            <?php do_action( 'dokan_settings_after_form', $current_user, $profile_info ); ?>

                <script>
                    (function($){
                        $(document).ready(function(){
                            $('#payment_method_tab').easytabs();
                        });
                    })(jQuery)
                </script>

        <?php
    }

}
