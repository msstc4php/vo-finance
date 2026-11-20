<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PayloadBound::class)]
#[UsesClass(InvalidPaymentQrException::class)]
final class PayloadBoundTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testTheLargestQrContentPasses(): void
    {
        PayloadBound::ensure(\str_repeat('я', 7089), 'Test');
    }

    #[DoesNotPerformAssertions]
    public function testTheLargestQrContentInFourByteCharactersPasses(): void
    {
        PayloadBound::ensure(\str_repeat("\u{1F600}", 7089), 'Test');
    }

    #[DoesNotPerformAssertions]
    public function testTextThatIsNotUtf8IsLeftToThePayloadsOwnCheck(): void
    {
        PayloadBound::ensure(\str_repeat("\xFF", 7090), 'Test');
    }

    public function testOneCharacterMoreIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Test must not exceed 7089 characters.');

        PayloadBound::ensure(\str_repeat('я', 7090), 'Test');
    }

    public function testMoreBytesThanFourPerCharacterAreRefusedBeforeCounting(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        PayloadBound::ensure(\str_repeat("\xFF", 7089 * 4 + 1), 'Test');
    }

    #[DoesNotPerformAssertions]
    public function testTheLargestSingleByteContentPasses(): void
    {
        PayloadBound::ensureSingleByte(\str_repeat("\xE0", 7089), 'Test');
    }

    public function testOneSingleByteCharacterMoreIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Test must not exceed 7089 characters.');

        PayloadBound::ensureSingleByte(\str_repeat("\xE0", 7090), 'Test');
    }
}
