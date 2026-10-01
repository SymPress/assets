<?php

declare(strict_types=1);

namespace SymPress\Assets\Loader;

use SymPress\Assets\Exception\FileNotFoundException;
use SymPress\Assets\Exception\InvalidResourceException;

final readonly class JsonFileReader
{
    /**
     * @return array<string, mixed>
     * @throws FileNotFoundException
     * @throws InvalidResourceException
     */
    #[\NoDiscard]
    public function read(mixed $resource): array
    {
        $resource = $this->readableFile($resource);

        $contents = \SymPress\Assets\IO\RequestFiles::shared()->contents($resource);

        if ($contents === null) {
            throw new FileNotFoundException(
                sprintf('The given file "%s" does not exist or is not readable.', esc_html($resource)),
            );
        }

        try {
            $data = \SymPress\Assets\IO\RequestFiles::shared()->remember('json:' . $resource, static fn (): mixed => json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
        } catch (\JsonException $exception) {
            throw new InvalidResourceException(
                sprintf('Error parsing JSON - %s', esc_html($exception->getMessage())),
                previous: $exception,
            );
        }

        if (!is_array($data)) {
            throw new InvalidResourceException('JSON resource must decode to an object or array.');
        }

        $normalized = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /** @throws FileNotFoundException */
    #[\NoDiscard]
    public function readableFile(mixed $resource): string
    {
        if (is_string($resource) && \SymPress\Assets\IO\RequestFiles::shared()->info($resource)['readable']) {
            return $resource;
        }

        throw new FileNotFoundException(
            sprintf(
                'The given file "%s" does not exist or is not readable.',
                esc_html($this->resourceLabel($resource)),
            ),
        );
    }

    private function resourceLabel(mixed $resource): string
    {
        return is_scalar($resource) || $resource instanceof \Stringable
            ? (string) $resource
            : get_debug_type($resource);
    }
}
