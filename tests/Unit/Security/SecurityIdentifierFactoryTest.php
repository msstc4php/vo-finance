<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Security;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\CfiGroups;
use MSSTC4PHP\ValueObject\Financial\Internal\CusipCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\FigiCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\IsinCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\Luhn;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Financial\Internal\SedolCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Security\Cfi;
use MSSTC4PHP\ValueObject\Financial\Security\Cusip;
use MSSTC4PHP\ValueObject\Financial\Security\Figi;
use MSSTC4PHP\ValueObject\Financial\Security\Fisn;
use MSSTC4PHP\ValueObject\Financial\Security\Isin;
use MSSTC4PHP\ValueObject\Financial\Security\Lei;
use MSSTC4PHP\ValueObject\Financial\Security\Mic;
use MSSTC4PHP\ValueObject\Financial\Security\SecurityIdentifier;
use MSSTC4PHP\ValueObject\Financial\Security\SecurityIdentifierFactory;
use MSSTC4PHP\ValueObject\Financial\Security\Sedol;
use MSSTC4PHP\ValueObject\Financial\Security\Upi;
use MSSTC4PHP\ValueObject\Financial\Security\Valor;
use MSSTC4PHP\ValueObject\Financial\Security\Wkn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
#[UsesClass(Upi::class)]
#[UsesClass(Valor::class)]
final class SecurityIdentifierFactoryTest extends TestCase
{
    public function testPicksTheOnlyMatch(): void
    {
        self::assertInstanceOf(Isin::class, SecurityIdentifierFactory::fromString('US0378331005'));
        self::assertInstanceOf(Lei::class, SecurityIdentifierFactory::fromString('F50EOCWSQFAUVO9Q8Z97'));
        self::assertInstanceOf(Upi::class, SecurityIdentifierFactory::fromString('QZ4TM8GJN9XW'));
        self::assertInstanceOf(Figi::class, SecurityIdentifierFactory::fromString('BBG000BLNQ16'));
        self::assertInstanceOf(Fisn::class, SecurityIdentifierFactory::fromString('APPLE INC/SH'));
        self::assertInstanceOf(Cusip::class, SecurityIdentifierFactory::fromString('12345*AB7'));
    }

    /**
     * Valor takes no part, so the types it overlapped are reachable.
     *
     * @return iterable<string, array{string, class-string<SecurityIdentifier>}>
     */
    public static function formerlyShadowedByValor(): iterable
    {
        yield 'WKN' => ['519000', Wkn::class];
        yield 'WKN of letters and digits' => ['A0D9PT', Wkn::class];
        yield 'CFI' => ['OCAFPS', Cfi::class];
        yield 'CUSIP' => ['037833100', Cusip::class];
        yield 'CUSIP with letters' => ['037833AL4', Cusip::class];
        yield 'SEDOL' => ['1222171', Sedol::class];
        yield 'SEDOL with letters' => ['B1F3M59', Sedol::class];
        yield 'MIC' => ['1234', Mic::class];
        yield 'MIC with letters' => ['XNAS', Mic::class];
    }

    /**
     * @param class-string<SecurityIdentifier> $class
     */
    #[DataProvider('formerlyShadowedByValor')]
    public function testBuildsTheTypeAValorUsedToShadow(string $value, string $class): void
    {
        $identifier = SecurityIdentifierFactory::fromString($value);

        self::assertInstanceOf($class, $identifier);
        self::assertSame($value, (string) $identifier);
    }

    public function testAValueOnlyAValorAcceptsIsNotRecognised(): void
    {
        self::assertInstanceOf(Valor::class, Valor::tryFrom('ABC'));

        $this->expectException(InvalidSecurityIdentifierException::class);
        SecurityIdentifierFactory::fromString('ABC');
    }

    public function testLowercaseIsin(): void
    {
        self::assertInstanceOf(Isin::class, SecurityIdentifierFactory::fromString('us0378331005'));
    }

    /**
     * @return iterable<string, array{string, list<class-string>}>
     */
    public static function ambiguous(): iterable
    {
        yield 'ISIN and FIGI' => ['MWG26Z8NW5M4', [Isin::class, Figi::class]];
        yield 'CFI and WKN' => ['ESVUFR', [Cfi::class, Wkn::class]];
    }

    /**
     * @param list<class-string> $candidates
     */
    #[DataProvider('ambiguous')]
    public function testOverlapsListEveryAcceptingType(string $value, array $candidates): void
    {
        try {
            SecurityIdentifierFactory::fromString($value);
            self::fail('Expected an exception.');
        } catch (AmbiguousValueException $e) {
            self::assertSame($candidates, $e->candidates());
        }
    }

    public function testNothingMatches(): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        SecurityIdentifierFactory::fromString('not an identifier!');
    }
}
