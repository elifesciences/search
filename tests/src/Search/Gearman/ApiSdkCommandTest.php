<?php

namespace tests\eLife\Search\Gearman;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Gearman\Command\ApiSdkCommand;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use tests\eLife\Search\HttpClient;

class ApiSdkCommandTest extends PHPUnit_Framework_TestCase
{
    use HttpClient;

    private $gearman;
    /** @var ApiSdkCommand */
    private $command;

    public function setUp()
    {
        $this->gearman = new GearmanClientMock();
        $this->command = new ApiSdkCommand(new ApiSdk($this->getHttpClient()), $this->gearman);
    }

    public function testCanInstantiate()
    {
        $this->assertInstanceOf(ApiSdkCommand::class, $this->command);
    }

    public function testImportBlogArticles()
    {
        $this->command->importBlogArticles(new NullLogger());
    }
}
