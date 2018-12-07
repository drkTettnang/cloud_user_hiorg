<?php

namespace OCA\User_Hiorg\Util;

use OCP\ILogger;

class LoggerProxy implements ILogger
{
	private $appName;
	private $logger;

	public function __construct($appName, ILogger $logger)
	{
		$this->appName = $appName;
		$this->logger = $logger;
	}
	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function emergency(string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->emergency($message, $context);
	}
	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function alert(string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->alert($message, $context);
	}
	/**
	 * Critical conditions.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function critical(string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->critical($message, $context);
	}
	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function error(string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->error($message, $context);
	}
	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function warning(string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->warning($message, $context);
	}
	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function notice(string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->notice($message, $context);
	}
	/**
	 * Interesting events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function info(string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->info($message, $context);
	}
	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function debug(string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->debug($message, $context);
	}
	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return mixed
	 * @since 7.0.0
	 */
	public function log(int $level, string $message, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->log($level, $message, $context);
	}
	/**
	 * Logs an exception very detailed
	 * An additional message can we written to the log by adding it to the
	 * context.
	 *
	 * <code>
	 * $logger->logException($ex, [
	 *     'message' => 'Exception during background job execution'
	 * ]);
	 * </code>
	 *
	 * @param \Exception | \Throwable $exception
	 * @param array $context
	 * @return void
	 * @since 8.2.0
	 */
	public function logException(\Throwable  $exception, array $context = [])
	{
		$context['app'] = $this->appName;

		$this->logger->logException($exception, $context);
	}
}
