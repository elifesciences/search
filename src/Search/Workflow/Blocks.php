<?php

namespace eLife\Search\Workflow;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use stdClass;

trait Blocks
{
    final private function flattenBlocks(array $blocks) : array
    {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator(
            array_map(function (stdClass $block) {
                return [
                    $block->title ?? null,
                    array_map(function ($content) {
                        return array_filter([
                            $content->id ?? null,
                            $content->label ?? null,
                            $content->alt ?? null,
                            $content->text ?? null,
                            $content->caption->text ?? null,
                        ]);
                    }, $block->content ?? []),
                ];
            }, $blocks)
        )), false);
    }
}
