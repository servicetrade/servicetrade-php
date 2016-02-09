# ServiceTrade PHP SDK
This library provides basic wrappers for REST calls (GET, PUT, POST, DELETE) to the [ServiceTrade](https://www.servicetrade.com) API.  It also provides convenience methods for handling attachments and starting/ending sessions.

Full documentation of ServiceTrade's API is available [here](https://api.servicetrade.com/api/docs).

## Requirements
* A ServiceTrade account
* PHP 5.6 or above with cURL support
* Composer (recommended)

# Installation

## Option 1: Install with Composer
1. Add this github repository to your composer.json:
 ```
"repositories": [
		{
	 		"type": "vcs",
	 		"url": "https://github.com/servicetrade/servicetrade-php"
		}
 ]
 ```

2. Install from the command line:
 ```
composer require servicetrade/servicetrade-php:dev-master
 ```

3. Make sure your application bootstrap file autoloads your Composer dependencies:
 ```php
require_once('vendor/autoload.php');
 ```
 
## Option 2: Download and Install
Download the Servicetrade.php library file and include it in your PHP script:

```php
require_once('/path/to/Servicetrade.php');
```

# Usage

Instantiate the ServiceTrade connection with a username/password, make calls to the API with `get()`, `put()`, etc. methods, and disconnect with `logout()` when done.  The library accepts and returns data as PHP arrays.

Attachments are treated specially; see the ["Upload an avatar image"](#upload-an-avatar-image) example below.

All date/times are handled as Unix timestamps.


## Basic example
```php
<?php
require_once('vendor/autoload.php');
// or: require_once('/path/to/Servicetrade.php');

$username = 'myUsername';
$password = 'myPassword123';
$st = new Servicetrade($username, $password);

if (!$st->getAuthId()) {
	die("Could not log in\n");
}

$myInfo = $st->get('/auth');
echo "My name is {$myInfo['user']['name']} and I work for {$myInfo['user']['company']['name']}.\n";

$technicians = $st->get('/user', [
	'company' => $myInfo['user']['company']['id'],
	'isTech' => true,
]);
echo "We have " . count($technicians['users']) . " technicians.\n";

echo "This will fail:\n";
$st->get('/sadpanda');
$error = $st->getLastError();
echo "ERROR: " . array_shift($error['error']) . "\n";

$st->logout();
```

## Create a new customer and job
```php
date_default_timezone_set('America/New_York');

// see basic example for authentication, then...
$myInfo = $st->get('/auth');

// Create a new customer company
$customerData = [
	'name'              => 'Aardvarks R Us, Inc',
	'addressStreet'     => '409 Blackwell St',
	'addressCity'       => 'Durham',
	'addressState'      => 'NC',
	'addressPostalCode' => '27701',
	'customer'          => true,
];
$customer = $st->post('/company', $customerData);
$customerId = $customer['id'];

// Create a service location for that customer
$locationData = [
	'name'              => 'Aardvarks R Us #423 - West Raleigh',
	'addressStreet'     => '1400 Edwards Mill Rd',
	'addressCity'       => 'Raleigh',
	'addressState'      => 'NC',
	'addressPostalCode' => '27607',
	'companyId'         => $customerId,
];

$location = $st->post('/location', $locationData);
$locationId = $location['id'];

// Create some services to perform at that location
$service1Data = [
	'description'   => 'Fix the whizbangs',
	'serviceLineId' => 1,  // see https://api.servicetrade.com/api/docs#resource-serviceline
	'windowStart'   => strtotime('today'),
	'windowEnd'     => strtotime('+2 days'),
	'duration'      => '3600',
	'locationId'    => $locationId,
];

$service2Data = [
	'description'   => 'Shine the howsadoodles',
	'serviceLineId' => 2,  // see https://api.servicetrade.com/api/docs#resource-serviceline
	'windowStart'   => strtotime('today'),
	'windowEnd'     => strtotime('tomorrow'),
	'duration'      => '1800',
	'locationId'    => $locationId,
];

$service1 = $st->post('/servicerequest', $service1Data);
$service2 = $st->post('/servicerequest', $service2Data);

// Create a job to perform those services
$jobData = [
	'locationId'        => $locationId,
	'vendorId'          => $myInfo['user']['company']['id'],
	'customerId'        => $customerId,
	'ownerId'           => $myInfo['user']['id'],
	'type'              => 'service_call',
	'serviceRequestIds' => [$service1['id'], $service2['id']],
];

$job = $st->post('/job', $jobData);

// Schedule the appointment and assign myself as the technician
$appointmentId = $job['currentAppointment']['id'];
$appointmentData = [
	'status'      => 'scheduled',
	'windowStart' => strtotime('+1 day 3 hours'),
	'windowEnd'   => strtotime('+1 day 4 hours 15 minutes'),
	'techIds'     => [$myInfo['user']['id']],
];
$appointment = $st->put('/appointment/' . $appointmentId, $appointmentData);

echo "Created job {$job['number']} for {$location['name']}!\n";
$st->logout();
```

## Upload an avatar image

```php
// see basic example for authentication, then...
$myInfo = $st->get('/auth');
$file = '/path/to/new_avatar.png';
$attachmentParams = [
	'purposeId'  => 8,  // avatar purpose ID, see https://api.servicetrade.com/api/docs#constants-attachment-purpose
	'entityType' => 4,  // user entity type, see https://api.servicetrade.com/api/docs#constants-entity-type
	'entityId'   => $myInfo['user']['id'],
];

$upload = $st->attach($file, $attachmentParams);
$st->logout();
```
