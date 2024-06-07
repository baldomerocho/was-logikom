<?php
/**
* Plugin Name: WAS Logikom
* Plugin URI: https://logikom.com
* Description: Send notifications to WhatsApp. Powered by Logikom
* Version: 1.1.1
* Author: Baldomero Cho
* Author URI: https://datogedon.com
**/
include(__DIR__ . "/send.php");
include(__DIR__ . "/admin.php");

defined('ABSPATH') || die('No script kiddies please!');

if (is_admin()) {
	define('GH_REQUEST_URI', 'https://api.github.com/repos/%s/%s/releases');
	define('GHPU_USERNAME', 'baldomerocho');
	define('GHPU_REPOSITORY', 'was-logikom');

	include_once plugin_dir_path(__FILE__) . '/gh-plugin-updater/GhPluginUpdater.php';

	$updater = new GhPluginUpdater(__FILE__);
	$updater->init();
}



add_action("woocommerce_order_status_pending_to_processing_notification", "waba_neworder_hook");
add_action("woocommerce_order_status_pending_to_on-hold_notification", "waba_neworder_hook");

// Registrar la función de activación
register_activation_hook( __FILE__, 'waba_plugin_activation' );

// Función de activación del plugin
function waba_plugin_activation(): void {
	// Comprobar si ya existen los valores de opciones
	$existing_options = get_option( 'waba_plugin_options' );

	// Si no existen, establecer valores iniciales
	if ( false === $existing_options ) {
		$initial_options = array(
			'key' => ' ',
			'number' => '59899999999',
			'webhook' => 'https://was.logikom.uy/api/messages/send',
			'send_image' => true, // Valor booleano inicial
			'waid' => '1',
			'token' => '',
			'title' => 'Nuevo mensaje de contacto recibido'
		);

		// Guardar los valores iniciales de opciones
		update_option( 'waba_plugin_options', $initial_options );
	}
}

// Registrar la función de desactivación
register_deactivation_hook( __FILE__, 'waba_plugin_deactivation' );

// Función de desactivación del plugin
function waba_plugin_deactivation(): void {
	// Eliminar los valores de opciones
	delete_option( 'waba_plugin_options' );
}



function waba_neworder_hook($param): void {

    $options = get_option( 'waba_plugin_options' );
    if(!isset($options["key"]) || !isset($options["number"])) return ;

    $order = wc_get_order($param);
    $data = $order->get_data();
    $text = '';

    $n1 = $param;
    $text .= "*".$options["title"]."*\nNúmero pedido: *$n1*\n\n";

    $n1 = $data["billing"]["first_name"] . " " . $data["billing"]["last_name"];
    $text .= "Nombre: $n1\n";

    $n1 = $data["billing"]["company"];
    $text .= "Compañía: $n1\n";

    $n1 = $data["billing"]["address_1"] . " " . $data["billing"]["address_2"];
    $text .= "Dirección: $n1\n";

    $n1 = $data["billing"]["city"];
    $text .= "Ciudad: $n1\n";

    $n1 = $data["billing"]["state"];
    $text .= "Estado: $n1\n";

    $n1 = $data["billing"]["postcode"];
    $text .= "Código postal: $n1\n";

    $n1 = $data["billing"]["email"];
    $text .= "Correo electrónico: $n1\n";

    $n1 = $data["billing"]["phone"];
    $text .= "Teléfono: $n1\n\n";

    $text .= "*Productos*\n";
    $nitems = [];
    // Iterating through each WC_Order_Item_Product objects
    foreach ($order->get_items() as $item_key => $item ):
        $text .= "\n";
        $item_data = $item->get_data();
	    $nitems[] = $item_data;
		$n1 = $item_data["product_id"];
		$text .= "Producto ID: $n1\n";
        $n1 = $item_data["name"];
        $text .= "Nombre: $n1\n";
        $n1 = $item_data["quantity"];
        $text .= "Cantidad: $n1\n";
        $n1 = floatval($item_data["total"]);
        $text .= "Precio: $n1\n";
    endforeach;

    $n1 = floatval($data["total"]);
    $text .= "\n*Total*: $n1";



    $params = array(
        "key"=> $options["key"],
        "number" => $options["number"],
        "message" => $text,
	    "waid" => $options["waid"],
        "webhook" => $options["webhook"],
    );


    $result = waba_sendMessage($params);
    if(isset($result["error_message"])){

        // put to error to retry later
        $t1 = json_encode(array(
            "error_message" =>$result["error_message"],
            "message"=> $params["message"],
            "params"=> array(
                "key"=> $options["key"],
                "number" => $options["number"]
            )
        ));
        $md5 = md5(json_encode($text));
        file_put_contents(__DIR__ . "/data/Order-" . $md5. ".json", $t1);
    }

	if($options['send_image'] == "true") {
		foreach ($nitems as  $product ):
			$params = array(
				"key"=> $options["key"],
				"number" => $options["number"],
				"message" => "*".$product["name"]. "* | Pedido: *".$param."*",
				"waid" => $options["waid"],
				"webhook" => $options["webhook"],
				"medias" => get_attached_file(get_post_thumbnail_id($product["product_id"]))
			);
			waba_sendImage($params);
		endforeach;
	}

}

// attach contact email 
add_action( 'wpcf7_before_send_mail', 'waba_contact_send' );
add_action( 'wpforms_process_complete', 'waba_contact_send_wpforms', 10, 4 );


function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

function retry_send(){

    $options = get_option( 'waba_plugin_options' );
    if(!isset($options["key"]) || !isset($options["number"])) return ; 


    $files = scandir(__DIR__ . "/data");
    $count = 0;
    foreach($files as $file){
        if(endsWith($file, ".json")){
            $ufile = __DIR__ . "/data/$file";
            $str = file_get_contents($ufile);
            $data = json_decode($str, true);

            $params = array(
                "key"=> $options["key"],
                "number"=> $options["number"],
                "message"=> $data["message"],
                "waid" => $options["waid"],
                "webhook" => $options["webhook"]
            );
            $result = waba_sendMessage($params);
            if(isset($result["cid"])){
                unlink($ufile);
            }

            $count += 1;
            if($count == 4) break ;
        }
    }
}


function waba_contact_send( $contact_form ) {
    
    $options = get_option( 'waba_plugin_options' );
    if(!isset($options["key"]) || !isset($options["number"])) return ; 


    $submission = WPCF7_Submission::get_instance();
    if($submission ) {
        $posted_data = $submission->get_posted_data();

        $text = "*".$options["title"]."* \n";
        $post_id = $submission->get_meta('container_post_id');
        $form_id= $contact_form->id();
        $text .= "Post id: $post_id\nForm id: $form_id\n\n";
        $conversions = array(
            "your-name"=>"Nombre",
            "your-email"=>"Correo",
            "your-subject"=> "Asunto",
            "your-message"=> "Mensaje"
        );
        foreach($posted_data as $i => $item) {
            if(substr($i, 0, 1) != "_"){
                if(isset($conversions[$i])){
                    $n = $conversions[$i];
                    $m = $posted_data[$i];
                    $text .= "$n: ```$m```\n";    
                }else{
                    $m = $posted_data[$i];
                    $text .= "$i: ```$m```\n";   
                }
            }        
            // $array[$i] is same as $item
        }
        $params = array(
            "key"=> $options["key"],
            "number" => $options["number"],
            "message" => $text,
	        "waid" => $options["waid"],
            "webhook" => $options["webhook"]
        ); 

        
        $result = waba_sendMessage($params);
        if(isset($result["error_message"])){

            // put to error to retry later
            $t1 = json_encode(array(
                "error_message" =>$result["error_message"],
                "params"=> array(
                    "key"=> $options["key"],
                    "message"=> $text,
                    "number" => $options["number"],
	                "waid" => $options["waid"],
					"webhook" => $options["webhook"]
                )
            ));
            $md5 = md5(json_encode($text));
            file_put_contents(__DIR__ . "/data/" . $md5. ".json", $t1);

        }
    }
}
function waba_contact_send_wpforms( $fields, $entry, $form_data, $entry_id ) {
	$options = get_option( 'waba_plugin_options' );
	if(!isset($options["key"]) || !isset($options["number"])) return;

	$text = "*".$options["title"]."* \n";
	$post_id = isset($form_data['post_id']) ? $form_data['post_id'] : 'N/A';
	$form_id = $form_data['id'];
	$text .= "Post id: $post_id\nForm id: $form_id\n\n";

	$conversions = array(
		"your-name" => "Nombre",
		"your-email" => "Correo",
		"your-subject" => "Asunto",
		"your-message" => "Mensaje"
	);

	foreach($fields as $field) {
		$field_id = $field['id'];
		$field_value = $field['value'];
		$field_name = isset($conversions[$field_id]) ? $conversions[$field_id] : $field_id;
		$text .= "$field_name: ```$field_value```\n";
	}

	$params = array(
		"key" => $options["key"],
		"number" => $options["number"],
		"message" => $text,
		"waid" => $options["waid"],
		"webhook" => $options["webhook"]
	);

	$result = waba_sendMessage($params);
	if(isset($result["error_message"])) {
		// Put to error to retry later
		$t1 = json_encode(array(
			"error_message" => $result["error_message"],
			"params" => array(
				"key" => $options["key"],
				"message" => $text,
				"number" => $options["number"],
				"waid" => $options["waid"],
				"webhook" => $options["webhook"]
			)
		));
		$md5 = md5(json_encode($text));
		file_put_contents(__DIR__ . "/data/" . $md5 . ".json", $t1);
	}
}