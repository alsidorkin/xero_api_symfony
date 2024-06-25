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

class XeroController extends AbstractController
{
    
        #[Route('/contacts/add', name: 'contacts_add')]
        public function addContact(Request $request, StorageClass $storage): Response
        {
           
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $email = $request->request->get('email');
    
           
            $config = Configuration::getDefaultConfiguration()->setAccessToken((string)$storage->getSession()['token']);
            $apiInstance = new AccountingApi(new GuzzleClient(), $config);
    
            
            try {
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
            } catch (\Exception $e) {
                $message = 'Error adding contact: ' . $e->getMessage();
            }
    
           
            return $this->render('xero/add.html.twig', [
                'message' => $message,
            ]);
        }
    }
    /**
     * @Route("/xero/generate-excel", name="xero_generate_excel")
     */
//     public function generateExcel(): Response
//     {
//         $accessToken = $this->get('session')->get('xero_access_token');
//         if (!$accessToken) {
//             return new Response('Not authenticated', Response::HTTP_UNAUTHORIZED);
//         }

//         $provider = new Xero([
//             'clientId'     => $_ENV['XERO_CLIENT_ID'],
//             'clientSecret' => $_ENV['XERO_CLIENT_SECRET'],
//             'redirectUri'  => $_ENV['XERO_REDIRECT_URI'],
//         ]);

//         $storage = new \Calcinai\Xero\Storage\MemoryStorage();
//         $storage->setAccessToken(new \League\OAuth2\Client\Token\AccessToken([
//             'access_token' => $accessToken,
//             'expires'      => time() + 3600, // Set appropriate expiration time
//         ]));

//         $xero = new \Calcinai\Xero\Client($provider, $storage);

//         $invoices = $xero->load(\Calcinai\Xero\Accounting\Invoice::class)->execute();

//         
//         $spreadsheet = new Spreadsheet();
//         $sheet = $spreadsheet->getActiveSheet();
//         $sheet->setCellValue('A1', 'Invoice Number');
//         $sheet->setCellValue('B1', 'Date');
//         $sheet->setCellValue('C1', 'Due Date');
//         $sheet->setCellValue('D1', 'Total');

//         $row = 2;
//         foreach ($invoices as $invoice) {
//             $sheet->setCellValue('A' . $row, $invoice->InvoiceNumber);
//             $sheet->setCellValue('B' . $row, $invoice->Date->format('Y-m-d'));
//             $sheet->setCellValue('C' . $row, $invoice->DueDate->format('Y-m-d'));
//             $sheet->setCellValue('D' . $row, $invoice->Total);
//             $row++;
//         }

//         $writer = new Xlsx($spreadsheet);
//         $fileName = 'invoices.xlsx';
//         $temp_file = tempnam(sys_get_temp_dir(), $fileName);
//         $writer->save($temp_file);

//         return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
//     }
// }
