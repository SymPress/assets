<?php

declare(strict_types=1);

namespace SymPress\Assets;

final readonly class AssetExtensionMerger
{
    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $incoming
     * @return array<string, mixed>
     */
    #[\NoDiscard]
    public function merge(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            $existing[$key] = match ($key) {
                'dependencies' => $this->mergeList($existing[$key] ?? [], $value),
                'inline' => $this->mergeInline($existing[$key] ?? [], $value),
                'attributes',
                'localize',
                'translation' => $this->replaceMap($existing[$key] ?? [], $value),
                default => $value,
            };
        }

        /** @var AssetExtensionConfig $existing */
        return $existing;
    }

    /** @return list<string> */
    private function mergeList(mixed $existing, mixed $incoming): array
    {
        return [
            ...$this->listFrom($existing),
            ...$this->listFrom($incoming),
        ];
    }

    /** @return array{before?: list<string>, after?: list<string>} */
    private function mergeInline(mixed $existing, mixed $incoming): array
    {
        $existing = is_array($existing) ? $existing : [];
        $incoming = is_array($incoming) ? $incoming : [];
        $merged = [];

        foreach (['before', 'after'] as $position) {
            if (!array_key_exists($position, $existing) && !array_key_exists($position, $incoming)) {
                continue;
            }

            $merged[$position] = $this->mergeList($existing[$position] ?? [], $incoming[$position] ?? []);
        }

        return $merged;
    }

    /** @return array<string, mixed> */
    private function replaceMap(mixed $existing, mixed $incoming): array
    {
        $merged = [];

        foreach ([is_array($existing) ? $existing : [], is_array($incoming) ? $incoming : []] as $map) {
            foreach ($map as $key => $value) {
                $merged[(string) $key] = $value;
            }
        }

        return $merged;
    }

    /** @return list<string> */
    private function listFrom(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            return is_scalar($value) || $value instanceof \Stringable
                ? [(string) $value]
                : [];
        }

        $list = array_is_list($value) ? $value : [$value];
        $normalized = [];

        foreach ($list as $item) {
            if (!is_scalar($item) && !($item instanceof \Stringable)) {
                continue;
            }

            $normalized[] = (string) $item;
        }

        return $normalized;
    }
}
