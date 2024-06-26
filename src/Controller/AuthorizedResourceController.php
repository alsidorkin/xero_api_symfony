<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\StorageClass;
use XeroAPI\XeroPHP\AccountingObjectSerializer;
use XeroAPI\XeroPHP\Models\Accounting\Contact; 
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Api\IdentityApi;
use XeroAPI\XeroPHP\Api\AccountingApi;
use GuzzleHttp\Client as GuzzleClient;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;
use XeroAPI\XeroPHP\Models\Accounting\ContactPerson;
use XeroAPI\XeroPHP\JWTClaims;

class AuthorizedResourceController extends AbstractController
{
    #[Route('/authorizedResource.php', name: 'authorized_resource')]
    public function index(): Response
    {
        
// Storage Classe uses sessions for storing token > extend to your DB of choice
$storage = new StorageClass();
$xeroTenantId = (string)$storage->getSession()['tenant_id'];

if ($storage->getHasExpired()) {
  $provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => '34D76C0B89F740A48B3A9BC86A9B2FEC',
    'clientSecret'            => 'tYnuyj5AJKwR8nmKJrS6RExzdXV39O3Fg7ADx4Ftip-Tm6tI',
    'redirectUri'             => 'http://localhost:8000/callback.php',
    'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
    'urlAccessToken'          => 'https://identity.xero.com/connect/token',
    'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
  ]);

  $newAccessToken = $provider->getAccessToken('refresh_token', [
    'refresh_token' => $storage->getRefreshToken()
  ]);
 
  // Save my token, expiration and refresh token
  $storage->setToken(
      $newAccessToken->getToken(),
      $newAccessToken->getExpires(),
      $xeroTenantId,
      $newAccessToken->getRefreshToken(),
      $newAccessToken->getValues()["id_token"] );
}

$config = Configuration::getDefaultConfiguration()->setAccessToken( (string)$storage->getSession()['token'] );
$apiInstance = new AccountingApi(
    new GuzzleClient(),
    $config
);

$message = "no API calls";
if (isset($_GET['action'])) {
  if ($_GET["action"] == 1) {
      // Get Organisation details
      $apiResponse = $apiInstance->getOrganisations($xeroTenantId);
      $message = 'Organisation Name: ' . $apiResponse->getOrganisations()[0]->getName();
  } else if ($_GET["action"] == 2) {

    //   require_once 'create_contact.php';

      $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
      $last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';
      $email = isset($_POST['email']) ? $_POST['email'] : '';
      // Create Contact
      try {
          $person = new ContactPerson;
          $person->setFirstName($first_name)
              ->setLastName($last_name)
              ->setEmailAddress($email)
              ->setIncludeInEmails(true);

          $arr_persons = [];
          array_push($arr_persons, $person);

          $contact = new Contact;
          $contact->setName($first_name . ' ' . $last_name)
              ->setFirstName($first_name)
              ->setLastName($last_name)
              ->setEmailAddress($email)
              ->setContactPersons($arr_persons);
          
          $arr_contacts = [];
          array_push($arr_contacts, $contact);
          $contacts = new Contacts;
          $contacts->setContacts($arr_contacts);

          $apiResponse = $apiInstance->createContacts($xeroTenantId,$contacts);
          $message = 'New Contact Name: ' . $apiResponse->getContacts()[0]->getName();
      } catch (\XeroAPI\XeroPHP\ApiException $e) {
          $error = AccountingObjectSerializer::deserialize(
              $e->getResponseBody(),
              '\XeroAPI\XeroPHP\Models\Accounting\Error',
              []
          );
          $message = "ApiException - " . $error->getElements()[0]["validation_errors"][0]["message"];
      }

  } else if ($_GET["action"] == 3) {
      $if_modified_since = new \DateTime("2019-01-02T19:20:30+01:00"); // \DateTime | Only records created or modified since this timestamp will be returned
      $if_modified_since = null;
      $where = 'Type=="ACCREC"'; // string
      $where = null;
      $order = null; // string
      $ids = null; // string[] | Filter by a comma-separated list of Invoice Ids.
      $invoice_numbers = null; // string[] |  Filter by a comma-separated list of Invoice Numbers.
      $contact_ids = null; // string[] | Filter by a comma-separated list of ContactIDs.
      $statuses = array("DRAFT", "SUBMITTED");;
      $page = 1; // int | e.g. page=1 – Up to 100 invoices will be returned in a single API call with line items
      $include_archived = null; // bool | e.g. includeArchived=true - Contacts with a status of ARCHIVED will be included
      $created_by_my_app = null; // bool | When set to true you'll only retrieve Invoices created by your app
      $unitdp = null; // int | e.g. unitdp=4 – You can opt in to use four decimal places for unit amounts

      try {
          $apiResponse = $apiInstance->getInvoices($xeroTenantId, $if_modified_since, $where, $order, $ids, $invoice_numbers, $contact_ids, $statuses, $page, $include_archived, $created_by_my_app, $unitdp);
          if (  count($apiResponse->getInvoices()) > 0 ) {
              $message = 'Total invoices found: ' . count($apiResponse->getInvoices());
          } else {
              $message = "No invoices found matching filter criteria";
          }
      } catch (Exception $e) {
          echo 'Exception when calling AccountingApi->getInvoices: ', $e->getMessage(), PHP_EOL;
      }
  } else if ($_GET["action"] == 4) {
      // Create Multiple Contacts
      try {
          $contact = new Contact;
          $contact->setName('George Jetson')
              ->setFirstName("George")
              ->setLastName("Jetson")
              ->setEmailAddress("george.jetson@aol.com");

          // Add the same contact twice - the first one will succeed, but the
          // second contact will throw a validation error which we'll catch.
          $arr_contacts = [];
          array_push($arr_contacts, $contact);
          array_push($arr_contacts, $contact);
          $contacts = new Contacts;
          $contacts->setContacts($arr_contacts);

          $apiResponse = $apiInstance->createContacts($xeroTenantId,$contacts,false);
          $message = 'First contacts created: ' . $apiResponse->getContacts()[0]->getName();

          if ($apiResponse->getContacts()[1]->getHasValidationErrors()) {
              $message = $message . '<br> Second contact validation error : ' . $apiResponse->getContacts()[1]->getValidationErrors()[0]["message"];
          }

      } catch (\XeroAPI\XeroPHP\ApiException $e) {
          $error = AccountingObjectSerializer::deserialize(
              $e->getResponseBody(),
              '\XeroAPI\XeroPHP\Models\Accounting\Error',
              []
          );
          $message = "ApiException - " . $error->getElements()[0]["validation_errors"][0]["message"];
      }
  } else if ($_GET["action"] == 5) {

      $if_modified_since = new \DateTime("2019-01-02T19:20:30+01:00"); // \DateTime | Only records created or modified since this timestamp will be returned
      $where = null;
      $order = null; // string
      $ids = null; // string[] | Filter by a comma-separated list of Invoice Ids.
      $page = 1; // int | e.g. page=1 – Up to 100 invoices will be returned in a single API call with line items
      $include_archived = null; // bool | e.g. includeArchived=true - Contacts with a status of ARCHIVED will be included
 
      try {
          $apiResponse = $apiInstance->getContacts($xeroTenantId, $if_modified_since, $where, $order, $ids, $page, $include_archived);
          if (  count($apiResponse->getContacts()) > 0 ) {
              $message = 'Total contacts found: ' . count($apiResponse->getContacts());
          } else {
              $message = "No contacts found matching filter criteria";
          }
      } catch (Exception $e) {
          echo 'Exception when calling AccountingApi->getContacts: ', $e->getMessage(), PHP_EOL;
      }
  } else if ($_GET["action"] == 6) {

      $jwt = new JWTClaims();
      $jwt->setTokenId((string)$storage->getIdToken() );
      // Set access token in order to get authentication event id
      $jwt->setTokenAccess( (string)$storage->getAccessToken() );
      $jwt->decode();

      echo("sub:" . $jwt->getSub() . "<br>");
      echo("sid:" . $jwt->getSid() . "<br>");
      echo("iss:" . $jwt->getIss() . "<br>");
      echo("exp:" . $jwt->getExp() . "<br>");
      echo("given name:" . $jwt->getGivenName() . "<br>");
      echo("family name:" . $jwt->getFamilyName() . "<br>");
      echo("email:" . $jwt->getEmail() . "<br>");
      echo("user id:" . $jwt->getXeroUserId() . "<br>");
      echo("username:" . $jwt->getPreferredUsername() . "<br>");
      echo("session id:" . $jwt->getGlobalSessionId() . "<br>");
      echo("authentication_event_id:" . $jwt->getAuthenticationEventId() . "<br>");

  }

}
        return $this->render('authorized_resource/index.html.twig', [
            'controller_name' => 'AuthorizedResourceController','message' => $message,
        ]);
    }
}
