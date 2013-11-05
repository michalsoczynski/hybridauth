<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

use Hybridauth\Http\ClientInterface;
use Hybridauth\Http\Request;

class Client implements ClientInterface
{
	protected $request  = null;
	protected $response = null;

	protected $parameters = null;

	// --------------------------------------------------------------------

	function __construct( $curl_opts = array() )
	{
		$this->request = new Request();

		$this->request->setCurlOptions( $curl_opts );
	}

	// --------------------------------------------------------------------

	function get($uri, $args = array(), $headers = array(), $body = null)
	{
		$this->parameters = array( 'uri' => $uri, 'method' => 'GET', 'args' => $args, 'headers' => $headers, 'body' => $body );

		return $this->response = $this->request->send( $uri, 'GET', $args, $headers, $body );
	}

	// --------------------------------------------------------------------

	function post($uri, $args, $headers = array(), $body = null)
	{

		$this->parameters = array( 'uri' => $uri, 'method' => 'GET', 'args' => $args, 'headers' => $headers, 'body' => $body );

		return $this->response = $this->request->send( $uri, 'POST', $args, $headers, $body );
	}

	// --------------------------------------------------------------------

	function getState()
	{
		return
			'Uri: ' . $this->parameters['uri'] .
			'. Method: ' . $this->parameters['method'] .
			'. Error: ' . $this->getResponseError() .
			'. Status: ' . $this->getResponseStatus() .
			'. Response: ' . $this->getResponseBody()
		;
	}

	// --------------------------------------------------------------------

	function getResponse()
	{
		return $this->response;
	}

	// --------------------------------------------------------------------

	function getResponseBody()
	{
		return $this->response->getBody();
	}

	// --------------------------------------------------------------------

	function getResponseStatus()
	{
		return $this->response->getStatusCode();
	}

	// --------------------------------------------------------------------

	function getResponseError()
	{
		return $this->response->getErrorCode();
	}
}
