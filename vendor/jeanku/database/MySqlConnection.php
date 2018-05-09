<?php


namespace Jeanku\Database;

use Jeanku\Database\Query\Grammars\MySqlGrammar as QueryGrammar;

class MySqlConnection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \Jeanku\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

}
