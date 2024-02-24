<?php 

// EJEMPLO DE ENVIAR CON WHATSAPP TEXTO
function waba_sendMessage($data){
    $data = array(
        "key" => $data["key"],
        "number" => $data["number"],
        "body" => $data["message"],
	    "whatsappId" => $data["waid"],
	    "webhook" => $data["webhook"]
    );

    /* Example using POST data 
    $data = array(
        "key" => "1785234f7f2d6050557762fe52c02e1bf4ca43e6",
        "number" => $_POST["number"],
        "body" => $_POST["body"]
    );
    */ 

    $ch = curl_init( $data["webhook"] );
    # Setup request to send json via POST.
    $payload = json_encode( $data );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    # Return response instead of printing.
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	// Bearer token
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer '. $data["key"]
	));

    # Send request.
    $result = curl_exec($ch);


    if($result === false){
        $error = curl_error($ch);
        //echo "Error in request:". $error;
        return array(
            "error_message"=>  "Error in request:". $error
        );
    }
    else{
        $result = json_decode($result, true);
        if(!empty($result["error"])){
            $error = $result["error"]["message"];
            return array(
                "error_message"=>  $error
            );
        }
        else{
            //echo "Mensaje enviado correctamente. Se regitró con el id:".$result["cid"];
            return array(
                "cid"=> $result["cid"]
            );
        }
    }
}
?>