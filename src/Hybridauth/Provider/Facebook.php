<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception;
use Hybridauth\Adapter\Template\OAuth2\OAuth2Template;
use Hybridauth\Entity\Profile;

/**
* Facebook adapter extending OAuth2 Template
*
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Facebook.html
*/
class Facebook extends OAuth2Template
{
	/**
	* Internal: Initialize Facebook adapter. This method isn't intended for public consumption.
	*
	* Basically on initializers we feed defaults values to \OAuth2\Template::initialize()
	*
	* let*() methods are similar to set, but 'let' will not overwrite the value if its already set
	*/
	function initialize()
	{
		parent::initialize();

		$this->letApplicationId( $this->getAdapterConfig( 'keys', 'id' ) );
		$this->letApplicationSecret( $this->getAdapterConfig( 'keys', 'secret' ) );

		$scope = $this->getAdapterConfig( 'scope' )
			? $this->getAdapterConfig( 'scope' )
			: 'email,user_about_me,user_birthday,user_hometown,user_website,read_stream,offline_access,publish_stream,read_friendlists';

		$this->letApplicationScope( $scope );

		$this->letEndpointRedirectUri( $this->getHybridauthEndpointUri() );
		$this->letEndpointBaseUri( 'https://graph.facebook.com/' );
		$this->letEndpointAuthorizeUri( 'https://www.facebook.com/dialog/oauth' );
		$this->letEndpointRequestTokenUri( 'https://graph.facebook.com/oauth/access_token' );

		$this->letEndpointAuthorizeUriAdditionalParameters( array( 'display' => 'page' ) );
	}

	// --------------------------------------------------------------------

	/**
	* Returns user profile
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->getUserProfile();
	*/
	function getUserProfile()
	{
		// request user infos
		$response = $this->signedRequest( "me" );
		$response = json_decode ( $response );

		if ( ! isset( $response->id ) || isset ( $response->error ) ){
			throw new
				Exception(
					'User profile request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::USER_PROFILE_REQUEST_FAILED,
					$this
				);
		}

		$parser = function($property) use($response)
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$profile = new Profile();

		$profile->setIdentifier ( $parser( 'id'         ) );
		$profile->setFirstName  ( $parser( 'first_name' ) );
		$profile->setLastName   ( $parser( 'last_name'  ) );
		$profile->setDisplayName( $parser( 'name'       ) );
		$profile->setProfileURL ( $parser( 'link'       ) );
		$profile->setWebSiteURL ( $parser( 'website'    ) );
		$profile->setGender     ( $parser( 'gender'     ) );
		$profile->setDescription( $parser( 'bio'        ) );
		$profile->setEmail      ( $parser( 'email'      ) );
		$profile->setLanguage   ( $parser( 'locale'     ) );
		$profile->setPhotoURL   ( 'https://graph.facebook.com/' . $profile->getIdentifier() . '/picture?width=150&height=150' );

		if( $parser( 'birthday' ) ){
			list ( $m, $d, $y ) = explode ( "/", $parser( 'birthday' ) );

			$profile->setBirthDay  ( $d );
			$profile->setBirthMonth( $m );
			$profile->setBirthYear ( $y );
		}

		if( $parser( 'verified' ) ){
			$profile->setEmailVerified( $profile->getEmail() );
		}

		return $profile;
	}

	// --------------------------------------------------------------------

	/**
	* Returns user contacts list
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->getUserContacts();
	*/
	function getUserContacts()
	{
		$response = $this->signedRequest( 'me/friends' );
		$response = json_decode( $response );

		// Provider Errors shall not pass silently
		if( ! $response || isset( $response->error ) ){
			throw new
				Exception(
					'User contacts request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::USER_CONTACTS_REQUEST_FAILED,
					$this
				);
		}

		$parser = function($property) use($response)
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$contacts = array();

		if( isset( $response->data ) && is_array( $response->data ) ){
			foreach( $response->data as $item ){
				$uc = new Profile();

				$profile->setIdentifier ( $parser( 'id'   ) );
				$profile->setDisplayName( $parser( 'name' ) );
				$profile->setProfileURL ( 'https://www.facebook.com/profile.php?id=' . $profile->getIdentifier() );
				$profile->setPhotoURL   ( 'https://graph.facebook.com/' . $profile->getIdentifier() . '/picture?width=150&height=150' );

				$contacts [] = $uc;
			}
		}

		return $contacts;
	}

	// --------------------------------------------------------------------

	/**
	* Updates user status
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->setUserStatus( _STATUS_ );
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->setUserStatus( _PARAMS_ );
	*/
	function setUserStatus( $status )
	{
		$config = $this->getHybridauthConfig();
		$keys = $config['providers']['Facebook']['keys'];

		$response = $this->signedRequest(
			'/' . $this->getUserProfile()->getIdentifier() . '/feed',
			'POST',
			array(
				'message' 		=> $status,
				'client_id'     => $this->getApplicationId(),
				'client_secret' => $this->getApplicationSecret(),
				'redirect_uri'  => $this->getEndpointRedirectUri(),
			)
		);
		return json_decode( $response );
 	}
}
