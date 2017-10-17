<?php

namespace eLife\Search\Workflow;

use stdClass;

trait Blocks
{
    final private function flattenBlocks(array $blocks) : string
    {
        return implode(' ', array_filter(array_map([$this, 'flattenBlock'], $blocks)));
    }

    final private function flattenBlock(stdClass $block) : string
    {
        return implode(' ', array_filter([
            $block->id ?? null,
            $block->label ?? null,
            $block->title ?? null,
            $block->alt ?? null,
            $block->text ?? null,
            $block->caption->text ?? null,
            $block->question ?? null,
            $this->flattenBlocks($block->answer ?? []),
            $this->flattenBlocks($block->content ?? []),
            $this->flattenItems($block->items ?? []),
        ]));
    }

    final private function flattenItems(array $items) : string
    {
        return implode(' ', array_map(function ($item) {
            if (is_string($item)) {
                return $item;
            }

            return $this->flattenBlocks($item);
        }, $items));
    }
}
