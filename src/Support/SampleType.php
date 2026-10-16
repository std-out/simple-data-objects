<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use DOMElement;

/** @internal */
final class SampleType
{
    /** @var array<string, true> */
    public array $scalars = [];

    public bool $nullable = false;

    public ?SampleObject $object = null;

    public ?SampleType $items = null;

    public bool $list = false;

    public function add(mixed $value): void
    {
        match (true) {
            $value === null => $this->nullable = true,
            $value instanceof \stdClass => ($this->object ??= new SampleObject)->add($value),
            is_array($value) => $this->addList($value),
            default => $this->scalars[get_debug_type($value)] = true,
        };
    }

    public function addElement(DOMElement $element): void
    {
        if (! $element->hasAttributes() && $element->firstElementChild === null) {
            $this->addText($element->textContent);

            return;
        }

        ($this->object ??= new SampleObject)->addElement($element);
    }

    public function addText(string $text): void
    {
        $text = trim($text);

        match (true) {
            $text === '' => $this->nullable = true,
            preg_match('/^-?(0|[1-9]\d{0,17})$/', $text) === 1 => $this->scalars['int'] = true,
            preg_match('/^-?\d+\.\d+$/', $text) === 1 => $this->scalars['float'] = true,
            $text === 'true' || $text === 'false' => $this->scalars['bool'] = true,
            default => $this->scalars['string'] = true,
        };
    }

    /** @param array<mixed> $values */
    private function addList(array $values): void
    {
        $this->list = true;
        $this->items ??= new self;

        foreach ($values as $value) {
            $this->items->add($value);
        }
    }
}
