<?php

class Doctrine_Expression
{
    /**
     * @var string
     */
    protected $expression;

    protected Doctrine_Connection $connection;

    /**
     * @var Doctrine_Query_Tokenizer
     */
    protected $tokenizer;

    /**
     * Creates an expression.
     *
     * The constructor needs the dql fragment that contains one or more dbms
     * functions.
     * <code>
     * $e = new Doctrine_Expression("CONCAT('some', 'one')");
     * </code>
     *
     * @param string              $expr sql fragment
     * @param Doctrine_Connection $conn the connection (optional)
     */
    public function __construct($expr, $conn = null)
    {
        if ($conn !== null) {
            $this->connection = $conn;
        }
        $this->tokenizer = new Doctrine_Query_Tokenizer();
        $this->setExpression($expr);
    }

    /**
     * Retrieves the connection associated to this expression at creation,
     * or the current connection used if it was not specified.
     *
     * @return Doctrine_Connection The connection
     */
    public function getConnection()
    {
        if (!isset($this->connection)) {
            return Doctrine_Manager::connection();
        }

        return $this->connection;
    }

    /**
     * Sets the contained expression assuring that it is parsed.
     * <code>
     * $e->setExpression("CONCAT('some', 'one')");
     * </code>
     *
     * @param  string $clause The expression to set
     * @return void
     */
    public function setExpression($clause)
    {
        $this->expression = $this->parseClause($clause);
    }

    /**
     * Parses a single expressions and substitutes dql abstract functions
     * with their concrete sql counterparts for the given connection.
     *
     * @param  string $expr The expression to parse
     * @return string
     */
    public function parseExpression($expr)
    {
        $pos    = strpos($expr, '(');
        $quoted = (substr($expr, 0, 1) === "'" && substr($expr, -1) === "'");
        if ($pos === false || $quoted) {
            return $expr;
        }

        // get the name of the function
        $name   = substr($expr, 0, $pos);
        $argStr = substr($expr, ($pos + 1), -1);
        $args   = [];

        // parse args
        foreach ($this->tokenizer->bracketExplode($argStr, ',') as $arg) {
            $args[] = $this->parseClause($arg);
        }

        return $this->getConnection()->expression->$name(...$args);
    }

    /**
     * Parses a set of expressions at once.
     *
     * @see parseExpression()
     *
     * @param  string $clause The clause. Can be complex and parenthesised.
     * @return string           The parsed clause.
     */
    public function parseClause($clause)
    {
        $e = $this->tokenizer->bracketExplode($clause, ' ');

        foreach ($e as $k => $expr) {
            $e[$k] = $this->parseExpression($expr);
        }

        return implode(' ', $e);
    }

    /**
     * Gets the sql fragment represented.
     *
     * @return string
     */
    public function getSql()
    {
        return $this->expression;
    }

    /**
     * Magic method.
     *
     * Returns a string representation of this object. Proxies to @see getSql().
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getSql();
    }
}
