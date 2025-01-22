<?php

declare(strict_types=1);

namespace Fmasa\SentryBreadcrumbsMonologHandler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Sentry\Breadcrumb;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

final class BreadcrumbsHandler extends AbstractProcessingHandler
{
    private const array LEVELS = [
        Level::Debug->value => Breadcrumb::LEVEL_DEBUG,
        Level::Info->value => Breadcrumb::LEVEL_INFO,
        Level::Notice->value => Breadcrumb::LEVEL_INFO,
        Level::Warning->value => Breadcrumb::LEVEL_WARNING,
        Level::Error->value => Breadcrumb::LEVEL_ERROR,
        Level::Critical->value => Breadcrumb::LEVEL_FATAL,
        Level::Alert->value => Breadcrumb::LEVEL_FATAL,
        Level::Emergency->value => Breadcrumb::LEVEL_FATAL,
    ];

    private HubInterface $hub;

    public function __construct(HubInterface $hub)
    {
        parent::__construct();
        $this->hub = $hub;
    }

    protected function write(LogRecord $record): void
    {
        $this->hub->configureScope(function (Scope $scope) use ($record): void {
            $scope->addBreadcrumb(
                new Breadcrumb(
                    $this->convertMonologLevelToSentryLevel($record->level->value),
                    Breadcrumb::TYPE_DEFAULT,
                    $record->channel,
                    $record->message,
                    $record->context ?? null,
                    $record->datetime->getTimestamp(),
                )
            );
        });
    }

    /**
     * Translates the Monolog level into the Sentry breadcrumbs level.
     *
     * @param int $level The Monolog log level
     */
    private function convertMonologLevelToSentryLevel(int $level): string
    {
        return self::LEVELS[$level] ?? Breadcrumb::LEVEL_INFO;
    }
}
