<?php

namespace App\Services\WordPress;

/**
 * HMAC-SHA256 request signing shared by the panel and the WP plugin.
 *
 * Signature covers method, path, timestamp and the body hash, so a captured
 * request cannot be replayed against a different endpoint.
 */
class Signature
{
    public const TOLERANCE_SECONDS = 300;

    public static function payload(string $method, string $path, int $timestamp, string $body): string
    {
        return implode("\n", [
            mb_strtoupper($method),
            $path,
            (string) $timestamp,
            hash('sha256', $body),
        ]);
    }

    public static function make(string $secret, string $method, string $path, int $timestamp, string $body = ''): string
    {
        return hash_hmac('sha256', self::payload($method, $path, $timestamp, $body), $secret);
    }

    public static function verify(string $secret, string $signature, string $method, string $path, int $timestamp, string $body = ''): bool
    {
        if (abs(time() - $timestamp) > self::TOLERANCE_SECONDS) {
            return false;
        }

        return hash_equals(self::make($secret, $method, $path, $timestamp, $body), $signature);
    }
}
