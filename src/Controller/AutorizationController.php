<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\StorageClass;

class AutorizationController extends AbstractController
{

    #[Route('/autorization', name: 'app_autorization')]
    public function autorization(): Response
    {
        
        $storage = new StorageClass();

        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
          'clientId'                => '34D76C0B89F740A48B3A9BC86A9B2FEC',
          'clientSecret'            => 'tYnuyj5AJKwR8nmKJrS6RExzdXV39O3Fg7ADx4Ftip-Tm6tI',
          'redirectUri'             => 'http://localhost:8000/callback.php',
          'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
          'urlAccessToken'          => 'https://identity.xero.com/connect/token',
          'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
        ]);
      
        // Scope defines the data your app has permission to access.
        // Learn more about scopes at https://developer.xero.com/documentation/oauth2/scopes
        $options = [
          'scope' => ['openid email profile offline_access assets projects accounting.settings accounting.transactions accounting.contacts accounting.journals.read accounting.reports.read accounting.attachments']
      ];
        // This returns the authorizeUrl with necessary parameters applied (e.g. state).
        $authorizationUrl = $provider->getAuthorizationUrl($options);
        //  print_r($authorizationUrl);exit();
      
        // Save the state generated for you and store it to the session.
        // For security, on callback we compare the saved state with the one returned to ensure they match.
        $_SESSION['oauth2state'] = $provider->getState();
        // print_r($_SESSION);exit();
        //  print_r($_SESSION['oauth2state']);exit();
        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit();
    }


}
