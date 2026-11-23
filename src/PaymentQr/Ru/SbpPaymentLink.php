<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru;

use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Payment link of the Russian Faster Payments System (SBP): `https://qr.nspk.ru/` and the QR identifier (one path
 * segment of ASCII letters and digits), optionally followed by a query. Format only: NSPK publishes the link layout
 * and its query fields only in the SBP OPKTs standards on its closed participant portal, so neither the identifier
 * length nor any query field is checked. Scheme and host are compared case-insensitively; the link is kept as given.
 * {@see masked()} hides the identifier. At most 7089 characters, the largest QR content: a bound against abusive input.
 *
 * @see https://sbp.nspk.ru/file/pravila-informacionno-tekhnologicheskogo-vzaimodejstviya_v.4.0.pdf NSPK, Rules P.235 v4.0 (2024): the SBP OPKTs standards are published on the NSPK portal
 * @see docs/sources/FINANCE.md "NSPK SBP"
 */
final class SbpPaymentLink extends AbstractStringValueObject implements Maskable
{
    private const string TYPE = 'SbpPaymentLink';

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
     * The value of a query parameter as written; null when absent.
     *
     * @throws AmbiguousPaymentQrFieldException when the parameter repeats with distinct values
     */
    public function get(string $name): ?string
    {
        return $this->query?->single($name, self::TYPE);
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
        $prefix = \substr($this->value, 0, 19);

        return $prefix . Mask::keepEnds($this->id) . \substr($this->value, 19 + \strlen($this->id));
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
}
