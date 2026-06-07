<?php

declare(strict_types=1);

namespace SymPress\Assets\OutputFilter;

final readonly class HtmlAttributes
{
    /**
     * @param array<string, mixed> $attributes
     * @param list<string>         $excludedAttributes
     */
    public static function render(array $attributes, array $excludedAttributes = []): string
    {
        $html = '';

        foreach (self::normalize($attributes, $excludedAttributes) as $key => $value) {
            $html .= sprintf(
                ' %s="%s"',
                esc_attr($key),
                esc_attr(true === $value ? $key : $value),
            );
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param list<string>         $excludedAttributes
     */
    public static function applyToTag(
        \WP_HTML_Tag_Processor $tag,
        array $attributes,
        array $excludedAttributes = [],
    ): void {
        foreach (self::normalize($attributes, $excludedAttributes) as $key => $value) {
            if (null !== $tag->get_attribute($key)) {
                continue;
            }

            $tag->set_attribute($key, true === $value ? $key : $value);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     * @param list<string>         $excludedAttributes
     *
     * @return array<string, string|true>
     */
    public static function normalize(array $attributes, array $excludedAttributes = []): array
    {
        $normalized = [];
        $excludedAttributes = array_fill_keys(array_map('strtolower', $excludedAttributes), true);

        foreach ($attributes as $key => $value) {
            $key = (string) $key;
            $normalizedKey = strtolower($key);

            if (isset($excludedAttributes[$normalizedKey]) || !self::validAttributeName($key)) {
                continue;
            }

            if (false === $value || null === $value) {
                continue;
            }

            if (true === $value) {
                $normalized[$key] = true;
                continue;
            }

            if (is_scalar($value) || $value instanceof \Stringable) {
                $normalized[$key] = (string) $value;
            }
        }

        return $normalized;
    }

    private static function validAttributeName(string $name): bool
    {
        return 1 === preg_match('/^[A-Za-z_:][A-Za-z0-9:_.-]*$/', $name);
    }
}
