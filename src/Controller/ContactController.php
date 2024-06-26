<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ContactController extends AbstractController
{

    #[Route('/contacts/addform', name: 'contacts_addform')]
    public function addFormContact(): Response
    {
        
        return $this->render('contact/add.html.twig', [
           
        ]);
    }



#[Route('/contacts/add', name: 'contacts_add')]
public function addContact(Request $request, StorageClass $storage, UrlGeneratorInterface $urlGenerator): Response
{
    $token = $storage->getSession()['token'] ?? null;
    $xeroTenantId = $storage->getSession()['tenant_id'] ?? null;

    if (!$token || !$xeroTenantId) {
        return $this->render('xero/update.html.twig', [
            'message' => 'Token or Xero Tenant ID is missing.',
        ]);
    }

    $config = Configuration::getDefaultConfiguration()->setAccessToken((string)$token);
    $apiInstance = new AccountingApi(new GuzzleClient(), $config);

    try {
        if ($request->isMethod('POST')) {
           
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $email = $request->request->get('email_address');

            $person = new ContactPerson();
            $person->setFirstName($firstName)
                ->setLastName($lastName)
                ->setEmailAddress($email);

            $contact = new Contact();
            $contact->setName("$firstName $lastName")
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setEmailAddress($email)
                ->setContactPersons([$person]);

            $contacts = new Contacts();
            $contacts->setContacts([$contact]);

            $apiResponse = $apiInstance->createContacts($storage->getSession()['tenant_id'], $contacts);
            $message = 'Contact added successfully: ' . $apiResponse->getContacts()[0]->getName();

            return new RedirectResponse($urlGenerator->generate('contacts_list'));
        }
    } catch (\Exception $e) {
        $message = 'Error adding contact: ' . $e->getMessage();
        $contacts = [];
    }

    return $this->render('xero/add.html.twig', [
        'message' => $message,
    ]);
}




        #[Route('/contacts/list', name: 'contacts_list')]
    public function listContacts(StorageClass $storage): Response
    {
        //dd($_SESSION);

        $token = $storage->getSession()['token'] ?? null;
        $xeroTenantId = $storage->getSession()['tenant_id'] ?? null;
              //dd($xeroTenantId);
        if (!$token || !$xeroTenantId) {
            return $this->render('xero/list.html.twig', [
                'message' => 'Token or Xero Tenant ID is missing.',
                'contacts' => [],
            ]);
        }

        $config = Configuration::getDefaultConfiguration()->setAccessToken((string)$token);
        $apiInstance = new AccountingApi(new GuzzleClient(), $config);

        try {
            $result = $apiInstance->getContacts($xeroTenantId);
            $contacts = $result->getContacts();
            // dd($contacts);
            $message = 'Contacts retrieved successfully.';
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = AccountingObjectSerializer::deserialize(
                $e->getResponseBody(),
                '\XeroAPI\XeroPHP\Models\Accounting\Error',
                []
            );
            $message = "ApiException - " . $error->getElements()[0]["validation_errors"][0]["message"];
            $contacts = [];
        }

        return $this->render('contact/list.html.twig', [
            'message' => $message,
            'contacts' => $contacts,
        ]);
    }




    #[Route('/contacts/form/{id}', name: 'form_list')]
    public function listForm($id,StorageClass $storage)
    {
        //dd($_SESSION);
        //dd($id);
        $token = $storage->getSession()['token'] ?? null;
        $xeroTenantId = $storage->getSession()['tenant_id'] ?? null;
              //dd($xeroTenantId);
        if (!$token || !$xeroTenantId) {
            return $this->render('xero/list.html.twig', [
                'message' => 'Token or Xero Tenant ID is missing.',
                'contacts' => [],
            ]);
        }

        $config = Configuration::getDefaultConfiguration()->setAccessToken((string)$token);
        $apiInstance = new AccountingApi(new GuzzleClient(), $config);

        try {
            $result = $apiInstance->getContact($xeroTenantId, $id);
            $contact = $result->getContacts()[0]; 
            // dd($contact);
            $message = 'Contacts retrieved successfully.';
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = AccountingObjectSerializer::deserialize(
                $e->getResponseBody(),
                '\XeroAPI\XeroPHP\Models\Accounting\Error',
                []
            );
            $message = "ApiException - " . $error->getElements()[0]["validation_errors"][0]["message"];
            $contact = [];
        }

        return $this->render('contact/update.html.twig', [
           
            'contact' => $contact,
        ]);
    }



    #[Route('/contacts/update/{id}', name: 'contacts_update')]
    public function updateContact(string $id, Request $request, StorageClass $storage): Response
    {
        $token = $storage->getSession()['token'] ?? null;
        $xeroTenantId = $storage->getSession()['tenant_id'] ?? null;

        if (!$token || !$xeroTenantId) {
            return $this->render('xero/update.html.twig', [
                'message' => 'Token or Xero Tenant ID is missing.',
            ]);
        }

        $config = Configuration::getDefaultConfiguration()->setAccessToken((string)$token);
        $apiInstance = new AccountingApi(new GuzzleClient(), $config);

        try {
            $contact = $apiInstance->getContact($xeroTenantId, $id)->getContacts()[0];
           //dd($request);
            if ($request->isMethod('POST')) {
                
                $firstName = $request->request->get('first_name');
                // dd($firstName);
                $lastName = $request->request->get('last_name');
                $email = $request->request->get('email_address');

                $contact->setFirstName($firstName);
                $contact->setLastName($lastName);
                $contact->setEmailAddress($email);

                $apiInstance->updateContact($xeroTenantId, $id, $contact);
                $message = 'Contact updated successfully.';
            } else {
                $message = '';
            }
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = AccountingObjectSerializer::deserialize(
                $e->getResponseBody(),
                '\XeroAPI\XeroPHP\Models\Accounting\Error',
                []
            );
            // $message = "ApiException - " . $error->getElements()[0]["validation_errors"][0]["message"];
            $validationErrors = $error->getElements()[0]["validation_errors"] ?? null;
            if ($validationErrors && isset($validationErrors[0]["message"])) {
                $message = "ApiException - " . $validationErrors[0]["message"];
            } else {
                $message = "ApiException - An unexpected error occurred.";
            }
           
        }
        // $contact = $apiInstance->getContact($xeroTenantId, $id)->getContacts()[0];
        $contacts = $apiInstance->getContacts($xeroTenantId)->getContacts();
    //    dd($contacts);
        return $this->render('contact/list.html.twig', [
            'message' => $message,
            'contacts' => $contacts
        ]);

        
    }


    #[Route('/contacts/export/excel', name: 'contacts_export_excel')]
    public function exportContactsToExcel(StorageClass $storage): Response
    {
        $token = $storage->getSession()['token'] ?? null;
        $xeroTenantId = $storage->getSession()['tenant_id'] ?? null;
    
        if (!$token || !$xeroTenantId) {
            return $this->render('xero/update.html.twig', [
                'message' => 'Token or Xero Tenant ID is missing.',
            ]);
        }
    
        $config = Configuration::getDefaultConfiguration()->setAccessToken((string)$token);
        $apiInstance = new AccountingApi(new GuzzleClient(), $config);
    
        try {
            $result = $apiInstance->getContacts($xeroTenantId);
            $contacts = $result->getContacts();
    
           
            $excelData = [];
            foreach ($contacts as $contact) {
                $excelData[] = [
                    'First Name' => $contact->getFirstName(),
                    'Last Name' => $contact->getLastName(),
                    'Email' => $contact->getEmailAddress(),
                    'Contact ID' => $contact->getContactId(),
                ];
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($excelData, null, 'A1');
    
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'contacts_') . '.xlsx';
            $writer->save($tempFile);
    
            $response = new Response(file_get_contents($tempFile));
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="contacts.xlsx"');
    
            unlink($tempFile);
    
            return $response;
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = $e->getResponseBody();
            return $this->render('error.html.twig', [
                'message' => 'Error exporting contacts to Excel: ' . $error,
            ]);
        }

    } 
}