<?php 
namespace App\Test\Fixture;

use Origin\TestSuite\Fixture;

class UserFixture extends Fixture
{
    public $import = ['model'=>'User'];
    public $records = [
        ['id' => 1000,
        'name' => 'Frank' ,
        'email'=>'frank@example.com',
        'password'=>'secret',
        'dob'=>'1999-08-01',
        'created'=>'2019-01-18 09:53:00',
        'modified'=>'2019-01-18 09:53:00']
    ];
}
