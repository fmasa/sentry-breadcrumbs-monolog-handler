<?php

declare(strict_types=1);

namespace Fmasa\SentryBreadcrumbsMonologHandler;

use Mockery;
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

        $hub = Mockery::mock(HubInterface::class);
        $hub->shouldReceive('configureScope')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                return $callback($this->scope);
            });

        $this->handler = new BreadcrumbsHandler($hub);
    }

    public function testWriteRecord() : void
    {
        $record = [
            'message' => 'Test message',
            'channel' => 'app',
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
        $this->assertSame('app', $breadcrumbs[0]->getCategory());
    }

    protected function tearDown() : void
    {
        Mockery::close();
    }
}
