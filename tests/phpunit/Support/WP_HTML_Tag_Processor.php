<?php

declare(strict_types=1);

if (!class_exists('WP_HTML_Tag_Processor')) {
    final class WP_HTML_Tag_Processor
    {
        private ?string $tagName = null;

        private string $tag = '';

        private string $updatedTag = '';

        public function __construct(
            private readonly string $html,
        ) {
        }

        /**
         * @param array{tag_name?: string} $query
         */
        public function next_tag(array $query = []): bool
        {
            $tagName = strtolower((string) ($query['tag_name'] ?? ''));

            if ('' === $tagName) {
                return false;
            }

            if (1 !== preg_match('/<' . preg_quote($tagName, '/') . '\b[^>]*>/i', $this->html, $matches)) {
                return false;
            }

            $this->tagName = $tagName;
            $this->tag = $matches[0];
            $this->updatedTag = $this->tag;

            return true;
        }

        public function get_attribute(string $name): ?string
        {
            if ('' === $this->tag) {
                return null;
            }

            if (1 === preg_match('/\s' . preg_quote($name, '/') . '=(["\'])(.*?)\1/i', $this->tag, $matches)) {
                return html_entity_decode($matches[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            if (1 === preg_match('/\s' . preg_quote($name, '/') . '(?:\s|>|\/>)/i', $this->tag)) {
                return '';
            }

            return null;
        }

        public function set_attribute(string $name, string $value): void
        {
            if ('' === $this->updatedTag || null === $this->tagName) {
                return;
            }

            $attribute = sprintf(' %s="%s"', $name, $value);
            $this->updatedTag = preg_replace('/\s*\/?>$/', $attribute . '$0', $this->updatedTag) ?? $this->updatedTag;
        }

        public function get_updated_html(): string
        {
            if ('' === $this->tag) {
                return $this->html;
            }

            return preg_replace('/' . preg_quote($this->tag, '/') . '/', $this->updatedTag, $this->html, 1)
                ?? $this->html;
        }
    }
}
