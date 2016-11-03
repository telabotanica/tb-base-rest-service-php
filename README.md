# tb-base-rest-service
A simple PHP class that helps building REST webservices

## install
```
composer install telabotanica/tb-base-rest-service
```

## usage
```php
class MyService extends BaseRestServiceTB {
	public function __construct() {
		$config = array(
			"domain_root" => "http://sub.mydomain.tld",
			"base_uri" => "/my-service-root-folder",
			"first_resource_separator" => "/"
		);
		parent::__construct($config);
	}

	protected function get() {
		// ...
	}

	protected function post() {
		// ...
	}
}
```

## config parameters
### mandatory
- __domain_root__ : root URL of your server
- __base_uri__ : base path of your webservice

### non-mandatory
- __first_resource_separator__ : first character following your base_uri (typically "/" or ":", defaults to "/")
