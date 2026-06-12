<?php

declare(strict_types=1);

namespace SymPress\Assets\Security;

final readonly class InlineStyleContent
{
    private const string CUSTOM_PROPERTY_PATTERN = '/^--[A-Za-z_][A-Za-z0-9_-]*$/';

    private const string CLOSING_STYLE_TAG_PATTERN = '/<\s*\/\s*style/i';

    private const string SELECTOR_CONTROL_PATTERN = '/[<{}]/';

    public static function safeRawText(string $css): string
    {
        return preg_replace(self::CLOSING_STYLE_TAG_PATTERN, '<\/style', $css) ?? $css;
    }

    public static function safeSelector(string $selector): string
    {
        $selector = trim($selector);

        if ($selector === '' || preg_match(self::SELECTOR_CONTROL_PATTERN, $selector) === 1) {
            throw new \InvalidArgumentException(sprintf('Invalid CSS selector "%s".', $selector));
        }

        return $selector;
    }

    public static function customPropertyName(string $name): string
    {
        $name = str_starts_with($name, '--')
            ? $name
            : "--{$name}";

        if (preg_match(self::CUSTOM_PROPERTY_PATTERN, $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid CSS custom property name "%s".', $name));
        }

        return $name;
    }

    public static function customPropertyValue(mixed $value): string
    {
        if (!is_scalar($value) && !$value instanceof \Stringable) {
            throw new \InvalidArgumentException('CSS custom property values must be scalar or stringable.');
        }

        return self::safeRawText((string) $value);
    }
}
