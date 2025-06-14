<?php
declare(strict_types=1);

namespace WP_PHPUnit_Framework\Event\Listener;

use PHPUnit\Event\TestRunner\ExecutionStarted as TestRunnerExecutionStartedEvent;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;
use PHPUnit\Runner\Version;

class GlTestRunnerExecutionStartedListener implements ExecutionStartedSubscriber
{
    private string $logFile;
    private bool $debug = false;

    public function __construct(string $logDir)
    {
        if (!is_dir($logDir) && !mkdir($logDir, 0777, true) && !is_dir($logDir)) {
            throw new \InvalidArgumentException(sprintf('Log directory "%s" does not exist and could not be created.', $logDir));
        }
        $this->logFile = rtrim($logDir, '/') . '/test_events.log';
        $this->debug = (bool) \WP_PHPUnit_Framework\get_setting('PHPUNIT_DEBUG', false);

        if ($this->debug) {
            // Initialize log file or append header if it's the first listener writing
            $header = sprintf("=== Test Event Log ===\nInitialized/Accessed at: %s\nPHPUnit Debugging: ENABLED\nLog File: %s\n===\n", date('Y-m-d H:i:s'), $this->logFile);
            // Check if file exists and is empty to avoid multiple headers if other listeners also write
            if (!file_exists($this->logFile) || filesize($this->logFile) === 0) {
                 file_put_contents($this->logFile, $header);
            } else {
                 file_put_contents($this->logFile, sprintf("--- Listener GlTestRunnerExecutionStartedListener activated at %s ---\n", date('Y-m-d H:i:s')), FILE_APPEND);
            }
        }
    }

    public function notify(TestRunnerExecutionStartedEvent $event): void
    {
        $this->log(sprintf(
            "Test Runner Execution Started. PHPUnit Version: %s",
            Version::id()
        ));
    }

    private function log(string $message): void
    {
        if (!$this->debug) {
            return;
        }
        $timestamp = date('Y-m-d H:i:s.u');
        $logMessage = sprintf("[%s] %s\n", $timestamp, $message);
        if (file_put_contents($this->logFile, $logMessage, FILE_APPEND) === false) {
            error_log(sprintf('Failed to write to TestListener log file (%s): %s', $this->logFile, $message));
        }
    }
}
