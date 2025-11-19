<?php

namespace Makis83\LaravelBundle\Tests;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Makis83\LaravelBundle\Traits\models\UsesDb;
use Makis83\LaravelBundle\Exceptions\InvalidUsageException;

// Test class that extends Model (valid usage)
class ValidModel extends Model
{
    use UsesDb;

    protected $table = 'test_table';
}

// Test class that does not extend Model (invalid usage)
class InvalidClass
{
    use UsesDb;
}

class UsesDbTest extends TestCase
{
    public function testValidUsageWithModel(): void
    {
        // This should work without throwing an exception
        $result = ValidModel::getTableName();
        $this->assertIsString($result);
    }

    public function testInvalidUsageWithoutModel(): void
    {
        // This should throw an InvalidUsageException
        $this->expectException(InvalidUsageException::class);
        $this->expectExceptionMessage('The trait Makis83\LaravelBundle\Traits\UsesDb can only be used in classes that extend/implement Illuminate\Database\Eloquent\Model. Makis83\LaravelBundle\Tests\InvalidClass does not extend/implement Illuminate\Database\Eloquent\Model.');

        InvalidClass::getTableName();
    }
}
