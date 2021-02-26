<?php

class Doctrine_Event
{
    /**
     * CONNECTION EVENT CODES
     */
    const CONN_QUERY   = 1;
    const CONN_EXEC    = 2;
    const CONN_PREPARE = 3;
    const CONN_CONNECT = 4;
    const CONN_CLOSE   = 5;
    const CONN_ERROR   = 6;

    const STMT_EXECUTE  = 10;
    const STMT_FETCH    = 11;
    const STMT_FETCHALL = 12;

    const TX_BEGIN           = 31;
    const TX_COMMIT          = 32;
    const TX_ROLLBACK        = 33;
    const SAVEPOINT_CREATE   = 34;
    const SAVEPOINT_ROLLBACK = 35;
    const SAVEPOINT_COMMIT   = 36;

    const HYDRATE = 40;

    /*
     * RECORD EVENT CODES
     */
    const RECORD_DELETE      = 21;
    const RECORD_SAVE        = 22;
    const RECORD_UPDATE      = 23;
    const RECORD_INSERT      = 24;
    const RECORD_SERIALIZE   = 25;
    const RECORD_UNSERIALIZE = 26;
    const RECORD_DQL_DELETE  = 27;
    const RECORD_DQL_SELECT  = 28;
    const RECORD_DQL_UPDATE  = 29;
    const RECORD_VALIDATE    = 30;

    /**
     * @var mixed $nextSequence        the sequence of the next event that will be created
     */
    protected static $nextSequence = 0;

    /**
     * @var mixed $sequence            the sequence of this event
     */
    protected $sequence;

    /**
     * @var mixed $invoker             the handler which invoked this event
     */
    protected $invoker;

    /**
     * @var string|Doctrine_Query_Abstract|null $query              the sql query associated with this event (if any)
     */
    protected $query;

    /**
     * @var array $params             the parameters associated with the query (if any)
     */
    protected $params;

    /**
     * @see Doctrine_Event constants
     * @var integer $code              the event code
     */
    protected $code;

    /**
     * @var float $startedMicrotime  the time point in which this event was started
     */
    protected $startedMicrotime;

    /**
     * @var float|null $endedMicrotime    the time point in which this event was ended
     */
    protected $endedMicrotime;

    /**
     * @var array $options             an array of options
     */
    protected $options = [];

    /**
     * constructor
     *
     * @param Doctrine_Connection|Doctrine_Connection_Statement|Doctrine_Connection_UnitOfWork|Doctrine_Transaction|Doctrine_Record|null $invoker the handler which invoked this event
     * @param integer                                                                                                                    $code    the event code
     * @param string|Doctrine_Query_Abstract                                                                                             $query   the sql query associated with this event (if any)
     * @param array                                                                                                                      $params
     */
    public function __construct($invoker, $code, $query = null, $params = [])
    {
        $this->sequence = self::$nextSequence++;
        $this->invoker  = $invoker;
        $this->code     = $code;
        $this->query    = $query;
        $this->params   = $params;
    }

    /**
     * getQuery
     *
     * @return string|Doctrine_Query_Abstract|null       returns the query associated with this event (if any)
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * getName
     * returns the name of this event
     *
     * @return string|null       the name of this event
     */
    public function getName()
    {
        switch ($this->code) {
            case self::CONN_QUERY:
                return 'query';
            case self::CONN_EXEC:
                return 'exec';
            case self::CONN_PREPARE:
                return 'prepare';
            case self::CONN_CONNECT:
                return 'connect';
            case self::CONN_CLOSE:
                return 'close';
            case self::CONN_ERROR:
                return 'error';

            case self::STMT_EXECUTE:
                return 'execute';
            case self::STMT_FETCH:
                return 'fetch';
            case self::STMT_FETCHALL:
                return 'fetch all';

            case self::TX_BEGIN:
                return 'begin';
            case self::TX_COMMIT:
                return 'commit';
            case self::TX_ROLLBACK:
                return 'rollback';

            case self::SAVEPOINT_CREATE:
                return 'create savepoint';
            case self::SAVEPOINT_ROLLBACK:
                return 'rollback savepoint';
            case self::SAVEPOINT_COMMIT:
                return 'commit savepoint';

            case self::RECORD_DELETE:
                return 'delete record';
            case self::RECORD_SAVE:
                return 'save record';
            case self::RECORD_UPDATE:
                return 'update record';
            case self::RECORD_INSERT:
                return 'insert record';
            case self::RECORD_SERIALIZE:
                return 'serialize record';
            case self::RECORD_UNSERIALIZE:
                return 'unserialize record';
            case self::RECORD_DQL_SELECT:
                return 'select records';
            case self::RECORD_DQL_DELETE:
                return 'delete records';
            case self::RECORD_DQL_UPDATE:
                return 'update records';
            case self::RECORD_VALIDATE:
                return 'validate record';
        }

        return null;
    }

    /**
     * getCode
     *
     * @return integer      returns the code associated with this event
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * getOption
     * returns the value of an option
     *
     * @param  string $option the name of the option
     * @return mixed
     */
    public function __get($option)
    {
        if (!isset($this->options[$option])) {
            return null;
        }

        return $this->options[$option];
    }

    /**
     * setOption
     * sets the value of an option
     *
     * @param  string $option the name of the option
     * @param  mixed  $value  the value of the given option
     * @return $this   this object
     */
    public function __set($option, $value)
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * setOption
     * sets the value of an option by reference
     *
     * @param  string $option the name of the option
     * @param  mixed  $value  the value of the given option
     * @return $this   this object
     */
    public function set($option, &$value)
    {
        $this->options[$option] = & $value;

        return $this;
    }

    /**
     * start
     * starts the internal timer of this event
     *
     * @return void
     */
    public function start()
    {
        $this->startedMicrotime = microtime(true);
    }

    /**
     * hasEnded
     * whether or not this event has ended
     *
     * @return boolean
     */
    public function hasEnded()
    {
        return ($this->endedMicrotime != null);
    }

    /**
     * end
     * ends the internal timer of this event
     *
     * @return $this   this object
     */
    public function end()
    {
        $this->endedMicrotime = microtime(true);

        return $this;
    }

    /**
     * getSequence
     * returns the sequence of this event
     *
     * @return integer
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * getInvoker
     * returns the handler that invoked this event
     *
     * @return mixed   the handler that invoked this event
     */
    public function getInvoker()
    {
        return $this->invoker;
    }

    /**
     * setInvoker
     * Defines new invoker (used in Hydrator)
     *
     * @param  mixed $invoker
     * @return void
     */
    public function setInvoker($invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * getParams
     * returns the parameters of the query
     *
     * @return array   parameters of the query
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get the elapsed time (in microseconds) that the event ran.  If the event has
     * not yet ended, return null.
     */
    public function getElapsedSecs(): ?float
    {
        if ($this->endedMicrotime === null) {
            return null;
        }
        return ($this->endedMicrotime - $this->startedMicrotime);
    }
}
