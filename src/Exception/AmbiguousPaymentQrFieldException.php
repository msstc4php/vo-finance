<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Exception;

use DomainException;

/**
 * Thrown by a single-value accessor of a payment QR profile when the payload carries several distinct values for the
 * field. The message names the profile, the field and the reason, never a value.
 */
final class AmbiguousPaymentQrFieldException extends DomainException
{
    /**
     * @param non-empty-string $profile
     * @param non-empty-string $field
     * @param non-empty-string $reason why no single value exists, e.g. "several distinct values"
     */
    public function __construct(string $profile, string $field, string $reason)
    {
        parent::__construct(\sprintf('%s cannot give a single value for %s: %s.', $profile, $field, $reason));
    }
}
