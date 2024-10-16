<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Implementation of Base function for Doctrine ORM.
 */
abstract class BaseFunction extends FunctionNode
{
    protected string $functionFormat;
    protected array $argMapping = [];
    protected array $argValues = [];

    abstract protected function initFunctionMapping(): void;

    protected function setFunctionFormat(string $functionFormat): void
    {
        $this->functionFormat = $functionFormat;
    }

    protected function addArgMapping(string $parserMethod): void
    {
        $this->argMapping[] = $parserMethod;
    }

    #[\Override]
    public function parse(Parser $parser): void
    {
        $this->initFunctionMapping();

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->feedParserWithNodes($parser);
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * Feeds given parser with previously set nodes.
     */
    protected function feedParserWithNodes(Parser $parser): void
    {
        $nodesMappingCount = \count($this->argMapping);
        $lastNode = $nodesMappingCount - 1;
        for ($i = 0; $i < $nodesMappingCount; $i++) {
            $parserMethod = $this->argMapping[$i];
            $this->argValues[$i] = $parser->{$parserMethod}();
            if ($i < $lastNode) {
                $parser->match(Lexer::T_COMMA);
            }
        }
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $dispatched = [];
        foreach ($this->argValues as $value) {
            $dispatched[] = $value instanceof Node ? $value->dispatch($sqlWalker) : 'null';
        }

        return \sprintf($this->functionFormat, ...$dispatched);
    }
}
