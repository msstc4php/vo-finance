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
    private function __construct(
        private string $query,
        private array $pairs,
    ) {
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

        return new self($query, $pairs);
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
     * As {@see single()}, with an empty value read as absent.
     *
     * @param non-empty-string $type
     *
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when the parameter repeats with distinct values
     */
    public function filled(string $name, string $type): ?string
    {
        $value = $this->single($name, $type);

        return $value === '' ? null : $value;
    }

    /**
     * The query as written with every value of `$name` replaced by the result of `$mask`; other pairs, pairs without
     * `=` and empty pairs stay as they are.
     *
     * @param Closure(string): string $mask
     */
    public function withMasked(string $name, Closure $mask): string
    {
        $prefix = $name . '=';
        $parts = [];
        foreach (\explode('&', $this->query) as $pair) {
            $parts[] = \str_starts_with($pair, $prefix) ? $prefix . $mask(\substr($pair, \strlen($prefix))) : $pair;
        }

        return \implode('&', $parts);
    }
}
