<?php 

// EJEMPLO DE ENVIAR CON WHATSAPP TEXTO
function waba_sendMessage($data): array {
    $body = array(
        "number" => $data["number"],
        "body" => $data["message"],
	    "whatsappId" => $data["waid"],
    );

	$data = array(
		"key" => $data["key"],
		"webhook" => $data["webhook"]
	);

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $data["webhook"],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => json_encode($body),
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$data["key"]
		),
	));

	$result = curl_exec($curl);

	curl_close($curl);
	if($result === false){
        $error = curl_error($curl);
        //echo "Error in request:". $error;
		error_log("Error in request:". $error);
        return array(
            "error_message"=>  "Error in request:". $error
        );
    }
    else{
        $result = json_decode($result, true);
        if(!empty($result["error"])){
            $error = $result["error"]["message"];
			error_log($error);
            return array(
                "error_message"=>  $error
            );
        }
        else{
            return array(
                "cid"=> $result["cid"]
            );
        }
    }
}

// EJEMPLO DE ENVIAR CON WHATSAPP IMAGEN
function waba_sendImage($data): void {
	$curl = curl_init();

	$data = array(
		"key" => $data["key"],
		"webhook" => $data["webhook"],
		"waid" => $data["waid"],
		"number" => $data["number"],
		"medias" => $data["medias"],
		"message" => $data["message"]
	);

	curl_setopt_array( $curl, array(
		CURLOPT_URL            => $data["webhook"],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING       => '',
		CURLOPT_MAXREDIRS      => 10,
		CURLOPT_TIMEOUT        => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST  => 'POST',
		CURLOPT_POSTFIELDS     => array(
			'medias' => new CURLFILE( $data["medias"] ),
			'body'   => $data["message"],
			'number' => $data["number"],
			'whatsappId' => $data["waid"],
		),
		CURLOPT_HTTPHEADER     => array(
			'Authorization: Bearer ' . $data["key"],
		),
	) );

	$response = curl_exec( $curl );
	curl_close( $curl );
	if ( $response === false ) {
		$error = curl_error( $curl );
		error_log( "Error in request:" . $error );
	} else {
		$result = json_decode( $response, true );
		if ( ! empty( $result["error"] ) ) {
			$error = $result["error"]["message"];
			error_log( $error );
		}
	}



}

?>