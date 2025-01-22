<?php

declare(strict_types=1);

namespace Fmasa\SentryBreadcrumbsMonologHandler;

use DateTimeImmutable;
use Mockery;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

use function defined;

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
        $record = new LogRecord(
            new DateTimeImmutable('2025-01-22 10:11:12'),
            'app',
            Level::Warning,
            'Test message',
            ['option' => 'value'],
        );

        $breadcrumbs = $this->handleRecord($record);

        $this->assertCount(1, $breadcrumbs);
        $this->assertSame($record->message, $breadcrumbs[0]->getMessage());
        $this->assertSame($record->context, $breadcrumbs[0]->getMetadata());
        $this->assertSame('default', $breadcrumbs[0]->getType());
        $this->assertSame('app', $breadcrumbs[0]->getCategory());
    }

    public function testWriteRecordSetsCorrectLevel(): void
    {
        $record = new LogRecord(
            new DateTimeImmutable('2025-01-22 10:11:12'),
            'app',
            Level::Alert,
            'Test message',
        );

        $expectedLevel = defined(Breadcrumb::class . '::LEVEL_FATAL') ? 'fatal' : 'critical';

        $breadcrumbs = $this->handleRecord($record);

        $this->assertCount(1, $breadcrumbs);
        $this->assertSame($expectedLevel, $breadcrumbs[0]->getLevel());
    }

    /**
     * @return Breadcrumb[]
     */
    private function handleRecord(LogRecord $record): array
    {
        $this->handler->handle($record);

        $event = Event::createEvent();
        $this->scope->applyToEvent($event);

        return $event->getBreadcrumbs();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
