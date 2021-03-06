<?php

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class LoggingContentHandlerTest extends TestCase
{
    private $request;
    private $response;
    private $logger;
    private $config;

    public function setUp(): void
    {
        $this->request = (new RequestFactory)->createRequest('GET', '/path');
        $this->response = (new ResponseFactory)->createResponse();

        $this->logger = new class implements LoggerInterface {
            use LoggerTrait;

            public $messages = [];

            public function log($level, $message, array $context = [])
            {
                $this->messages[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context
                ];
            }
        };

        $this->config = [
            'error' => 'critical',
            'not-allowed' => 'info',
            'not-found' => 'info'
        ];
    }

    public function testNotFound()
    {
        $handler = new LoggingContentHandler(new PlainTextContentHandler, $this->logger, $this->config);
        $response = $handler->handleNotFound($this->request, $this->response);

        $m = $this->logger->messages;
        $this->assertCount(1, $m);
        $this->assertSame('info', $m[0]['level']);
        $this->assertSame('Page Not Found: /path', $m[0]['message']);
        $this->assertSame([], $m[0]['context']);
    }

    public function testNotAllowed()
    {
        $handler = new LoggingContentHandler(new PlainTextContentHandler, $this->logger, $this->config);
        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $m = $this->logger->messages;
        $this->assertCount(1, $m);
        $this->assertSame('info', $m[0]['level']);
        $this->assertSame('Method Not Allowed: GET on /path', $m[0]['message']);
        $this->assertSame([], $m[0]['context']);
    }

    public function testHandleException()
    {
        $ex = new ErrorException('exception message');

        $handler = new LoggingContentHandler(new PlainTextContentHandler, $this->logger, $this->config);
        $response = $handler->handleException($this->request, $this->response, $ex);

        $m = $this->logger->messages;
        $this->assertCount(1, $m);
        $this->assertSame('critical', $m[0]['level']);
        $this->assertSame('exception message', $m[0]['message']);
        $this->assertSame(1, $m[0]['context']['errorCode']);

        $this->assertSame('E_ERROR', $m[0]['context']['errorType']);
        $this->assertSame('ErrorException', $m[0]['context']['errorClass']);
        $this->assertSame(1, $m[0]['context']['errorCode']);
    }
}
