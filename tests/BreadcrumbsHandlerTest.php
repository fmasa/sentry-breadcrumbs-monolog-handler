<?php

declare(strict_types=1);

namespace Fmasa\SentryBreadcrumbsMonologHandler;

use Mockery;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

use function defined;
use function method_exists;

final class BreadcrumbsHandlerTest extends TestCase
{
    private Scope $scope;

    private BreadcrumbsHandler $handler;

    protected function setUp(): void
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

    public function testWriteRecord(): void
    {
        $record = [
            'message' => 'Test message',
            'channel' => 'app',
            'level' => Logger::WARNING,
            'context' => ['option' => 'value'],
            'extra' => [],
        ];

        $breadcrumbs = $this->handleRecord($record);

        $this->assertCount(1, $breadcrumbs);
        $this->assertSame($record['message'], $breadcrumbs[0]->getMessage());
        $this->assertSame($record['context'], $breadcrumbs[0]->getMetadata());
        $this->assertSame('default', $breadcrumbs[0]->getType());
        $this->assertSame('app', $breadcrumbs[0]->getCategory());
    }

    public function testWriteRecordSetsCorrectLevel(): void
    {
        $record = [
            'message' => 'Test message',
            'channel' => 'app',
            'level' => Logger::ALERT,
            'context' => [],
            'extra' => [],
        ];

        $expectedLevel = defined(Breadcrumb::class . '::LEVEL_FATAL') ? 'fatal' : 'critical';

        $breadcrumbs = $this->handleRecord($record);

        $this->assertCount(1, $breadcrumbs);
        $this->assertSame($expectedLevel, $breadcrumbs[0]->getLevel());
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return Breadcrumb[]
     */
    private function handleRecord(array $record): array
    {
        $this->handler->handle($record);

        if (method_exists(Event::class, 'createEvent')) {
            // Sentry 3
            $event = Event::createEvent();
            $this->scope->applyToEvent($event);
        } else {
            // Sentry 2
            $event = new Event();
            $this->scope->applyToEvent($event, []);
        }

        return $event->getBreadcrumbs();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
