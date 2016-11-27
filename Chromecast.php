<?php

require_once("CCprotoBuf.php");

class Chromecast
{

	// Sends a picture or a video to a Chromecast using reverse
	// engineered castV2 protocol

	private $socket; // Socket to the Chromecast
	private $requestId = 1; // Incrementing request ID parameter
	public $transportid = ""; // The transportid of our connection

	public function __construct($ip, $port) {

		// Establish Chromecast connection

		// Don't pay much attention to the Chromecast's certificate. 
		// It'll be for the wrong host address anyway if we 
		// use port forwarding!
		$contextOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
			]
		];
		$context = stream_context_create($contextOptions);

		if ($this->socket = stream_socket_client('ssl://' . $ip . ":" . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context)) {
		} else {
			throw new Exception("Failed to connect to remote Chromecast");
		}
	}

	public function launch($appid) {

		// Launches the chromecast app on the connected chromecast

		// CONNECT
		$c = new CastMessage();
		$c->source_id = "0000000000";
		$c->receiver_id = "receiver-0";
		$c->namespace = "urn:x-cast:com.google.cast.tp.connection";
		$c->payloadtype = 0;
		$c->payloadutf8 = '{"type":"CONNECT"}';
		fwrite($this->socket, $c->encode());
		fflush($this->socket);

		// LAUNCH
		$c = new CastMessage();
                $c->source_id = "0000000000";
                $c->receiver_id = "receiver-0";
                $c->namespace = "urn:x-cast:com.google.cast.receiver";
                $c->payloadtype = 0;
                $c->payloadutf8 = '{"type":"LAUNCH","appId":"' . $appid . '","requestId":' . $this->requestId . '}';
		fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->requestId++;

		while ($this->transportid == "") {
			$this->getCastMessage();
		}
		
	}

	function connect() {
		// This connects to the transport
		$c = new CastMessage();
                $c->source_id = "0000000000";
                $c->receiver_id = $this->transportid;
                $c->namespace = "urn:x-cast:com.google.cast.tp.connection";
                $c->payloadtype = 0;
                $c->payloadutf8 = '{"type":"CONNECT"}';
                fwrite($this->socket, $c->encode());
                fflush($this->socket);
                $this->requestId++;
	}

	public function getCastMessage() {
		// Get the Chromecast Message/Response
		// Later on we could update CCprotoBuf to decode this
		// but for now all we need is the transport id if it is
		// in the packet and we can read that directly.
		$response = fread($this->socket, 2000);
		if (preg_match("/transportId/s", $response)) {
			preg_match("/transportId\"\:\"([^\"]*)/",$response,$matches);
			$matches = $matches[1];
			$this->transportid = $matches;
		}
		return $response;
	}

	public function show($url) {
		// Request the app to show the given url (of a picture
		// or a chromecast compatible video)

		$c = new CastMessage();
                $c->source_id = "0000000000";
                $c->receiver_id = $this->transportid;
		$c->namespace = "urn:x-cast:com.chrisridings.piccastr";
                $c->payloadtype = 0;
                $c->payloadutf8 = $url;
		fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->requestId++;

		$response = $this->getCastMessage();
	}

	public function pingpong() {
		// To keep the image/video displaying we have to
		// send the heartbeat.
		$c = new CastMessage();
                $c->source_id = "0000000000";
                $c->receiver_id = "receiver-0";
                $c->namespace = "urn:x-cast:com.google.cast.tp.heartbeat";
                $c->payloadtype = 0;
                $c->payloadutf8 = '{"type":"PING"}';
                fwrite($this->socket, $c->encode());
                fflush($this->socket);
                $this->requestId++;
		$response = $this->getCastMessage();
	}


}

?>
