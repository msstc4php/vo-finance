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
 * Functional link of the Russian Faster Payments System (SBP): `https://`, one of the hosts NSPK's API documentation
 * names (`qr.nspk.ru` for QR_TYPE 01 and 02, `sub.nspk.ru` for 03, `b2b.cbrpay.ru` for B2B links), the QR identifier
 * (one path segment of ASCII letters and digits) and an optional query. Parse checks only that shape: NSPK gives the
 * identifier 32 characters and the query fields `type`, `bank`, `sum`, `cur` and `crc` their formats, and 1.0 accepted
 * links outside them, so {@see hasValidFunctionalLinkStructure()} reports them instead. Scheme and host are compared
 * case-insensitively; the link is kept as given. {@see masked()} hides the identifier and fails closed on the query:
 * it keeps only `type` (`01`, `02`, `03`), `bank` (12 digits), `sum` (1 to 12 digits, kopecks), `cur` (three capital
 * letters) and `crc` (four hex digits), names compared exactly; another value of these becomes `***`, so `sum=Anna`
 * and a phone number as `bank` give `***`.
 * Any other pair may hold free text in its name as in its value, so it becomes `***=***`, `***=` for an empty value
 * and `***` without `=`; an empty pair stays empty. At most 7089 characters, the largest QR content: a bound against
 * abusive input.
 *
 * @see https://sbp.nspk.ru/api/ NSPK, "СБП API для Участников СБП и Агентов ТСП", section "Формат Функциональной ссылки СБП / URL-based QR"
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "NSPK SBP"
 */
final class SbpPaymentLink extends AbstractStringValueObject implements PaymentPayload
{
    /** The longest stored payload, in characters: the abuse bound of 7089; frozen for 1.x. */
    public const int MAX_LENGTH = PayloadBound::MAX_CHARACTERS;

    private const string TYPE = 'SbpPaymentLink';

    /**
     * The query fields kept visible, with the shape a value must have. NSPK's table gives `bank` "String 12 символов",
     * `cur` "String 3 символа" and `crc` "String 4 символа"; only the digits, capitals and hex digits of its examples
     * stay visible.
     */
    private const array VISIBLE_PARAMETERS = [
        'type' => '/^0[123]$/D',
        'bank' => '/^\d{12}$/D',
        'sum' => self::SUM,
        'cur' => '/^[A-Z]{3}$/D',
        'crc' => '/^[0-9A-Fa-f]{4}$/D',
    ];

    /** SUM: "Сумма операции в копейках", "String 1...12 цифр". */
    private const string SUM = '/^\d{1,12}$/D';

    private const string PATTERN = '~^(https://(qr\.nspk\.ru|sub\.nspk\.ru|b2b\.cbrpay\.ru)/)([A-Za-z0-9]+)(?:\?([\x21-\x22\x24-\x7E]*))?$~Di';

    private const string B2B_HOST = 'b2b.cbrpay.ru';

    /** @var non-empty-string */
    private string $prefix;

    /** @var lowercase-string&non-empty-string */
    private string $host;

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
     * The decoded `type`; null when absent, empty or not one of NSPK's codes.
     *
     * @throws AmbiguousPaymentQrFieldException when `type` repeats with distinct values
     */
    public function qrType(): ?SbpQrType
    {
        return SbpQrType::tryFrom($this->get('type') ?? '');
    }

    /**
     * Whether the link has the structure of NSPK's table, which parse does not enforce: an identifier of 32
     * characters, and either no query or, on `qr.nspk.ru` and `sub.nspk.ru` only, `type` `01` or `02` on `qr.nspk.ru`
     * and `03` on `sub.nspk.ru`, `bank` of 12 characters, `sum` of 1 to 12 digits mandatory for `02` and absent for
     * `03`, `cur` of three characters whenever `sum` is present, and `crc` of four characters. A repeated parameter
     * with distinct values, or an empty one where a value is mandatory, gives false. Never refuses anything.
     */
    public function hasValidFunctionalLinkStructure(): bool
    {
        if (\strlen($this->id) !== 32) {
            return false;
        }

        if (!$this->query instanceof QueryString) {
            return true;
        }

        try {
            [$type, $bank, $sum, $cur, $crc] = \array_map($this->get(...), ['type', 'bank', 'sum', 'cur', 'crc']);
        } catch (AmbiguousPaymentQrFieldException) {
            return false;
        }

        return $this->host !== self::B2B_HOST
            && \in_array($type, ['01', '02', '03'], true)
            && $this->host === ($type === '03' ? 'sub.nspk.ru' : 'qr.nspk.ru')
            && \strlen($bank ?? '') === 12
            && \strlen($crc ?? '') === 4
            && match ($type) {
                '02' => $sum !== null,
                '03' => $sum === null,
                default => true,
            }
        && ($sum === null || (\preg_match(self::SUM, $sum) === 1 && $cur !== null))
        && ($cur === null || \strlen($cur) === 3);
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
        $masked = $this->prefix . Mask::keepEnds($this->id);
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
            throw new InvalidPaymentQrException(\sprintf('%s requires https://, an SBP host, an identifier of letters and digits and an optional query without spaces or fragment.', self::TYPE));
        }

        $this->prefix = $match[1];
        $this->host = \strtolower($match[2]);
        $this->id = $match[3];
        $this->query = isset($match[4]) ? QueryString::parse($match[4]) : null;

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
