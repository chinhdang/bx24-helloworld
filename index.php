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

// Hiển thị lỗi (Chỉ nên bật trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


 // Include the autoloader file, which is responsible for loading the dependencies required by the script
require_once __DIR__ . '/vendor/autoload.php';

// Import necessary classes from the Bitrix24 PHP SDK and other dependencies
use Bitrix24\SDK\Core\Credentials\AuthToken; // Represents an authentication token used to authenticate with the Bitrix24 API
use Bitrix24\SDK\Core\Credentials\ApplicationProfile; // Represents an application profile, which contains configuration data for the Bitrix24 application
use Bitrix24\SDK\Services\ServiceBuilderFactory; // Factory for creating service instances
use Monolog\Handler\StreamHandler; // Handler for logging messages to a stream (e.g., a file)
use Monolog\Logger; // Logger that can be used to log messages
use Monolog\Processor\MemoryUsageProcessor; // Processor that logs memory usage information
use Symfony\Component\EventDispatcher\EventDispatcher; // Event dispatcher that can be used to dispatch events
use Symfony\Component\HttpFoundation\Request; // Represents an HTTP request

// Tạo logger (không bắt buộc)
$log = new Logger('bitrix24-php-sdk'); // Create a new logger instance with the name 'bitrix24-php-sdk'
$log->pushHandler(new StreamHandler('bitrix24-php-sdk.log')); // Add a stream handler to the logger, which will log messages to a file named 'bitrix24-php-sdk.log'
$log->pushProcessor(new MemoryUsageProcessor(true, true)); // Add a memory usage processor to the logger, which will log memory usage information

// Create a new request object from the current HTTP request
$request = Request::createFromGlobals(); 

// In ra nội dung của $_REQUEST
?>
    <pre>
    Application is worked, auth tokens from bitrix24:
    <?= print_r($_REQUEST, true) ?>
</pre>
<?php

// Lấy thông tin xác thực
$auth = getAuth($request);

/*
// Kiểm tra xem có nhận được thông tin xác thực hay không
if ($request->request->has('auth')) {
    echo 'Received auth data from Bitrix24.';
} else {
    echo 'No auth data received from Bitrix24.';
};
*/

// Kiểm tra giá trị của $auth
if ($auth instanceof AuthToken) {
    echo 'Received auth data from Bitrix24.';
} else {
    echo 'No auth data received from Bitrix24.';
    exit;
}


// Create a new service builder factory instance with an event dispatcher and logger
$b24ServiceBuilderFactory = new ServiceBuilderFactory(new EventDispatcher(), $log); 

// Initialize an application profile from an array of configuration data
// The array contains the client ID, client secret, and scope for the Bitrix24 application
// The INSERT_HERE_YOUR_DATA placeholders should be replaced with actual values
$appProfile = ApplicationProfile::initFromArray([
    'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID' => getenv('CLIENT_ID'),
    'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => getenv('CLIENT_SECRET'),
    'BITRIX24_PHP_SDK_APPLICATION_SCOPE' => 'crm'
]);

// Initialize the Bitrix24 service using the application profile, authentication token, and domain from the request
$b24Service = $b24ServiceBuilderFactory->initFromRequest($appProfile, AuthToken::initFromPlacementRequest($request), $request->get('DOMAIN'));


// Retrieve the current user's profile using the getMainScope() method and dump the result
$userProfile = $b24Service->getMainScope()->main()->getCurrentUserProfile()->getUserProfile();
var_dump($userProfile);

// Retrieve a list of deals and address to the first element
// The getCRMScope() method returns the CRM scope of the service, which provides access to the deals
// The list() method retrieves a list of deals with the specified fields (ID and TITLE)
// The getLeads() method returns the list of deals
// The [0] index accesses the first deal in the list
// The TITLE property accesses the title of the first deal
// var_dump($b24Service->getCRMScope()->deal()->list([], [], ['ID', 'TITLE'])->getDeals()[0]->TITLE);

$deals = $b24Service->getCRMScope()->deal()->list([], [], ['ID', 'TITLE'])->getDeals();
if (!empty($deals)) {
    echo 'First Deal Title: ' . $deals[0]->TITLE;
} else {
    echo 'No deal found.';
}

// Hàm lấy thông tin xác thực
function getAuth($request)
{
    if (isset($_SESSION['AUTH'])) {
        return AuthToken::initFromArray($_SESSION['AUTH']);
    } elseif ($request->request->has('AUTH_ID') && $request->request->has('REFRESH_ID')) {
        $authData = [
            'access_token' => $request->request->get('AUTH_ID'),
            'refresh_token' => $request->request->get('REFRESH_ID'),
            'member_id' => $request->request->get('member_id'),
            'domain' => $request->request->get('DOMAIN'),
            'application_token' => $request->request->get('APP_SID'),
            'expires_in' => $request->request->get('AUTH_EXPIRES'),
        ];

        $_SESSION['AUTH'] = $authData;

        return AuthToken::initFromArray($authData);
    } else {
        return null; // Trả về null thay vì false
    }
}

/*
// Xử lý các sự kiện từ Bitrix24
if ($request->request->has('event')) {
    $event = $request->request->get('event');

    switch ($event) {
        case 'ONAPPINSTALL':
            // Xử lý sự kiện cài đặt ứng dụng
            handleAppInstall($bitrix24, $request);
            break;

        case 'ONIMBOTMESSAGEADD':
            // Xử lý sự kiện nhận tin nhắn
            handleIncomingMessage($bitrix24, $request);
            break;

        // Thêm các trường hợp khác nếu cần
        default:
            // Xử lý các sự kiện khác
            break;
    }
} else {
    echo 'No event received from Bitrix24.';
    // Xử lý nếu cần
}
*/