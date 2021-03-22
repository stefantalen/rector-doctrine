<?php

declare(strict_types=1);

namespace Rector\Doctrine\PhpDoc\NodeFactory\Property_;

use Rector\BetterPhpDocParser\Printer\ArrayPartPhpDocTagPrinter;
use Rector\BetterPhpDocParser\Printer\TagValueNodePrinter;
use Rector\Doctrine\PhpDoc\Node\Property_\ColumnTagValueNode;

final class ColumnTagValueNodeFactory
{
    /**
     * @var ArrayPartPhpDocTagPrinter
     */
    private $arrayPartPhpDocTagPrinter;

    /**
     * @var TagValueNodePrinter
     */
    private $tagValueNodePrinter;

    public function __construct(
        ArrayPartPhpDocTagPrinter $arrayPartPhpDocTagPrinter,
        TagValueNodePrinter $tagValueNodePrinter
    ) {
        $this->arrayPartPhpDocTagPrinter = $arrayPartPhpDocTagPrinter;
        $this->tagValueNodePrinter = $tagValueNodePrinter;
    }

    public function create(): ColumnTagValueNode
    {
        return $this->createFromItems([]);
    }

    /**
     * @param array<string, mixed> $items
     */
    public function createFromItems(array $items): ColumnTagValueNode
    {
        return new ColumnTagValueNode($this->arrayPartPhpDocTagPrinter, $this->tagValueNodePrinter, $items);
    }
}
