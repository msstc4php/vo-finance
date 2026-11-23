<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru;

use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentPayload;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Payment link of the Russian Faster Payments System (SBP): `https://qr.nspk.ru/` and the QR identifier (one path
 * segment of ASCII letters and digits), optionally followed by a query. Format only: NSPK publishes the link layout
 * and its query fields only in the SBP OPKTs standards on its closed participant portal, so neither the identifier
 * length nor any query field is checked. Scheme and host are compared case-insensitively; the link is kept as given.
 * {@see masked()} hides the identifier and fails closed on the query: it keeps only the codes and amounts `type`,
 * `bank`, `sum`, `cur` and `crc` (names compared exactly) whose value has a code shape (`sum=Anna` gives `sum=***`).
 * With no published field list, any other pair may hold free text in its name as in its value, so it becomes
 * `***=***`, `***=` for an empty value and `***` without `=`; an empty pair stays empty. At most 7089 characters, the
 * largest QR content: a bound against abusive input.
 *
 * @see https://sbp.nspk.ru/file/pravila-informacionno-tekhnologicheskogo-vzaimodejstviya_v.4.0.pdf NSPK, Rules P.235 v4.0 (2024): the SBP OPKTs standards are published on the NSPK portal
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "NSPK SBP"
 */
final class SbpPaymentLink extends AbstractStringValueObject implements PaymentPayload
{
    /** The longest stored payload, in characters: the abuse bound of 7089; frozen for 1.x. */
    public const int MAX_LENGTH = PayloadBound::MAX_CHARACTERS;

    private const string TYPE = 'SbpPaymentLink';

    /**
     * The query fields kept visible, with the shape a code must have: `type`, `bank` and `crc` from the only link
     * examples found (third-party; NSPK publishes none), `sum` and `cur` by ruling. Loose on purpose: the shapes only
     * keep prose out.
     */
    private const array VISIBLE_PARAMETERS = [
        'type' => '/^\d{1,2}$/D',
        'bank' => '/^\d{1,12}$/D',
        'sum' => '/^\d{1,18}$/D',
        'cur' => '/^[A-Z]{3}$/D',
        'crc' => '/^[0-9A-Fa-f]{1,8}$/D',
    ];

    private const string PATTERN = '~^(https://qr\.nspk\.ru/)([A-Za-z0-9]+)(?:\?([\x21-\x22\x24-\x7E]*))?$~Di';

    /** @var non-empty-string */
    private string $id;

    private ?QueryString $query = null;

    /**
     * @return non-empty-string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * The value of a query parameter as written; null when absent or empty.
     *
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when the parameter repeats with distinct values
     */
    public function get(string $name): ?string
    {
        return $this->query?->filled($name, self::TYPE);
    }

    /**
     * @return list<string>
     */
    public function parameterNames(): array
    {
        return $this->query?->names() ?? [];
    }

    #[Override]
    public function masked(): string
    {
        $masked = \substr($this->value, 0, 19) . Mask::keepEnds($this->id);
        if (!$this->query instanceof QueryString) {
            return $masked;
        }

        return $masked . '?' . $this->query->rewritten($this->maskedPair(...));
    }

    #[Override]
    protected function parse(string $value): string
    {
        PayloadBound::ensure($value, self::TYPE);
        if (\preg_match(self::PATTERN, $value, $match) !== 1) {
            throw new InvalidPaymentQrException(\sprintf('%s requires https://qr.nspk.ru/, an identifier of letters and digits and an optional query without spaces or fragment.', self::TYPE));
        }

        $this->id = $match[2];
        $this->query = isset($match[3]) ? QueryString::parse($match[3]) : null;

        return $value;
    }

    private function maskedPair(string $name, ?string $value): string
    {
        $shape = self::VISIBLE_PARAMETERS[$name] ?? null;

        return match (true) {
            $value === null => Mask::TEXT,
            $shape === null => Mask::TEXT . '=' . Mask::text($value),
            $value === '', \preg_match($shape, $value) === 1 => $name . '=' . $value,
            default => $name . '=' . Mask::TEXT,
        };
    }
}
