<?php

declare(strict_types=1);

namespace Fmasa\SentryBreadcrumbsMonologHandler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Sentry\Breadcrumb;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use function defined;

final class BreadcrumbsHandler extends AbstractProcessingHandler
{
    /** @var array<string, string> */
    private static $levels = [
        Logger::DEBUG => Breadcrumb::LEVEL_DEBUG,
        Logger::INFO => Breadcrumb::LEVEL_INFO,
        Logger::NOTICE => Breadcrumb::LEVEL_INFO,
        Logger::WARNING => Breadcrumb::LEVEL_WARNING,
        Logger::ERROR => Breadcrumb::LEVEL_ERROR,
        // Logger::CRITICAL, Logger::ALERT and Logger::EMERGENCY are set in the constructor
    ];

    /** @var HubInterface */
    private $hub;

    public function __construct(HubInterface $hub)
    {
        parent::__construct();
        $this->hub = $hub;

        $criticalLevel                   = defined(Breadcrumb::class . '::LEVEL_FATAL') ? Breadcrumb::LEVEL_FATAL : Breadcrumb::LEVEL_CRITICAL;
        self::$levels[Logger::CRITICAL]  = $criticalLevel;
        self::$levels[Logger::ALERT]     = $criticalLevel;
        self::$levels[Logger::EMERGENCY] = $criticalLevel;
    }

    /**
     * @param array<string, mixed> $record
     */
    protected function write(array $record) : void
    {
        $this->hub->configureScope(function (Scope $scope) use ($record) : void {
            $scope->addBreadcrumb(
                new Breadcrumb(
                    $this->convertMonologLevelToSentryLevel($record['level']),
                    Breadcrumb::TYPE_DEFAULT,
                    $record['channel'],
                    $record['message'],
                    $record['context'] ?? null
                )
            );
        });
    }

    /**
     * Translates the Monolog level into the Sentry breadcrumbs level.
     *
     * @param int $level The Monolog log level
     */
    private function convertMonologLevelToSentryLevel(int $level) : string
    {
        return self::$levels[$level] ?? Breadcrumb::LEVEL_INFO;
    }
}
