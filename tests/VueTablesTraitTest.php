<?php

namespace Grashoper20\VueTables\Tests;

use Carbon\Carbon;
use Grashoper20\VueTables\VueTablesTrait;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Grashoper20\VueTables\VueTablesTrait
 */
class VueTablesTraitTest extends TestCase
{

    private $builder;

    public function setUp()
    {
        parent::setUp();
        $this->builder = $this->prophesize(Builder::class);
    }


    public function testFilter()
    {
        $test = new Concrete($this->builder->reveal());
        $this->markTestIncomplete('Test with closure....');
    }

    /**
     * @covers ::vueTables
     * @covers ::buildVueTablesResult
     * @covers ::filterByColumn
     */
    public function testFilterColumns()
    {
        $test = new Concrete($this->builder->reveal());

        $this->builder->where('testcol1', 'LIKE', '%foo%')
          ->shouldBeCalledTimes(1);
        $this->builder->where('testcol2', 'LIKE', '%bar%')
          ->shouldBeCalledTimes(1);
        $this->builder->whereBetween('testcol3', [
          Carbon::createFromFormat('Y-m-d', '2010-10-10')
            ->startOfDay(),
          Carbon::createFromFormat('Y-m-d', '2010-10-11')
            ->endOfDay(),
        ])->shouldBeCalledTimes(1);
        $this->builder->paginate(Argument::any())->shouldBeCalled();

        $test->vueTables(new Request([
          'byColumn' => 1,
          'query' => [
            'testcol1' => 'foo',
            'testcol2' => 'bar',
            'testcol3' => [
              'start' => '2010-10-10',
              'end' => '2010-10-11',
            ],
          ],
        ]));
    }

    /**
     * @covers ::vueTables
     * @covers ::buildVueTablesResult
     */
    public function testLimit()
    {
        $test = new Concrete($this->builder->reveal());

        $this->builder->paginate(10)->shouldBeCalledTimes(1);
        $this->builder->paginate(5)->shouldBeCalledTimes(1);

        $test->vueTables(new Request());
        $test->vueTables(new Request(['limit' => 5]));
    }

    /**
     * @covers ::vueTables
     * @covers ::buildVueTablesResult
     */
    public function testOrderBy()
    {
        $test = new Concrete($this->builder->reveal());

        $this->builder->orderBy('test1', 'asc')->shouldBeCalledTimes(1);
        $this->builder->orderBy('test2', 'asc')->shouldBeCalledTimes(1);
        $this->builder->orderBy('test3', 'desc')->shouldBeCalledTimes(1);
        $this->builder->paginate(Argument::any())->shouldBeCalled();

        $test->vueTables(new Request());
        $test->vueTables(new Request(['orderBy' => 'test1']));
        $test->vueTables(new Request([
          'orderBy' => 'test2',
          'ascending' => '1',
        ]));
        $test->vueTables(new Request([
          'orderBy' => 'test3',
          'ascending' => '0',
        ]));
    }

}

class Concrete
{
    use VueTablesTrait;

    private $builder;

    public function __construct($builder)
    {
        $this->builder = $builder;
    }

    public function query()
    {
        return $this->builder;
    }
}