<?php
/**
 * Copyright: STORY DESIGN Sp. z o.o.
 * Author: Yaroslav Shatkevich
 * Date: 22.09.2016
 * Time: 16:21
 */

namespace FreshmailTest;


use Freshmail\Command\Subscriber\Create;
use Freshmail\Command\Util\Ping;
use Freshmail\Exception\FreshmailException;
use Freshmail\Exception\InvalidCommandException;
use Freshmail\Model\Configuration;
use Freshmail\Model\Subscriber;
use Freshmail\Model\SubscriptionList;
use Freshmail\Service\Freshmail;
use PHPUnit\Framework\TestCase;

class FreshmailTest extends TestCase
{
    /**
     * @var Freshmail
     */
    private $freshmail;

    public function setUp()
    {
        $configuration = new Configuration();
        $configuration->key = $this->getEnvironmentVariable('API_KEY');
        $configuration->secret = $this->getEnvironmentVariable('API_SECRET');

        $this->freshmail = new Freshmail($configuration);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Freshmail::class, $this->freshmail);
    }

    public function testGetRequest()
    {
        $responseData = $this->freshmail->get('/ping');

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayNotHasKey('status', $responseData);
    }

    public function testRequestException()
    {
        try {
            $this->freshmail->get('/wrong-path');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(FreshmailException::class, $exception);
        }
    }

    public function testPostRequest()
    {
        $responseData = $this->freshmail->post('/ping', ['ping' => 'pong']);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('ping', $responseData['data']);
    }

    public function testExecuteCommand()
    {
        $result = $this->freshmail->executeCommand(new Ping());

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('pong', $result['data']);

        try {
            $this->freshmail->executeCommand(new Create(new Subscriber()));
        } catch (\Exception $exception) {
            $this->assertInstanceOf(InvalidCommandException::class, $exception);
        }

        $list = new SubscriptionList();
        $list->setHash(getenv('TEST_LIST'));

        $subscriber = new Subscriber();
        $subscriber->setEmail(md5(time()) . '@email.com')
            ->setList($list);

        try {
            $this->freshmail->executeCommand(new Create($subscriber));
        } catch (\Exception $exception) {
            $this->assertInstanceOf(FreshmailException::class, $exception);
        }
    }

    private function getEnvironmentVariable($key)
    {
        if (!$value = getenv($key)) {
            throw new \Exception('Environment variable not found');
        }

        return $value;
    }
}