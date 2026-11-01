<?php

/**
 * Signature-only stub for the WP-CLI runtime (MIT), for static analysis only.
 * WP-CLI is not a dependency: the `wp` binary provides this class, and only when a command runs.
 * Not shipped in the release.
 */

/**
 * @method static bool add_command(string $name, callable|string $callable, array<string, mixed> $args = [])
 */
class WP_CLI
{
    /**
     * @param callable|string $callable
     * @param array<string, mixed> $args
     */
    public static function add_command($name, $callable, $args = [])
    {
    }

    /**
     * @param string|array<int, string> $message
     */
    public static function success($message)
    {
    }

    /**
     * @param string|array<int, string> $message
     * @param bool|int $exit
     */
    public static function error($message, $exit = true)
    {
    }

    /**
     * @param string $message
     */
    public static function log($message)
    {
    }

    /**
     * @param string $message
     */
    public static function warning($message)
    {
    }

    /**
     * @param string $message
     */
    public static function line($message = '')
    {
    }

    /**
     * @param string $question
     * @param array<string, mixed> $assoc_args
     */
    public static function confirm($question, $assoc_args = [])
    {
    }
}
