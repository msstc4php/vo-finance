<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Exception;

use DomainException;

/**
 * Thrown by a single-value accessor of a payment QR profile when the payload carries several distinct values for the
 * field. The message names the profile and the field, never a value.
 */
final class AmbiguousPaymentQrFieldException extends DomainException
{
    /**
     * @param non-empty-string $profile
     * @param non-empty-string $field
     */
    public function __construct(string $profile, string $field)
    {
        parent::__construct(\sprintf('%s carries several distinct values for %s; use the list accessor.', $profile, $field));
    }
}
