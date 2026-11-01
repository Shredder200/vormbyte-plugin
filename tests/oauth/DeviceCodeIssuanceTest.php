<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

use Vormbyte\OAuth\Endpoints\Device;
use Vormbyte\OAuth\Repositories\DeviceCodeRepository;
use Vormbyte\OAuth\Repositories\PendingDeviceCodeStore;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/oauth/repositories/device-code-repository.php';
require_once __DIR__ . '/../../includes/oauth/endpoints/device.php';

/**
 * Stands in for the device-code table with the one property the endpoint depends on: the user code
 * is unique, so a write can be refused because someone else already holds that code. Writes can also
 * be made to fail outright, which is what a database error looks like from here.
 */
final class FakePendingDeviceCodes implements PendingDeviceCodeStore
{
    /** @var array<string, string> normalized user code => device code */
    public array $written = [];

    /** @var list<string> codes a losing write handed to another authorization */
    public array $taken = [];

    public int $attempts = 0;

    public int $prunes = 0;

    /** How many writes lose the unique user code before one succeeds. */
    public int $collisions = 0;

    /** When true every write fails for a reason that has nothing to do with the user code. */
    public bool $writes_fail = false;

    public function create(
        string $device_code,
        string $user_code,
        string $client_id,
        string $scopes,
        int $expires_in,
    ): bool {
        $this->attempts++;
        if ($this->writes_fail) {
            return false;
        }
        if ($this->collisions > 0) {
            $this->collisions--;
            $this->taken[] = $user_code;
            return false;
        }
        $this->written[$user_code] = $device_code;
        return true;
    }

    /**
     * @return array{
     *     device_code_hash: string,
     *     client_id: string,
     *     user_id: int,
     *     scopes: string,
     *     status: string,
     *     expires_at: string,
     *     last_polled_at: string|null,
     * }|null
     */
    public function find_by_user_code(string $user_code): ?array
    {
        if (!in_array($user_code, $this->taken, strict: true) && !array_key_exists($user_code, $this->written)) {
            return null;
        }
        return [
            'device_code_hash' => hash('sha256', $user_code),
            'client_id' => 'someone-else',
            'user_id' => 0,
            'scopes' => 'mcp',
            'status' => DeviceCodeRepository::STATUS_PENDING,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 600),
            'last_polled_at' => null,
        ];
    }

    public function count_pending(?string $client_id = null): int
    {
        return count($this->written);
    }

    public function prune_expired(): void
    {
        $this->prunes++;
    }
}

final class DeviceCodeIssuanceTest extends TestCase
{
    public function testALostUserCodeRaceIsRetriedAndTheDisplayedCodeIsTheStoredOne(): void
    {
        $codes = new FakePendingDeviceCodes();
        $codes->collisions = 2;

        $user_code = Device\mint_pending_authorization($codes, 'device-code', 'device-client', 'mcp');

        self::assertNotNull($user_code);
        self::assertSame(3, $codes->attempts);
        // The operator is shown the formatted code, and the row holds the normalized form the
        // verification page will look up — the two must be the same code.
        self::assertSame(['device-code'], array_values($codes->written));
        self::assertSame([Device\normalize_user_code($user_code)], array_keys($codes->written));
        self::assertStringContainsString('-', $user_code);
    }

    public function testAWriteThatFailsForAnyOtherReasonIsNotRetried(): void
    {
        $codes = new FakePendingDeviceCodes();
        $codes->writes_fail = true;

        // A failure the user code cannot explain will repeat identically, so it is reported at once
        // rather than spun on — and it must never be reported as a usable authorization.
        self::assertNull(Device\mint_pending_authorization($codes, 'device-code', 'device-client', 'mcp'));
        self::assertSame(1, $codes->attempts);
        self::assertSame([], $codes->written);
    }

    public function testPersistentCollisionsGiveUpInsteadOfLoopingForever(): void
    {
        $codes = new FakePendingDeviceCodes();
        $codes->collisions = PHP_INT_MAX;

        self::assertNull(Device\mint_pending_authorization($codes, 'device-code', 'device-client', 'mcp'));
        self::assertSame(Device\USER_CODE_ATTEMPTS, $codes->attempts);
        self::assertSame([], $codes->written);
    }

    public function testTheExpiryInstantItselfCountsAsExpired(): void
    {
        $codes = new DeviceCodeRepository();

        // A code lives until expires_at, not through the second it names, so the boundary second
        // cannot be raced into an approval.
        self::assertTrue($codes->is_expired(gmdate('Y-m-d H:i:s')));
        self::assertTrue($codes->is_expired(gmdate('Y-m-d H:i:s', time() - 1)));
        self::assertFalse($codes->is_expired(gmdate('Y-m-d H:i:s', time() + 60)));
        // A row we cannot date must not authorize.
        self::assertTrue($codes->is_expired('not a timestamp'));
    }

    public function testDeviceCodesAreNotRetainedLikeTheRowsAnApprovedGrantProduces(): void
    {
        // The device table is the only one an unauthenticated request can write to. Keeping it for
        // the thirty days the token tables use is what turned a rate-limited endpoint into
        // sustained growth, so the retention stays short enough to bound it.
        self::assertLessThanOrEqual(3_600, DeviceCodeRepository::EXPIRED_RETENTION);
        self::assertGreaterThan(Device\CODE_TTL, DeviceCodeRepository::EXPIRED_RETENTION);
    }
}
