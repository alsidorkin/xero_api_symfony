<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Api\IdentityApi;
use GuzzleHttp\Client as GuzzleClient;
use App\StorageClass;

class CallbackController extends AbstractController
{
    #[Route('/callback.php', name: 'app_callback')]
    public function callback(Request $request, StorageClass $storage): Response
    {
      
       // print_r($_SESSION['oauth2state']);exit();
  // Storage Classe uses sessions for storing token > extend to your DB of choice
  $storage = new StorageClass();

  $provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => '34D76C0B89F740A48B3A9BC86A9B2FEC',
    'clientSecret'            => 'tYnuyj5AJKwR8nmKJrS6RExzdXV39O3Fg7ADx4Ftip-Tm6tI',
    'redirectUri'             => 'http://localhost:8001/callback.php',
    'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
    'urlAccessToken'          => 'https://identity.xero.com/connect/token',
    'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
  ]);
   //print_r($_SESSION);exit();
   
  // If we don't have an authorization code then get one
  if (!isset($_GET['code'])) {
    echo "Something went wrong, no authorization code found";
    exit("Something went wrong, no authorization code found");

  // Check given state against previously stored one to mitigate CSRF attack
  } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    echo "Invalid State";
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
  } else {

    try {

      // print_r($_GET['code']);exit();
      // Try to get an access token using the authorization code grant.
      $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
      ]);
      //print_r($accessToken);exit();
      $config = Configuration::getDefaultConfiguration()->setAccessToken( (string)$accessToken->getToken() );
      $identityInstance = new IdentityApi(
        new GuzzleClient(),
        $config
      );

      $result = $identityInstance->getConnections();

      // Save my tokens, expiration tenant_id
      $storage->setToken(
          $accessToken->getToken(),
          $accessToken->getExpires(),
          $result[0]->getTenantId(),
          $accessToken->getRefreshToken(),
          $accessToken->getValues()["id_token"]
      );

      header('Location: ' . './authorizedResource.php');
      exit();

    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
      echo "Callback failed";
      exit();
    }
    }
}
}
