<?php

namespace eLife\Search\Indexer\ModelIndexer\Helper;

use stdClass;

trait Blocks
{
    protected function flattenBlocks(array $blocks) : string
    {
        return implode(' ', array_filter(array_map([$this, 'flattenBlock'], $blocks)));
    }

    protected function flattenBlock(stdClass $block) : string
    {
        return implode(' ', array_filter([
            $block->id ?? null,
            $block->label ?? null,
            $block->title ?? null,
            $block->alt ?? null,
            is_array($block->text ?? null) ? $this->flattenBlocks($block->text) : ($block->text ?? null),
            $block->caption->text ?? null,
            $block->question ?? null,
            $this->flattenBlocks($block->answer ?? []),
            $this->flattenBlocks($block->content ?? []),
            $this->flattenItems($block->items ?? []),
        ]));
    }

    protected function flattenItems(array $items) : string
    {
        return implode(' ', array_map(function ($item) {
            if (is_string($item)) {
                return $item;
            }

            return $this->flattenBlocks($item);
        }, $items));
    }
}
