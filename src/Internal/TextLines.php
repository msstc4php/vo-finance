<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;

/**
 * Splits a line-based payload (EPC QR, Swiss QR-bill) into its elements and keeps every separator, so the payload can
 * be rebuilt byte for byte. A separator is LF or CRLF; a carriage return anywhere else is refused. One separator after
 * the last element is kept as given and does not count as an empty element.
 *
 * @internal
 */
final readonly class TextLines
{
    /**
     * @param non-empty-list<string> $lines
     * @param list<non-empty-string> $separators LF or CRLF, one after each line but the last, plus a trailing one when present
     */
    private function __construct(
        public array $lines,
        private array $separators,
    ) {
    }

    /**
     * @param non-empty-string $type
     *
     * @throws InvalidPaymentQrException for invalid UTF-8 or a carriage return outside CRLF
     */
    public static function parse(string $value, string $type): self
    {
        if (\preg_match('//u', $value) !== 1) {
            throw new InvalidPaymentQrException(\sprintf('%s must be valid UTF-8.', $type));
        }

        $pieces = \explode("\n", $value);
        $last = \count($pieces) - 1;
        $lines = [];
        $separators = [];
        foreach ($pieces as $index => $piece) {
            if ($index < $last) {
                $crlf = \str_ends_with($piece, "\r");
                $separators[] = $crlf ? "\r\n" : "\n";
                $piece = $crlf ? \substr($piece, 0, -1) : $piece;
            }

            if (\str_contains($piece, "\r")) {
                throw new InvalidPaymentQrException(\sprintf('%s requires LF or CRLF as line separator.', $type));
            }

            $lines[] = $piece;
        }

        if ($last > 0 && $lines[$last] === '') {
            \array_pop($lines);
        }

        \assert($lines !== []);

        return new self($lines, $separators);
    }

    public function isUniform(): bool
    {
        return \count(\array_unique($this->separators)) <= 1;
    }

    public function line(int $index): string
    {
        return $this->lines[$index] ?? '';
    }

    /**
     * The payload with one line replaced and every separator as given.
     */
    public function withLine(int $index, string $line): string
    {
        $lines = $this->lines;
        $lines[$index] = $line;

        $text = '';
        foreach ($lines as $position => $current) {
            $text .= $current . ($this->separators[$position] ?? '');
        }

        return $text;
    }
}
