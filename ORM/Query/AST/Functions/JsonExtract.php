<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Implementation of `->>` for Doctrine ORM.
 */
class JsonExtract extends FunctionNode
{
    /**
     * @var Subselect|Node|string
     */
    private $entityFieldPath;

    /**
     * @var Subselect|Node|string
     */
    private $jsonFieldPath;

    /**
     * Parse JSON_EXTRACT(e.json_field, 'some_field')
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->entityFieldPath = $parser->StringExpression();
        $parser->match(Lexer::T_COMMA);
        $this->jsonFieldPath = $parser->StringExpression();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $entityFieldPath = $sqlWalker->walkStringPrimary($this->entityFieldPath);
        $jsonFieldPath = trim($sqlWalker->walkStringPrimary($this->jsonFieldPath), "'");

        return $entityFieldPath . '->>' . "'$jsonFieldPath'";
    }
}
