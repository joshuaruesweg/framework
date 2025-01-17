<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDecimalCastingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use TypeError;

class EloquentModelDecimalCastingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('decimal_field_2', 8, 2)->nullable();
            $table->decimal('decimal_field_4', 8, 4)->nullable();
        });
    }

    public function testItThrowsOnNonNumericValues()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:20',
            ];
        };
        $model->amount = 'foo';

        $this->expectException(TypeError::class);

        $model->amount;
    }

    public function testItHandlesLargeNumbers()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:20',
            ];
        };

        $model->amount = '0.89898989898989898989';
        $this->assertSame('0.89898989898989898989', $model->amount);

        $model->amount = '89898989898989898989';
        $this->assertSame('89898989898989898989.00000000000000000000', $model->amount);
    }

    public function testItTrimsLongValues()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:20',
            ];
        };

        $model->amount = '0.89898989898989898989898989898989898989898989';
        $this->assertSame('0.89898989898989898989', $model->amount);
    }

    public function testItDoesntRoundNumbers()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:1',
            ];
        };

        $model->amount = '0.99';
        $this->assertSame('0.9', $model->amount);
    }

    public function testDecimalsAreCastable()
    {
        $user = TestModel1::create([
            'decimal_field_2' => '12',
            'decimal_field_4' => '1234',
        ]);

        $this->assertSame('12.00', $user->toArray()['decimal_field_2']);
        $this->assertSame('1234.0000', $user->toArray()['decimal_field_4']);

        $user->decimal_field_2 = 12;
        $user->decimal_field_4 = '1234';

        $this->assertSame('12.00', $user->toArray()['decimal_field_2']);
        $this->assertSame('1234.0000', $user->toArray()['decimal_field_4']);

        $this->assertFalse($user->isDirty());

        $user->decimal_field_4 = '1234.1234';
        $this->assertTrue($user->isDirty());
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'decimal_field_2' => 'decimal:2',
        'decimal_field_4' => 'decimal:4',
    ];
}
