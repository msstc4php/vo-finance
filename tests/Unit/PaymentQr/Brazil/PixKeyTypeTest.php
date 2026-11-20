<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Brazil;

use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixKeyType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PixKeyType::class)]
final class PixKeyTypeTest extends TestCase
{
    #[DataProvider('keys')]
    public function testDecodesTheKeyFormatsOfTheDict(string $key, ?PixKeyType $expected): void
    {
        self::assertSame($expected, PixKeyType::ofKey($key));
    }

    /**
     * @return iterable<string, array{string, ?PixKeyType}>
     */
    public static function keys(): iterable
    {
        // BCB, API do DICT 2.13.0, key type table: the official example of each type.
        yield 'DICT example CPF' => ['12345678901', PixKeyType::Cpf];
        yield 'DICT example CNPJ' => ['12345678901234', PixKeyType::Cnpj];
        yield 'DICT example phone' => ['+5510998765432', PixKeyType::Phone];
        yield 'DICT example e-mail' => ['pix@bcb.gov.br', PixKeyType::Email];
        yield 'DICT example EVP' => ['123e4567-e89b-12d3-a456-426655440000', PixKeyType::Evp];

        // BCB, Manual de Padroes para Iniciacao do Pix 2.10.0, section 2.6.3: the key of the static example.
        yield 'Pix manual example key' => ['123e4567-e12b-12d1-a456-426655440000', PixKeyType::Evp];

        yield 'alphanumeric CNPJ' => ['AB12CD34EF5612', PixKeyType::Cnpj];
        yield 'shortest phone' => ['+12', PixKeyType::Phone];
        yield 'longest phone' => ['+123456789012345', PixKeyType::Phone];
        yield 'e-mail of 77 characters' => [\str_repeat('a', 71) . '@b.com', PixKeyType::Email];
        yield 'e-mail with special characters and subdomains' => ["a.b!#\$%&'*+/=?^_`{|}~-@mail-1.example.co", PixKeyType::Email];

        yield 'CPF of 10 digits' => ['1234567890', null];
        yield 'CNPJ with lower-case letters' => ['ab12cd34ef5612', null];
        yield 'CNPJ with a letter in the check digits' => ['123456789012A4', null];
        yield 'formatted CPF' => ['123.456.789-01', null];
        yield 'phone without plus' => ['5510998765432', null];
        yield 'phone starting with zero' => ['+0510998765432', null];
        yield 'phone of 16 digits' => ['+1234567890123456', null];
        yield 'e-mail of 78 characters' => [\str_repeat('a', 72) . '@b.com', null];
        yield 'upper-case e-mail' => ['Pix@bcb.gov.br', null];
        yield 'e-mail without domain' => ['pix@', null];
        yield 'e-mail with a domain label ending in a hyphen' => ['pix@bcb-.gov.br', null];
        yield 'upper-case EVP' => ['123E4567-E89B-12D3-A456-426655440000', null];
        yield 'EVP without hyphens' => ['123e4567e89b12d3a456426655440000', null];
        yield 'EVP with a trailing character' => ['123e4567-e89b-12d3-a456-4266554400001', null];
        yield 'empty' => ['', null];
        yield 'CPF with a trailing newline' => ["12345678901\n", null];
        yield 'CNPJ with a trailing newline' => ["12345678901234\n", null];
        yield 'CNPJ with a trailing digit' => ['123456789012345', null];
        yield 'phone with a trailing newline' => ["+5510998765432\n", null];
        yield 'phone after a prefix' => ['tel+5510998765432', null];
        yield 'e-mail with a trailing newline' => ["pix@bcb.gov.br\n", null];
        yield 'EVP with a trailing newline' => ["123e4567-e89b-12d3-a456-426655440000\n", null];
        yield 'EVP after a prefix' => ['x123e4567-e89b-12d3-a456-426655440000', null];
    }
}
