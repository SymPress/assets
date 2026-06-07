<?php

declare(strict_types=1);

namespace SymPress\Assets;

trait ConfigureAutodiscoverVersionTrait
{
    /**
     * Set to "false" and the version will not automatically discovered.
     *
     * @see self::disableAutodiscoverVersion()
     * @see self::enableAutodiscoverVersion()
     */
    protected bool $autodiscoverVersion = true;

    /**
     * Enable automatic discovering of the version if no version is set.
     */
    public function enableAutodiscoverVersion(): static
    {
        $this->autodiscoverVersion = true;

        return $this;
    }

    /**
     * Disable automatic discovering of the version if no version is set.
     */
    public function disableAutodiscoverVersion(): static
    {
        $this->autodiscoverVersion = false;

        return $this;
    }
}
