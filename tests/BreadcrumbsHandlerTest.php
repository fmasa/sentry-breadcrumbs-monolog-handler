<?php

declare(strict_types=1);

namespace Fmasa\SentryBreadcrumbsMonologHandler;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

final class BreadcrumbsHandlerTest extends TestCase
{
    /** @var Scope */
    private $scope;

    /** @var BreadcrumbsHandler */
    private $handler;

    protected function setUp() : void
    {
        $this->scope = new Scope();

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())
            ->method('configureScope')
            ->willReturnCallback(function (callable $callback) {
                return $callback($this->scope);
            });

        $this->handler = new BreadcrumbsHandler($hub);
    }

    public function testWriteRecord() : void
    {
        $record = [
            'message' => 'Test message',
            'level' => Logger::WARNING,
            'context' => ['option' => 'value'],
            'extra' => [],
        ];

        $this->handler->handle($record);

        $event = new Event();

        $this->scope->applyToEvent($event, []);

        $breadcrumbs = $event->getBreadcrumbs();

        $this->assertCount(1, $breadcrumbs);
        $this->assertSame($record['message'], $breadcrumbs[0]->getMessage());
        $this->assertSame($record['context'], $breadcrumbs[0]->getMetadata());
        $this->assertSame('default', $breadcrumbs[0]->getType());
    }
}
