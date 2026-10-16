<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use DOMElement;
use DOMText;

/** @internal */
final class SampleObject
{
    public int $seen = 0;

    /** @var array<string, SampleType> */
    public array $fields = [];

    /** @var array<string, int> */
    public array $presence = [];

    public function add(\stdClass $value): void
    {
        $this->seen++;

        foreach (get_object_vars($value) as $key => $field) {
            $this->field((string) $key)->add($field);
        }
    }

    public function addElement(DOMElement $element): void
    {
        $this->seen++;

        foreach ($element->attributes as $attribute) {
            $this->field('@'.$attribute->nodeName)->addText($attribute->value);
        }

        $children = [];

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[$child->nodeName][] = $child;
            }
        }

        foreach ($children as $name => $elements) {
            $field = $this->field($name);
            $field->list = $field->list || count($elements) > 1;
            $field->items ??= new SampleType;

            foreach ($elements as $child) {
                $field->items->addElement($child);
            }
        }

        $text = self::ownText($element);

        if ($text !== '' || isset($this->fields['#text'])) {
            $this->field('#text')->addText($text);
        }
    }

    private static function ownText(DOMElement $element): string
    {
        $text = '';

        foreach ($element->childNodes as $node) {
            if ($node instanceof DOMText) {
                $text .= $node->data;
            }
        }

        return trim($text);
    }

    private function field(string $key): SampleType
    {
        $this->presence[$key] = ($this->presence[$key] ?? 0) + 1;

        return $this->fields[$key] ??= new SampleType;
    }
}
