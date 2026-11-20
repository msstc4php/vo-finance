<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use Closure;
use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;

/**
 * The `name=value` pairs of a URI query, split on `&` and the first `=`, kept as written (no percent-decoding: UPI
 * writes a space as a bare `%`). A pair without `=` has the empty value; empty pairs are skipped.
 *
 * @internal
 */
final readonly class QueryString
{
    /**
     * @param list<array{string, string}> $pairs
     */
    private function __construct(private array $pairs)
    {
    }

    public static function parse(string $query): self
    {
        $pairs = [];
        foreach (\explode('&', $query) as $pair) {
            if ($pair !== '') {
                $parts = \explode('=', $pair, 2);
                $pairs[] = [$parts[0], $parts[1] ?? ''];
            }
        }

        return new self($pairs);
    }

    /**
     * @return list<string> in order of first appearance
     */
    public function names(): array
    {
        return \array_values(\array_unique(\array_column($this->pairs, 0)));
    }

    /**
     * @return list<string>
     */
    public function all(string $name): array
    {
        $values = [];
        foreach ($this->pairs as [$current, $value]) {
            if ($current === $name) {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * @param non-empty-string $type
     *
     * @throws AmbiguousPaymentQrFieldException when the parameter repeats with distinct values
     */
    public function single(string $name, string $type): ?string
    {
        $distinct = \array_values(\array_unique($this->all($name)));
        if (\count($distinct) > 1) {
            $field = \preg_match('/^[A-Za-z0-9_]{1,20}$/D', $name) === 1 ? 'the parameter ' . $name : 'the requested parameter';

            throw new AmbiguousPaymentQrFieldException($type, $field, 'several distinct values');
        }

        return $distinct[0] ?? null;
    }

    /**
     * The query with every value of `$name` replaced by the result of `$mask`.
     *
     * @param Closure(string): string $mask
     */
    public function withMasked(string $name, Closure $mask): string
    {
        $parts = [];
        foreach ($this->pairs as [$current, $value]) {
            $parts[] = $current . '=' . ($current === $name ? $mask($value) : $value);
        }

        return \implode('&', $parts);
    }
}
