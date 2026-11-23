<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;

/**
 * A securities or entity identifier of this package; {@see SecurityIdentifierFactory::fromString()} returns one.
 *
 * Implemented by this package only; methods may be added in minor releases.
 */
interface SecurityIdentifier extends ValueObject, StringConvertible
{
}
