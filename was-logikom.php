<?php
/**
* Plugin Name: WAS Logikom
* Plugin URI: https://logikom.com
* Description: Send notifications to WhatsApp. Powered by Logikom
* Version: 1.0
* Author: Baldomero Cho
* Author URI: https://datogedon.com
**/
include(__DIR__ . "/send.php");
include(__DIR__ . "/admin.php");



add_action("woocommerce_order_status_pending_to_processing_notification", "waba_neworder_hook");
add_action("woocommerce_order_status_pending_to_on-hold_notification", "waba_neworder_hook");



function waba_neworder_hook($param){
    
    $options = get_option( 'waba_plugin_options' );
    if(!isset($options["key"]) || !isset($options["number"])) return ; 
    
    $order = wc_get_order($param);
    $data = $order->get_data();
    $text = '';
    
    $n1 = $param;
    $text .= "*Nuevo pedido recibido*\nNúmero pedido: *$n1*\n\n";

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
        $item_data    = $item->get_data();
        //$nitems[] = $item_data;

        $n1 = $item_data["name"];
        $text .= "Nombre: $n1\n";
        $n1 = $item_data["quantity"];
        $text .= "Cantidad: $n1\n";
        $n1 = $item_data["total"];
        $text .= "Precio: $n1\n";

    endforeach;

    $n1 = $data["total"];
    $text .= "\n*Total*: $n1";



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
            "message"=> $params["message"],
            "params"=> array(
                "key"=> $options["key"],
                "number" => $options["number"]
            )
        ));
        $md5 = md5(json_encode($text));
        file_put_contents(__DIR__ . "/data/Order-" . $md5. ".json", $t1);
    }

}

/*
function waba_neworder_hook1($param){
    ob_start();
    var_dump($param);
    $str = ob_get_clean();
    file_put_contents(__DIR__ . "/data2/data-3.json", $str);

    ob_start();
    $order = wc_get_order($param);
    var_dump($order);
    $str = ob_get_clean();
    file_put_contents(__DIR__ . "/data2/data-4.json", $str);
}*/


// attach contact email 
add_action( 'wpcf7_before_send_mail', 'waba_contact_send' );


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

        $text = "*Nuevo mensaje de contacto recibido* \n";
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
                "message"=> $params["message"],
                "params"=> array(
                    "key"=> $options["key"],
                    "number" => $options["number"],
                )
            ));
            $md5 = md5(json_encode($text));
            file_put_contents(__DIR__ . "/data/" . $md5. ".json", $t1);

        }
    }

    
}
