<?php

class ServicetradeTest extends \PHPUnit\Framework\TestCase
{
	public function testConstructor_NoUsernameProvided_ThrowsError()
	{
		$this->expectException(\ArgumentCountError::class);
		new \Servicetrade();
	}

	public function testConstructor_NoPasswordProvided_ThrowsError()
	{
		$this->expectException(\ArgumentCountError::class);
		new \Servicetrade('myUser');
	}

	public function testConstructor_NoOptions_Authenticates()
	{
		$expectedAuthParams = ['username' => 'myUser', 'password' => 'myPass'];
		$mock = $this->mockServicetrade();
		$mock->expects($this->once())
			->method('execCurl')
			->with(
				'post',
				'/auth',
				[],
				[CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($expectedAuthParams)],
			);

		$servicetrade = $this->reflectServicetrade($mock, $expectedAuthParams['username'], $expectedAuthParams['password']);
		$baseUrl = $servicetrade->getProperty('baseUrl');
		$baseUrl->setAccessible(true);
		$this->assertEquals($baseUrl->getValue($mock), 'https://api.servicetrade.com/api');
	}

	public function testConstructor_ScalarBaseUrlOption_AuthenticatesWithProvidedBaseUrl()
	{
		$expectedAuthParams = ['username' => 'myUser', 'password' => 'myPass'];
		$expectedBaseUrl = 'https://fake.servicetrade.com/api';

		$mock = $this->mockServicetrade();
		$mock->expects($this->once())
			->method('execCurl')
			->with(
				'post',
				'/auth',
				[],
				[CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($expectedAuthParams)],
			);

		$servicetrade = $this->reflectServicetrade($mock, $expectedAuthParams['username'], $expectedAuthParams['password'], $expectedBaseUrl);

		$baseUrl = $servicetrade->getProperty('baseUrl');
		$baseUrl->setAccessible(true);
		$this->assertEquals($baseUrl->getValue($mock), $expectedBaseUrl);
	}

	public function testConstructor_OptionsArray_AuthenticatesWithProvidedOptions()
	{
		$expectedAuthParams = ['username' => 'myUser', 'password' => 'myPass'];
		$expectedBaseUrl = 'https://fake.servicetrade.com/api';
		$expectedUserAgent = 'FAKE APP';

		$mock = $this->mockServicetrade();
		$mock->expects($this->once())
			->method('execCurl')
			->with(
				'post',
				'/auth',
				[],
				[CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($expectedAuthParams)],
			);

		$servicetrade = $this->reflectServicetrade($mock, $expectedAuthParams['username'], $expectedAuthParams['password'], [
			'baseUrl' => $expectedBaseUrl,
			'userAgent' => $expectedUserAgent
		]);

		$baseUrl = $servicetrade->getProperty('baseUrl');
		$baseUrl->setAccessible(true);
		$this->assertEquals($baseUrl->getValue($mock), $expectedBaseUrl);

		$userAgent = $servicetrade->getProperty('userAgent');
		$userAgent->setAccessible(true);
		$this->assertEquals($userAgent->getValue($mock), $expectedUserAgent);
	}

	/////////////////////////////

	protected function mockServicetrade()
	{
		$mock = $this->getMockBuilder(Servicetrade::class)
			->disableOriginalConstructor()
			->setMethods(['execCurl'])
			->getMock();

		return $mock;
	}

	protected function reflectServicetrade($mock, $username, $password, $options=[])
	{
		$reflectedClass = new ReflectionClass('Servicetrade');
      $constructor = $reflectedClass->getConstructor();
		$constructor->invoke($mock, $username, $password, $options);

		return $reflectedClass;
	}
}
