<?php

// load assets/style.css
function waba_load_assets() {
    wp_enqueue_style( 'waba-style', plugin_dir_url( __FILE__ ) . 'assets/style.css' );
}

add_action( 'admin_enqueue_scripts', 'waba_load_assets' );

function waba_add_settings_page() {
    add_options_page( 'WAS', 'WAS Logikom', 'manage_options', 'waba_plugin', 'waba_plugin_settings_page' );
}

function waba_plugin_settings_page() { ?>
    <div class="was-wrap">
        <div class="was-header">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/logikom-logo.webp'; ?>" alt="WAS Logikom" class="was-logo">
            <span class="was-brand">WAS Logikom</span>
        </div>
        <p class="was-slogan">Configura aquí el API de WAS Logikom</p>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'waba_plugin_options' );
            do_settings_sections( 'waba_plugin' );
            ?>
            <button class="button button-primary button-large" type="submit">Save</button>
        </form>
    </div>
    <?php
}
add_action( 'admin_menu', 'waba_add_settings_page' );



function waba_register_settings() {
    register_setting( 'waba_plugin_options', 'waba_plugin_options',
        'waba_plugin_options_validate' );

    add_settings_field( 'waba_plugin_setting_title', 'Titulo del mensaje', 'waba_plugin_setting_title', 'waba_plugin', "waba_api_settings" );
    add_settings_section( 'waba_api_settings', 'API Credentials', 'waba_plugin_section_text', 'waba_plugin' );
    add_settings_field( 'waba_plugin_setting_webhook', 'Webhook', 'waba_plugin_setting_webhook', 'waba_plugin', "waba_api_settings" );
    add_settings_field( 'waba_plugin_setting_waid', 'WhatsApp ID', 'waba_plugin_setting_waid', 'waba_plugin', "waba_api_settings" );
    add_settings_field( 'waba_plugin_setting_api_key', 'API Key', 'waba_plugin_setting_api_key', 'waba_plugin', "waba_api_settings" );
    add_settings_field( 'waba_plugin_setting_number', 'Destinatario', 'waba_plugin_setting_number', 'waba_plugin', "waba_api_settings" );
    add_settings_field( 'waba_plugin_setting_send_image', 'Enviar imágen', 'waba_plugin_setting_send_image', 'waba_plugin', "waba_api_settings" );
    add_settings_field( 'waba_plugin_setting_token', 'GH Token', 'waba_plugin_setting_token', 'waba_plugin', "waba_api_settings" );

}

function waba_plugin_options_validate( $input ) {
    return $input;
}
function waba_plugin_setting_api_key() {
    $options = get_option( 'waba_plugin_options' );
    echo "<input style='width:100%;padding:0.5em;' id='waba_plugin_setting_api_key' name='waba_plugin_options[key]' type='text' value='" . esc_attr( $options['key'] ) . "' />";
}

function waba_plugin_setting_title() {
	$options = get_option( 'waba_plugin_options' );
	echo "<input style='width:100%;padding:0.5em;' id='waba_plugin_setting_title' name='waba_plugin_options[title]' type='text' value='" . esc_attr( $options['title'] ) . "' />";
}
function waba_plugin_setting_token() {
	$options = get_option( 'waba_plugin_options' );
	echo "<input style='width:100%;padding:0.5em;' id='waba_plugin_setting_token' name='waba_plugin_options[token]' type='text' value='" . esc_attr( $options['token'] ) . "' />";
}
function waba_plugin_setting_number(): void {
    $options = get_option( 'waba_plugin_options' );
    echo "<input style='width:100%;padding:0.5em;' id='waba_plugin_setting_number' name='waba_plugin_options[number]' type='text' value='" . esc_attr( $options['number'] ) . "' />";
}

function waba_plugin_setting_waid(): void {
	$options = get_option( 'waba_plugin_options' );
	echo "<input style='width:100%;padding:0.5em;' id='waba_plugin_setting_number' name='waba_plugin_options[waid]' type='text' value='" . esc_attr( $options['waid'] ) . "' />";
}

function waba_plugin_setting_webhook(): void {
	$options = get_option( 'waba_plugin_options' );
	echo "<input style='width:100%;padding:0.5em;' id='waba_plugin_setting_number' name='waba_plugin_options[webhook]' type='text' value='" . esc_attr( $options['webhook'] ) . "' />";
}
function waba_plugin_setting_send_image(): void {
	$options = get_option( 'waba_plugin_options' );

	echo "<select style='width:100%;padding:0.5em;' id='waba_plugin_setting_send_image' name='waba_plugin_options[send_image]'>";
    if ( $options['send_image'] == "true" ) {
	    echo "<option value='true' selected>Si</option>";
	    echo "<option value='false'>No</option>";
    } else {
	    echo "<option value='true'>Si</option>";
	    echo "<option value='false' selected>No</option>";
    }
    echo "</select>";

}
function waba_plugin_section_text(){
    echo 'Encuentra tus credenciales en WAS Logikom. <a href="https://was.logikom.uy" target="_blank">was.logikom.uy</a>';
}

add_action( 'admin_init', 'waba_register_settings' );