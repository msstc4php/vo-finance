<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil;

/**
 * The type of a Pix key, decoded from the formats the DICT stores; the value is the DICT's `KeyType`. A key of no
 * listed format has no type, and is never refused for that ("new types may appear", the DICT API says).
 *
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/pix/Regulamento_Pix/X_ManualOperacionaldoDICT.pdf BCB, Manual Operacional do DICT 8.5, section 1 "Chaves Pix"
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/pix/API-DICT.html BCB, API do DICT 2.13.0, "Chave" (regular expression per key type)
 * @see docs/sources/FINANCE.md "National EMVCo profiles"
 */
enum PixKeyType: string
{
    case Cpf = 'CPF';
    case Cnpj = 'CNPJ';
    case Phone = 'PHONE';
    case Email = 'EMAIL';
    case Evp = 'EVP';

    private const int MAX_EMAIL_LENGTH = 77;

    public static function ofKey(string $key): ?self
    {
        return array_find(self::cases(), static fn (self $type): bool => $type->matches($key));
    }

    private function matches(string $key): bool
    {
        return match ($this) {
            self::Cpf => \preg_match('/^[0-9]{11}$/D', $key) === 1,
            self::Cnpj => \preg_match('/^[A-Z0-9]{12}[0-9]{2}$/D', $key) === 1,
            self::Phone => \preg_match('/^\+[1-9][0-9]{1,14}$/D', $key) === 1,
            self::Email => \strlen($key) <= self::MAX_EMAIL_LENGTH
                && \preg_match('/^[a-z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$/D', $key) === 1,
            self::Evp => \preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D', $key) === 1,
        };
    }
}
