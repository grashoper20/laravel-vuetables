<?php

namespace Grashoper20\VueTables\Tests;

use Carbon\Carbon;
use Grashoper20\VueTables\VueTablesTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Grashoper20\VueTables\VueTablesTrait
 */
class VueTablesTraitTest extends TestCase
{

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();
        $this->builder = $this->prophesize(TestBuilder::class);
        $this->builder->paginate(Argument::any())
            ->willReturn($this->prophesize(LengthAwarePaginator::class)->reveal());
        Concrete::setBuilder($this->builder->reveal());
    }


    public function testFilter()
    {
        $this->markTestIncomplete('Test with closure....');
    }

    /**
     * @covers ::vueTables
     * @covers ::buildVueTablesResult
     * @covers ::filterByColumn
     */
    public function testFilterColumns()
    {
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

        Concrete::vueTables(new Request([
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
        $this->builder->paginate(10)->shouldBeCalledTimes(1)
          ->willReturn($this->prophesize(LengthAwarePaginator::class)->reveal());
        $this->builder->paginate(5)->shouldBeCalledTimes(1)
          ->willReturn($this->prophesize(LengthAwarePaginator::class)->reveal());

        Concrete::vueTables(new Request());
        Concrete::vueTables(new Request(['limit' => 5]));
    }

    /**
     * @covers ::vueTables
     * @covers ::buildVueTablesResult
     */
    public function testOrderBy()
    {
        $this->builder->orderBy('test1', 'asc')->shouldBeCalledTimes(1);
        $this->builder->orderBy('test2', 'asc')->shouldBeCalledTimes(1);
        $this->builder->orderBy('test3', 'desc')->shouldBeCalledTimes(1);

        Concrete::vueTables(new Request());
        Concrete::vueTables(new Request(['orderBy' => 'test1']));
        Concrete::vueTables(new Request([
          'orderBy' => 'test2',
          'ascending' => '1',
        ]));
        Concrete::vueTables(new Request([
          'orderBy' => 'test3',
          'ascending' => '0',
        ]));
    }

}

/**
 * Tell prophecy about some magic methods.
 *
 * @method whereBetween($column, array $values, $boolean = 'and', $not = false)
 * @method orderBy($column, $direction = 'asc')
 */
class TestBuilder extends Builder {}

class Concrete
{
    use VueTablesTrait;

    private static $builder;

    public static function setBuilder($builder)
    {
        static::$builder = $builder;
    }

    public static function query()
    {
        return static::$builder;
    }
}