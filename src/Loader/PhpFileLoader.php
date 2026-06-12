<?php

declare(strict_types=1);

namespace SymPress\Assets\Loader;

use SymPress\Assets\Asset;
use SymPress\Assets\Exception\FileNotFoundException;

class PhpFileLoader extends ArrayLoader
{
    /**
     * @param mixed $resource the path to your php-file
     * @return array<Asset>
     * @psalm-suppress UnresolvableInclude
     */
    #[\NoDiscard]
    #[\Override]
    public function load(mixed $resource): array
    {
        if (!is_string($resource) || !is_readable($resource)) {
            throw new FileNotFoundException(
                sprintf(
                    'The given file "%s" does not exist or is not readable.',
                    esc_html($this->resourceLabel($resource)),
                ),
            );
        }

        $data = require $resource;

        return parent::load($data);
    }

    private function resourceLabel(mixed $resource): string
    {
        return is_scalar($resource) || $resource instanceof \Stringable
            ? (string) $resource
            : get_debug_type($resource);
    }
}
