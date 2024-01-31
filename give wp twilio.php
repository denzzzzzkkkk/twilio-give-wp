<?php
/*
Plugin Name: GiveWP Twilio SMS
Description: Connect GiveWP with Twilio for SMS donations notifications.
Version: 1.0
Author: Your Name
*/

// Include the Twilio PHP library
require_once(plugin_dir_path(__FILE__) . 'twilio-php-main/src/Twilio/autoload.php');


// Define your Twilio credentials
define('TWILIO_ACCOUNT_SID', 'your_twilio_account_sid');
define('TWILIO_AUTH_TOKEN', 'your_twilio_auth_token');
define('TWILIO_PHONE_NUMBER', 'your_twilio_phone_number');

// Enqueue scripts only on GiveWP pages
function give_twilio_sms_enqueue_scripts() {
    if (is_singular('give_forms')) {
        wp_enqueue_script('give-twilio-sms', plugin_dir_url(__FILE__) . 'assets/js/give-twilio-sms.js', array('jquery'), '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'give_twilio_sms_enqueue_scripts');

// Hook into GiveWP donation complete event
function give_twilio_sms_send_notification($payment_id) {
    // Get donation details
    $payment_data = give_get_payment_meta($payment_id);

    // Get donor information
    $donor_name = $payment_data['user_info']['first_name'] . ' ' . $payment_data['user_info']['last_name'];
    $donor_phone = $payment_data['user_info']['phone'];

    // Customize your SMS message
    $sms_message = "Thank you, $donor_name, for your donation of $" . $payment_data['price'] . " to our cause!";

    // Send SMS using Twilio
    send_sms_via_twilio($donor_phone, $sms_message);
}
add_action('give_payment_complete', 'give_twilio_sms_send_notification');

// Function to send SMS via Twilio
function send_sms_via_twilio($to, $body) {
    $twilio_mode = 'test';  // Set this to 'live' for production

    // Use test credentials in test mode
    if ($twilio_mode == 'test') {
        // Your Account SID and Auth Token from twilio.com/console
        $account_sid = 'ACc8221e6caae950d54570cde698e89361';
        $auth_token = 'f0b2462208b4cba2e1d71008710a695b';
        // In production, these should be environment variables. E.g.:
        // $auth_token = $_ENV["TWILIO_AUTH_TOKEN"]

        // A Twilio number you own with SMS capabilities
        $twilio_number = "+15005550006";
    } else {
        // Use live credentials in live mode
        $account_sid = TWILIO_ACCOUNT_SID;
        $auth_token = TWILIO_AUTH_TOKEN;
        $twilio_number = TWILIO_PHONE_NUMBER;
    }

    $twilio = new Twilio\Rest\Client($account_sid, $auth_token);

    // Send the SMS
    $twilio->messages->create(
        $to,
        [
            'from' => $twilio_number,
            'body' => $body,
        ]
    );

}
// Add settings page to the admin menu
function give_twilio_sms_settings_menu() {
    add_menu_page(
        'Twilio SMS Settings',
        'Twilio SMS',
        'manage_options',
        'give_twilio_sms_settings',
        'give_twilio_sms_settings_page'
    );
}
add_action('admin_menu', 'give_twilio_sms_settings_menu');

// Callback function to display the settings page
function give_twilio_sms_settings_page() {
    ?>
    <div class="wrap">
        <h2>Twilio SMS Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('give_twilio_sms_settings_group'); ?>
            <?php do_settings_sections('give_twilio_sms_settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register and define settings
function give_twilio_sms_register_settings() {
    register_setting('give_twilio_sms_settings_group', 'twilio_account_sid');
    register_setting('give_twilio_sms_settings_group', 'twilio_auth_token');
    register_setting('give_twilio_sms_settings_group', 'twilio_phone_number');

    add_settings_section(
        'give_twilio_sms_section',
        'Twilio SMS Settings',
        'give_twilio_sms_section_callback',
        'give_twilio_sms_settings'
    );

    add_settings_field(
        'twilio_account_sid',
        'Twilio Account SID',
        'give_twilio_sms_field_account_sid',
        'give_twilio_sms_settings',
        'give_twilio_sms_section'
    );

    add_settings_field(
        'twilio_auth_token',
        'Twilio Auth Token',
        'give_twilio_sms_field_auth_token',
        'give_twilio_sms_settings',
        'give_twilio_sms_section'
    );

    add_settings_field(
        'twilio_phone_number',
        'Twilio Phone Number',
        'give_twilio_sms_field_phone_number',
        'give_twilio_sms_settings',
        'give_twilio_sms_section'
    );
}
add_action('admin_init', 'give_twilio_sms_register_settings');

// Callback functions for settings fields
function give_twilio_sms_section_callback() {
    echo '<p>Enter your Twilio credentials below:</p>';
}

function give_twilio_sms_field_account_sid() {
    $value = esc_attr(get_option('twilio_account_sid'));
    echo '<input type="text" name="twilio_account_sid" value="' . $value . '" />';
}

function give_twilio_sms_field_auth_token() {
    $value = esc_attr(get_option('twilio_auth_token'));
    echo '<input type="text" name="twilio_auth_token" value="' . $value . '" />';
}

function give_twilio_sms_field_phone_number() {
    $value = esc_attr(get_option('twilio_phone_number'));
    echo '<input type="text" name="twilio_phone_number" value="' . $value . '" />';
}
