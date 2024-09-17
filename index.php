<?php

/**
 * This file is part of the bitrix24-php-sdk package.
 *
 * © Maksim Mesilov <mesilov.maxim@gmail.com>
 *
 * For the full copyright and license information, please view the MIT-LICENSE.txt
 * file that was distributed with this source code.
 */

// Declare strict type checking for the PHP script
 declare(strict_types=1);

 session_start();

// Import necessary classes from the Bitrix24 PHP SDK and other dependencies
use Bitrix24\SDK\Core\Credentials\AuthToken; // Represents an authentication token used to authenticate with the Bitrix24 API
use Bitrix24\SDK\Core\Credentials\ApplicationProfile; // Represents an application profile, which contains configuration data for the Bitrix24 application
use Bitrix24\SDK\Services\ServiceBuilderFactory; // Factory for creating service instances
use Monolog\Handler\StreamHandler; // Handler for logging messages to a stream (e.g., a file)
use Monolog\Logger; // Logger that can be used to log messages
use Monolog\Processor\MemoryUsageProcessor; // Processor that logs memory usage information
use Symfony\Component\EventDispatcher\EventDispatcher; // Event dispatcher that can be used to dispatch events
use Symfony\Component\HttpFoundation\Request; // Represents an HTTP request

// Include the autoloader file, which is responsible for loading the dependencies required by the script
require_once 'vendor/autoload.php'; 
?>
    <pre>
    Application is worked, auth tokens from bitrix24:
    <?= print_r($_REQUEST, true) ?>
</pre>
<?php

// Create a new request object from the current HTTP request
$request = Request::createFromGlobals(); 

// Kiểm tra xem có nhận được thông tin xác thực hay không
if ($request->request->has('auth')) {
    echo 'Received auth data from Bitrix24.';
} else {
    echo 'No auth data received from Bitrix24.';
};

$log = new Logger('bitrix24-php-sdk'); // Create a new logger instance with the name 'bitrix24-php-sdk'
$log->pushHandler(new StreamHandler('bitrix24-php-sdk.log')); // Add a stream handler to the logger, which will log messages to a file named 'bitrix24-php-sdk.log'
$log->pushProcessor(new MemoryUsageProcessor(true, true)); // Add a memory usage processor to the logger, which will log memory usage information

// Create a new service builder factory instance with an event dispatcher and logger
$b24ServiceBuilderFactory = new ServiceBuilderFactory(new EventDispatcher(), $log); 

// Initialize an application profile from an array of configuration data
// The array contains the client ID, client secret, and scope for the Bitrix24 application
// The INSERT_HERE_YOUR_DATA placeholders should be replaced with actual values
$appProfile = ApplicationProfile::initFromArray([
    'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID' => 'local.66e95a625d0773.28570757',
    'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => 'FQZkGDCOSfzYW9XDqkuU4h3X2hNqQHr4t2Q6gsmySSpaM6dMbR',
    'BITRIX24_PHP_SDK_APPLICATION_SCOPE' => 'crm'
]);

// Initialize the Bitrix24 service using the application profile, authentication token, and domain from the request
$b24Service = $b24ServiceBuilderFactory->initFromRequest($appProfile, AuthToken::initFromPlacementRequest($request), $request->get('DOMAIN'));

// Retrieve the current user's profile using the getMainScope() method and dump the result
var_dump($b24Service->getMainScope()->main()->getCurrentUserProfile()->getUserProfile());

// Retrieve a list of deals and address to the first element
// The getCRMScope() method returns the CRM scope of the service, which provides access to the deals
// The list() method retrieves a list of deals with the specified fields (ID and TITLE)
// The getLeads() method returns the list of deals
// The [0] index accesses the first deal in the list
// The TITLE property accesses the title of the first deal
var_dump($b24Service->getCRMScope()->lead()->list([], [], ['ID', 'TITLE'])->getLeads()[0]->TITLE);