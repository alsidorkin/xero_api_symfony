
Getting Started
To run locally, you'll need a local web server with PHP support.
you can use a local symfony server.

Clone this repo into your local server webroot. i.e. htdocs
Launch a terminal app and change to the newly cloned folder xero-integration
Download dependencies with Composer using the following command:
composer install
Create a Xero App
To obtain your API keys, follow these steps and create a Xero app

Create a free Xero user account (if you don't have one)
Login to Xero developer center
Click "New App" link
Enter your App name, company url, privacy policy url.
Enter the redirect URI (your callback url - i.e. http://localhost:8000/callback.php)
Agree to terms and condition and click "Create App".
Click "Generate a secret" button.
Copy your client id and client secret and save for use later.
Click the "Save" button. You secret is now hidden.
Configure API keys
You'll need to update your code where ever there is a clientId, clientSecret or redirectUri

AutorizationController.php
CallbackController.php
AuthorizedResourceController.php

Sample PHP code from AutorizationController.php

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
	'clientId'                => '__YOUR_CLIENT_ID__',,   
	'clientSecret'            => "__YOUR_CLIENT_SECRET__",
	'redirectUri'             => "__YOUR_REDIRECT_URI__",
	'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
	'urlAccessToken'          => 'https://identity.xero.com/connect/token',
	'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
]);
Take it for a spin
Launch your browser and navigate to http://localhost:8000/ (or whatever the correct path is).

You should see a connect to xero link.
Click the link, login to Xero (if you aren't already)
Grant access to your user account and select the Demo company to connect to.
Done - try various API actions such as adding, changing, viewing data about your contacts in XERO.You can also upload all your contacts to an Excel file.


Links:

* https://developer.xero.com/documentation/getting-started-guide/