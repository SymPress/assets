<?php

declare(strict_types=1);

if (!class_exists('WP_Error')) {
    final class WP_Error
    {
        public function __construct(
            private readonly string $code = 'error',
            private readonly string $message = '',
        ) {
        }

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}
