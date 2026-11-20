<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Security;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\CusipCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\FigiCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\IsinCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\Luhn;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Financial\Internal\SedolCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Security\Cfi;
use MSSTC4PHP\ValueObject\Financial\Security\CfiGroups;
use MSSTC4PHP\ValueObject\Financial\Security\Cusip;
use MSSTC4PHP\ValueObject\Financial\Security\Figi;
use MSSTC4PHP\ValueObject\Financial\Security\Fisn;
use MSSTC4PHP\ValueObject\Financial\Security\Isin;
use MSSTC4PHP\ValueObject\Financial\Security\Lei;
use MSSTC4PHP\ValueObject\Financial\Security\Mic;
use MSSTC4PHP\ValueObject\Financial\Security\SecurityIdentifierFactory;
use MSSTC4PHP\ValueObject\Financial\Security\Sedol;
use MSSTC4PHP\ValueObject\Financial\Security\Valor;
use MSSTC4PHP\ValueObject\Financial\Security\Wkn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SecurityIdentifierFactory::class)]
#[UsesClass(SecurityRules::class)]
#[UsesClass(IsinCheckDigit::class)]
#[UsesClass(Luhn::class)]
#[UsesClass(CusipCheckDigit::class)]
#[UsesClass(SedolCheckDigit::class)]
#[UsesClass(Iso7064Mod97::class)]
#[UsesClass(FigiCheckDigit::class)]
#[UsesClass(Isin::class)]
#[UsesClass(Lei::class)]
#[UsesClass(Figi::class)]
#[UsesClass(Cfi::class)]
#[UsesClass(CfiGroups::class)]
#[UsesClass(Fisn::class)]
#[UsesClass(Mic::class)]
#[UsesClass(Cusip::class)]
#[UsesClass(Sedol::class)]
#[UsesClass(Wkn::class)]
#[UsesClass(Valor::class)]
final class SecurityIdentifierFactoryTest extends TestCase
{
    public function testPicksTheOnlyMatch(): void
    {
        self::assertInstanceOf(Isin::class, SecurityIdentifierFactory::fromString('US0378331005'));
        self::assertInstanceOf(Lei::class, SecurityIdentifierFactory::fromString('F50EOCWSQFAUVO9Q8Z97'));
        self::assertInstanceOf(Figi::class, SecurityIdentifierFactory::fromString('BBG000BLNQ16'));
        self::assertInstanceOf(Cfi::class, SecurityIdentifierFactory::fromString('OCAFPS'));
        self::assertInstanceOf(Fisn::class, SecurityIdentifierFactory::fromString('APPLE INC/SH'));
        self::assertInstanceOf(Mic::class, SecurityIdentifierFactory::fromString('XNAS'));
        self::assertInstanceOf(Sedol::class, SecurityIdentifierFactory::fromString('B1F3M59'));
        self::assertInstanceOf(Cusip::class, SecurityIdentifierFactory::fromString('037833AL4'));
        self::assertInstanceOf(Wkn::class, SecurityIdentifierFactory::fromString('A0D9PT'));
    }

    public function testLowercaseIsin(): void
    {
        self::assertInstanceOf(Isin::class, SecurityIdentifierFactory::fromString('us0378331005'));
    }

    public function testIsinAndFigiAreAmbiguous(): void
    {
        try {
            SecurityIdentifierFactory::fromString('MWG26Z8NW5M4');
            self::fail('Expected an exception.');
        } catch (AmbiguousValueException $e) {
            self::assertSame([Isin::class, Figi::class], $e->candidates());
        }
    }

    public function testWknAndValorAreAmbiguous(): void
    {
        try {
            SecurityIdentifierFactory::fromString('519000');
            self::fail('Expected an exception.');
        } catch (AmbiguousValueException $e) {
            self::assertSame([Wkn::class, Valor::class], $e->candidates());
        }
    }

    public function testCfiAndWknAreAmbiguous(): void
    {
        $this->expectException(AmbiguousValueException::class);
        SecurityIdentifierFactory::fromString('ESVUFR');
    }

    public function testDigitsOnlyValorIsFound(): void
    {
        self::assertInstanceOf(Valor::class, SecurityIdentifierFactory::fromString('12345678'));
    }

    public function testLetteredValueIsNeverAValor(): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        SecurityIdentifierFactory::fromString('ABC');
    }

    public function testNothingMatches(): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        SecurityIdentifierFactory::fromString('not an identifier!');
    }
}
