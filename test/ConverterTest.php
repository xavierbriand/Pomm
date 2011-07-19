<?php

namespace Pomm\Test;

include __DIR__.'/../Pomm/External/lime.php';
include "autoload.php";
include "bootstrap.php";

use Pomm\Service;
use Pomm\Connection\Database;
use Pomm\Exception\Exception;
use Pomm\Type;

class converter_test extends \lime_test
{
    public 
        $object;

    protected 
        $service,
        $connection,
        $map;

    public function initialize()
    {
        $this->service = new Service(array('default' => array('dsn' => 'pgsql://greg/greg')));
        $this->connection = $this->service->createConnection();
        $this->map = $this->connection->getMapFor('Pomm\Test\TestConverter');
        $this->map->createTable();

        return $this;
    }

    public function __destruct()
    {
        $this->map->dropTable();
        parent::__destruct();
    }

    public function updateObject(Array $fields)
    {
        $this->map->updateOne($this->object, $fields);
    }

    public function createObject($values)
    {
        $this->object = $this->map->createObject();
        $this->object->hydrate($values);
    }

    public function testObject(Array $values)
    {
        foreach($values as $key => $value)
        {
            if (is_object($value))
            {
                $this->isa_ok($this->object[$key], get_class($value), sprintf("'%s' is an instance of '%s'.", $key, get_class($value)));
            }
            else
            {
                $this->is($this->object[$key], $values[$key], sprintf("Checking '%s'.", $key));
            }
        }
    }

    public function testBasics($values, $compare)
    {
        $this->info('basic types.');
        $this->object = $this->map->createObject();
        $this->object->hydrate($values);
        $this->map->saveOne($this->object);
        $this->ok(is_integer($this->object['id']), "'id' is an integer.");
        $this->is($this->object['id'], $compare['id'], sprintf("'id' is '%d'.", $compare['id']));
        $this->isa_ok($this->object['created_at'], 'DateTime', "'created_at' is a 'DateTime' instance.");
        $this->is($this->object['created_at']->format('d/m/Y H:i'), $compare['created_at']->format('d/m/Y H:i'), sprintf("'created_at' is '%s'.", $compare['created_at']->format('d/m/Y H:i')));
        $this->is($this->object['something'], 'plop', "'something' is 'plop'.");
        $this->ok(is_bool($this->object['is_true']), "'is_true' is boolean.");
        $this->is($this->object['is_true'], $compare['is_true'], sprintf("'is_true' is '%s'.", $compare['is_true'] ? 'true' : 'false'));
        $this->is($this->object['precision'], $compare['precision'], sprintf("'precision' match float '%f'.", $compare['precision']));
        $this->is($this->object['probed_data'], $compare['probed_data'], sprintf("'probed_data' match '%4.3f'.", $compare['probed_data']));

        return $this;
    }

    public function testPoint(\Pomm\Type\Point $point)
    {
        $this->info('\\Pomm\\Converter\\PgPoint');
        if (!$this->map->hasField('test_point'))
        {
            $this->info('Creating column test_point.');
            $this->map->addPoint();
        }

        $this->object->setTestPoint($point);
        $this->map->saveOne($this->object);

        $object = $this->map->findByPk($this->object->getPrimaryKey());

        $this->ok(is_object($object['test_point']), "'point' is an object.");
        $this->ok($object['test_point'] instanceof \Pomm\Type\Point, "'point' is a \\Pomm\\Type\\Point instance.");
        $this->is($object['test_point']->x, $point->x, sprintf("Coord 'x' are equal (%f).", $point->x));
        $this->is($object['test_point']->y, $point->y, sprintf("Coord 'y' are equal (%f).", $point->y));

        return $this;
    }

    public function testLseg(\Pomm\Type\Segment $segment)
    {
        $this->info('\\Pomm\\Converter\\PgLseg');
        if (!$this->map->hasField('test_lseg'))
        {
            $this->info('Creating column test_lseg.');
            $this->map->addLseg();
        }

        $this->object->setTestLseg($segment);
        $this->map->saveOne($this->object);

        $object = $this->map->findByPk($this->object->getPrimaryKey());

        $this->ok(is_object($object['test_lseg']), "'test_lseg' is an object.");
        $this->ok($object['test_lseg'] instanceof \Pomm\Type\Segment, "'test_lseg' is a \\Pomm\\Type\\Segment instance.");
        $this->is($object['test_lseg']->point_a->x, $segment->point_a->x, sprintf("Coord 'x' are equal (%f).", $segment->point_a->x));
        $this->is($object['test_lseg']->point_a->y, $segment->point_a->y, sprintf("Coord 'y' are equal (%f).", $segment->point_a->y));
        $this->is($object['test_lseg']->point_b->x, $segment->point_b->x, sprintf("Coord 'x' are equal (%f).", $segment->point_b->x));
        $this->is($object['test_lseg']->point_b->y, $segment->point_b->y, sprintf("Coord 'y' are equal (%f).", $segment->point_b->y));

        return $this;
    }
}

$test = new converter_test();

$test
    ->initialize()
    ->testBasics(array('something' => 'plop', 'is_true' => false, 'precision' => 0.123456789, 'probed_data' => 4.3210), array('id' => 1, 'created_at' => new \DateTime(), 'something' => 'plop', 'is_true' => false, 'precision' => 0.123456789, 'probed_data' => 4.321))
    ->testPoint(new Type\Point(0,0))
    ->testPoint(new Type\Point(47.123456,-0.654321))
    ->testLseg(new Type\Segment(new Type\Point(1,1), new Type\Point(2,2)))
    ;
