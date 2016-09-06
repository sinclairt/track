<?php

require_once 'DbTestCase.php';
require_once 'Models/Dummy.php';

use Sinclair\Track\Track;

/**
 * Class ApiTest
 */
class ScopeTest extends DbTestCase
{
    /**
     * @var
     */
    private $faker;

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();

        $this->migrate(__DIR__ . '/migrations');

        $this->migrate(__DIR__ . '/../vendor/laravel/laravel/database/migrations');

        $this->faker = Faker\Factory::create();

        \Artisan::call('vendor:publish', [ '--tag' => 'config' ]);
    }

    public function test_i_can_get_objects_created_by_a_user()
    {
        $user1 = $this->createUser();

        auth()->login($user1);

        $user1Dummies = $this->createDummies(5);

        auth()->logout();

        $user2 = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user2);

        $user2Dummies = $this->createDummies(5);

        // user 1

        $expected = [];

        foreach ( $user1Dummies as $dummy )
        {
            $values = $dummy->toArray();
            ksort($values);
            $expected[] = $values;
        }

        $actual = [];

        foreach ( Dummy::createdBy($user1->id)
                       ->get() as $item )
        {
            $values = $item->toArray();
            ksort($values);
            $actual[] = $values;
        }

        $this->assertSame($expected, $actual);

        // user 2

        $expected = [];

        foreach ( $user2Dummies as $dummy )
        {
            $values = $dummy->toArray();
            ksort($values);
            $expected[] = $values;
        }

        $actual = [];

        foreach ( Dummy::createdBy($user2->id)
                       ->get() as $item )
        {
            $values = $item->toArray();
            ksort($values);
            $actual[] = $values;
        }

        $this->assertSame($expected, $actual);
    }

    public function test_i_can_get_objects_updated_by_a_user()
    {
        $user1 = $this->createUser();

        auth()->login($user1);

        $user1Dummies = $this->createDummies(5);

        foreach ( $user1Dummies as $dummy )
            $dummy->update([ 'name' => $this->faker->word ]);

        auth()->logout();

        $user2 = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user2);

        $user2Dummies = $this->createDummies(5);

        foreach ( $user2Dummies as $dummy )
            $dummy->update([ 'name' => $this->faker->word ]);

        // user 1

        $expected = [];

        foreach ( $user1Dummies as $dummy )
        {
            $values = $dummy->toArray();
            ksort($values);
            $expected[] = $values;
        }

        $actual = [];

        foreach ( Dummy::updatedBy($user1->id)
                       ->get() as $item )
        {
            $values = $item->toArray();
            ksort($values);
            $actual[] = $values;
        }

        $this->assertSame($expected, $actual);

        // user 2

        $expected = [];

        foreach ( $user2Dummies as $dummy )
        {
            $values = $dummy->toArray();
            ksort($values);
            $expected[] = $values;
        }

        $actual = [];

        foreach ( Dummy::updatedBy($user2->id)
                       ->get() as $item )
        {
            $values = $item->toArray();
            ksort($values);
            $actual[] = $values;
        }

        $this->assertSame($expected, $actual);
    }

    public function test_i_can_get_objects_deleted_by_a_user()
    {
        $user1 = $this->createUser();

        auth()->login($user1);

        $user1Dummies = $this->createSoftDeleteDummies(5);

        foreach ( $user1Dummies as $dummy )
            $dummy->delete();

        auth()->logout();

        $user2 = $this->createUser();

        auth()->login($user2);

        $user2Dummies = $this->createSoftDeleteDummies(5);

        foreach ( $user2Dummies as $dummy )
            $dummy->delete();

        // user 1

        $expected = [];

        foreach ( $user1Dummies as $dummy )
        {
            $values = $dummy->toArray();
            ksort($values);
            $expected[] = $values;
        }

        $actual = [];

        foreach ( DummySoftDelete::deletedBy($user1->id)
                                 ->get() as $item )
        {
            $values = $item->toArray();
            ksort($values);
            $actual[] = $values;
        }

        $this->assertSame($expected, $actual);

        // user 2

        $expected = [];

        foreach ( $user2Dummies as $dummy )
        {
            $values = $dummy->toArray();
            ksort($values);
            $expected[] = $values;
        }

        $actual = [];

        foreach ( DummySoftDelete::deletedBy($user2->id)
                                 ->get() as $item )
        {
            $values = $item->toArray();
            ksort($values);
            $actual[] = $values;
        }

        $this->assertSame($expected, $actual);
    }

    public function test_i_can_get_objects_restored_by_a_user()
    {
        $user1 = $this->createUser();

        auth()->login($user1);

        $user1Dummies = $this->createSoftDeleteDummies(5);

        foreach ( $user1Dummies as $dummy )
        {
            $dummy->delete();
            $dummy->restore();
        }

        auth()->logout();

        $user2 = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user2);

        $user2Dummies = $this->createSoftDeleteDummies(5);

        foreach ( $user2Dummies as $dummy )
        {
            $dummy->delete();
            $dummy->restore();
        }

        // user 1

        $expected = [];

        foreach ( $user1Dummies as $dummy )
        {
            $values = $dummy->toArray();
            ksort($values);
            $expected[] = $values;
        }

        $actual = [];

        foreach ( DummySoftDelete::restoredBy($user1->id)
                                 ->get() as $item )
        {
            $values = $item->toArray();
            ksort($values);
            $actual[] = $values;
        }

        $this->assertSame($expected, $actual);

        // user 2

        $expected = [];

        foreach ( $user2Dummies as $dummy )
        {
            $values = $dummy->toArray();
            ksort($values);
            $expected[] = $values;
        }

        $actual = [];

        foreach ( DummySoftDelete::restoredBy($user2->id)
                                 ->get() as $item )
        {
            $values = $item->toArray();
            ksort($values);
            $actual[] = $values;
        }

        $this->assertSame($expected, $actual);
    }

    /**
     * @param $count
     *
     * @return array
     */
    private function createSoftDeleteDummies( $count )
    {
        $dummies = [];
        for ( $i = 0; $i < $count; $i++ )
            $dummies[] = DummySoftDelete::create([
                'name'   => $this->faker->word,
                'number' => $this->faker->unique()->ean13,
                'plan'   => $this->faker->word,
            ]);

        return $dummies;
    }

    /**
     * @param $count
     *
     * @return array
     */
    private function createDummies( $count )
    {
        $dummies = [];
        for ( $i = 0; $i < $count; $i++ )
            $dummies[] = Dummy::create([
                'name'   => $this->faker->word,
                'number' => $this->faker->unique()->ean13,
                'plan'   => $this->faker->word,
            ]);

        return $dummies;
    }

    /**
     * @return \App\User;
     */
    private function createUser()
    {
        return \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);
    }
}