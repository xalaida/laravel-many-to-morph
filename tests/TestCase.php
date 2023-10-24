<?php

namespace Nevadskiy\ManyToMorph\Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase as PhpUnit;

class TestCase extends PhpUnit
{
	protected function setUp(): void
	{
		$this->setUpCapsule();

		Model::unguard();
	}

	protected function setUpCapsule(): void
	{
		$capsule = new Capsule();

		$capsule->addConnection([
			'driver' => 'sqlite',
			'database' => ':memory:',
		]);

		$capsule->setAsGlobal();

		$capsule->bootEloquent();
	}

	protected function tearDown(): void
	{
		Capsule::connection()->disconnect();

		parent::tearDown();
	}
}
