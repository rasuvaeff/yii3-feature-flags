<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\Yii3FeatureFlags\PercentageRollout;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(PercentageRollout::class)]
final class PercentageRolloutTest
{
    private PercentageRollout $rollout;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->rollout = new PercentageRollout();
    }

    public function zeroRolloutAlwaysDisabled(): void
    {
        Assert::false(
            $this->rollout->isEnabled(salt: 'test', subjectId: 'user-1', rolloutPercentage: 0),
        );
    }

    public function hundredRolloutAlwaysEnabled(): void
    {
        Assert::true(
            $this->rollout->isEnabled(salt: 'test', subjectId: 'user-1', rolloutPercentage: 100),
        );
    }

    public function sameSubjectGetsSameResult(): void
    {
        $result1 = $this->rollout->isEnabled(salt: 'test', subjectId: 'user-42', rolloutPercentage: 50);
        $result2 = $this->rollout->isEnabled(salt: 'test', subjectId: 'user-42', rolloutPercentage: 50);

        Assert::same($result1, $result2);
    }

    public function hashIsDeterministicWithKnownValues(): void
    {
        $enabled = $this->rollout->isEnabled(salt: 'test-salt', subjectId: 'user-1', rolloutPercentage: 50);

        $digest = hash(algo: 'sha256', data: 'test-salt:user-1');
        $bucket = hexdec(hex_string: substr(string: $digest, offset: 0, length: 8)) % 100;
        $expected = $bucket < 50;

        Assert::same($expected, $enabled);
    }

    public function hashUsesColonSeparator(): void
    {
        $withColon = $this->rollout->isEnabled(salt: 'a', subjectId: 'b', rolloutPercentage: 50);
        $withoutColon = $this->rollout->isEnabled(salt: 'ab', subjectId: '', rolloutPercentage: 50);

        Assert::notSame($withColon, $withoutColon);
    }

    public function hashUsesFirst8HexChars(): void
    {
        $digest = hash(algo: 'sha256', data: 'test:user-1');
        $bucket8 = hexdec(hex_string: substr(string: $digest, offset: 0, length: 8)) % 100;
        $bucket7 = hexdec(hex_string: substr(string: $digest, offset: 0, length: 7)) % 100;
        $bucket9 = hexdec(hex_string: substr(string: $digest, offset: 0, length: 9)) % 100;

        $result = $this->rollout->isEnabled(salt: 'test', subjectId: 'user-1', rolloutPercentage: 50);
        $expected = $bucket8 < 50;

        Assert::same($expected, $result);

        Assert::notSame($bucket7, $bucket8);
        Assert::notSame($bucket9, $bucket8);
    }

    public function moduloIs100Not99Or101(): void
    {
        $digest = hash(algo: 'sha256', data: 'test:user-1');
        $bucket = hexdec(hex_string: substr(string: $digest, offset: 0, length: 8));
        $mod100 = $bucket % 100;
        $mod99 = $bucket % 99;
        $mod101 = $bucket % 101;

        $result = $this->rollout->isEnabled(salt: 'test', subjectId: 'user-1', rolloutPercentage: 50);
        $expected = $mod100 < 50;

        Assert::same($expected, $result);
        Assert::notSame($mod99, $mod100);
        Assert::notSame($mod101, $mod100);
    }

    public function comparisonIsStrictLessThan(): void
    {
        $digest = hash(algo: 'sha256', data: 'test:user-1');
        $bucket = hexdec(hex_string: substr(string: $digest, offset: 0, length: 8)) % 100;

        $resultAtBucket = $this->rollout->isEnabled(salt: 'test', subjectId: 'user-1', rolloutPercentage: $bucket);
        $resultAtBucketPlus1 = $this->rollout->isEnabled(salt: 'test', subjectId: 'user-1', rolloutPercentage: $bucket + 1);

        Assert::false($resultAtBucket);
        Assert::true($resultAtBucketPlus1);
    }

    public function differentSaltsGiveDifferentDistribution(): void
    {
        $results = [];

        for ($i = 1; $i <= 100; $i++) {
            $a = $this->rollout->isEnabled(salt: 'salt-a', subjectId: (string) $i, rolloutPercentage: 50);
            $b = $this->rollout->isEnabled(salt: 'salt-b', subjectId: (string) $i, rolloutPercentage: 50);
            $results[] = $a !== $b;
        }

        Assert::true(array_filter($results) !== []);
    }

    public function fiftyPercentDistributesReasonably(): void
    {
        $enabled = 0;
        $total = 1000;

        for ($i = 1; $i <= $total; $i++) {
            if ($this->rollout->isEnabled(salt: 'test', subjectId: (string) $i, rolloutPercentage: 50)) {
                $enabled++;
            }
        }

        $ratio = $enabled / $total;

        Assert::true($ratio > 0.35);
        Assert::true($ratio < 0.65);
    }

    public function lowRolloutRarelyEnabled(): void
    {
        $enabled = 0;

        for ($i = 1; $i <= 1000; $i++) {
            if ($this->rollout->isEnabled(salt: 'test', subjectId: (string) $i, rolloutPercentage: 1)) {
                $enabled++;
            }
        }

        Assert::true($enabled < 30);
        Assert::true($enabled > 0);
    }

    public function highRolloutMostlyEnabled(): void
    {
        $enabled = 0;

        for ($i = 1; $i <= 1000; $i++) {
            if ($this->rollout->isEnabled(salt: 'test', subjectId: (string) $i, rolloutPercentage: 99)) {
                $enabled++;
            }
        }

        Assert::true($enabled > 970);
        Assert::true($enabled < 1000);
    }

    #[Property(runs: 300)]
    public function zeroPercentIsNeverEnabled(string $salt, string $subjectId): void
    {
        Assert::false($this->rollout->isEnabled(salt: $salt, subjectId: $subjectId, rolloutPercentage: 0));
    }

    /** @return array<string, ArbitraryInterface> */
    private function zeroPercentIsNeverEnabledGenerators(): array
    {
        return [
            'salt' => Gen::stringAscii(),
            'subjectId' => Gen::stringAscii(),
        ];
    }

    #[Property(runs: 300)]
    public function hundredPercentIsAlwaysEnabled(string $salt, string $subjectId): void
    {
        Assert::true($this->rollout->isEnabled(salt: $salt, subjectId: $subjectId, rolloutPercentage: 100));
    }

    /** @return array<string, ArbitraryInterface> */
    private function hundredPercentIsAlwaysEnabledGenerators(): array
    {
        return [
            'salt' => Gen::stringAscii(),
            'subjectId' => Gen::stringAscii(),
        ];
    }

    #[Property(runs: 500)]
    public function enablementIsMonotonicInPercentage(string $salt, string $subjectId, int $percentage, int $delta): void
    {
        $higher = min(100, $percentage + $delta);

        $atLower = $this->rollout->isEnabled(salt: $salt, subjectId: $subjectId, rolloutPercentage: $percentage);
        $atHigher = $this->rollout->isEnabled(salt: $salt, subjectId: $subjectId, rolloutPercentage: $higher);

        // Enabled at p implies enabled at every p' >= p (the bucket is fixed per subject).
        Assert::true(!$atLower || $atHigher);
    }

    /** @return array<string, ArbitraryInterface> */
    private function enablementIsMonotonicInPercentageGenerators(): array
    {
        return [
            'salt' => Gen::stringAscii(),
            'subjectId' => Gen::stringAscii(),
            'percentage' => Gen::intBetween(0, 100),
            'delta' => Gen::intBetween(0, 100),
        ];
    }
}
