<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm\Converter\PgBytea - Bytea converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgBytea implements ConverterInterface
{
    /**
     * escByteA
     *
     * Does the job of pg_escape_bytea in PHP
     * see http://php.net/manual/fr/function.pg-escape-bytea.php
     *
     * @param String the binary sting to be escaped
     * @return String binary string
     **/
    protected function escByteA($string)
    {
        $search = array(chr(92), chr(0), chr(39)); 
        $replace = array('\\\134', '\\\000', '\\\047'); 
        $binData = str_replace($search, $replace, $binData); 

        return $binData;
    }

    /**
     * @see ConverterInterface
     **/
    public function toPg($data)
    {
        if (function_exists('pg_escape_bytea'))
        {
            return sprintf("E'%s'::bytea", @pg_escape_bytea($data));
        }
        else
        {
            return $this->escByteA($data);
        }
    }

    /**
     * @see ConverterInterface
     **/
    public function fromPg($data)
    {
        return stripcslashes(stream_get_contents($data));
    }
}

