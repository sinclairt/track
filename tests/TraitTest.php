<?php

require_once 'DbTestCase.php';
require_once 'Models/Dummy.php';
require_once 'Models/DummySoftDelete.php';
require_once 'Events/MyCustomEvent.php';

/**
 * Class TraitTest
 */
class TraitTest extends DbTestCase
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

    /**
     *
     */
    public function test_a_change_is_logged_when_a_model_is_created_without_a_user_logged_in()
    {
        $count = \Sinclair\Track\Track::all()
                                      ->count();

        $dummy = Dummy::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        $this->assertEquals(1, $count + 1);

        $change = $dummy->trackedChanges->first();

        $this->assertEquals(Dummy::class, $change->tracked_type);
        $this->assertEquals($dummy->id, $change->tracked_id);
        $this->assertEquals(null, $change->user_id);
        $this->assertEquals('Created', $change->event);
        $this->assertEquals(null, $change->field);
        $this->assertEquals(null, $change->old_value);
        $this->assertEquals(null, $change->new_value);
    }

    /**
     *
     */
    public function test_a_change_is_logged_when_a_model_is_created_with_a_user_logged_in()
    {
        $count = \Sinclair\Track\Track::all()
                                      ->count();

        $user = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user);

        $dummy = Dummy::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        $this->assertEquals(1, $count + 1);

        $change = $dummy->trackedChanges->first();

        $this->assertEquals(Dummy::class, $change->tracked_type);
        $this->assertEquals($dummy->id, $change->tracked_id);
        $this->assertEquals($user->id, $change->user_id);
        $this->assertEquals('Created', $change->event);
        $this->assertEquals(null, $change->field);
        $this->assertEquals(null, $change->old_value);
        $this->assertEquals(null, $change->new_value);

        $this->assertEquals($user->fresh(), $change->user);
    }

    /**
     *
     */
    public function test_a_change_is_logged_when_a_model_is_updated_without_a_logged_in_user()
    {
        $dummy = Dummy::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        $created_change = $dummy->trackedChanges()
                                ->latest()
                                ->get()
                                ->first();

        $previous = $dummy->name;

        $dummy->name = $this->faker->word;

        $dummy->save();

        $this->assertEquals(2, \Sinclair\Track\Track::all()
                                                    ->count());

        $change = $dummy->trackedChanges()
                        ->where('changes.id', '!=', $created_change->id)
                        ->latest()
                        ->first();

        $this->assertEquals(Dummy::class, $change->tracked_type);
        $this->assertEquals($dummy->id, $change->tracked_id);
        $this->assertEquals(null, $change->user_id);
        $this->assertEquals('Updated', $change->event);
        $this->assertEquals('name', $change->field);
        $this->assertEquals($previous, $change->old_value);
        $this->assertEquals($dummy->name, $change->new_value);
    }

    /**
     *
     */
    public function test_a_change_is_logged_when_a_model_is_updated_with_a_logged_in_user()
    {
        $user = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user);

        $dummy = Dummy::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        $created_change = $dummy->trackedChanges()
                                ->latest()
                                ->get()
                                ->first();

        $previous = $dummy->name;

        $dummy->name = $this->faker->word;

        $dummy->save();

        $this->assertEquals(2, \Sinclair\Track\Track::all()
                                                    ->count());

        $change = $dummy->trackedChanges()
                        ->where('changes.id', '!=', $created_change->id)
                        ->latest()
                        ->first();

        $this->assertEquals(Dummy::class, $change->tracked_type);
        $this->assertEquals($dummy->id, $change->tracked_id);
        $this->assertEquals(auth()->user()->id, $change->user_id);
        $this->assertEquals('Updated', $change->event);
        $this->assertEquals('name', $change->field);
        $this->assertEquals($previous, $change->old_value);
        $this->assertEquals($dummy->name, $change->new_value);
    }

    /**
     *
     */
    public function test_a_change_is_logged_when_a_model_is_deleted_and_has_soft_deletes_and_user_is_not_logged_in()
    {
        $dummy = DummySoftDelete::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        $created_change = $dummy->trackedChanges->first();

        $dummy->delete();

        $dummy = DummySoftDelete::withTrashed()
                                ->find($dummy->id);

        $change = $dummy->trackedChanges()
                        ->where('changes.id', '!=', $created_change->id)
                        ->latest()
                        ->first();

        $this->assertEquals(DummySoftDelete::class, $change->tracked_type);
        $this->assertEquals($dummy->id, $change->tracked_id);
        $this->assertEquals(null, $change->user_id);
        $this->assertEquals('Deleted', $change->event);
        $this->assertEquals(null, $change->field);
        $this->assertEquals(null, $change->old_value);
        $this->assertEquals(null, $change->new_value);
    }

    /**
     *
     */
    public function test_a_change_is_logged_when_a_model_is_deleted_and_has_soft_deletes_and_user_is_logged_in()
    {
        $user = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user);

        $dummy = DummySoftDelete::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        $created_change = $dummy->trackedChanges->first();

        $dummy->delete();

        $dummy = DummySoftDelete::withTrashed()
                                ->find($dummy->id);

        $change = $dummy->trackedChanges()
                        ->where('changes.id', '!=', $created_change->id)
                        ->latest()
                        ->first();

        $this->assertEquals(DummySoftDelete::class, $change->tracked_type);
        $this->assertEquals($dummy->id, $change->tracked_id);
        $this->assertEquals($user->id, $change->user_id);
        $this->assertEquals('Deleted', $change->event);
        $this->assertEquals(null, $change->field);
        $this->assertEquals(null, $change->old_value);
        $this->assertEquals(null, $change->new_value);
    }

    /**
     *
     */
    public function test_a_change_is_logged_when_a_model_is_restored_and_the_model_uses_soft_deletes_and_a_user_is_logged_in()
    {
        $user = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user);

        $dummy = DummySoftDelete::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        $created_change = $dummy->trackedChanges->first();

        $dummy->delete();

        $dummy = DummySoftDelete::withTrashed()
                                ->find($dummy->id);

        $deleted_change = $dummy->trackedChanges()
                                ->where('changes.id', '!=', $created_change->id)
                                ->latest()
                                ->first();

        $dummy->restore();

        $restored_change = $dummy->trackedChanges()
                                 ->whereNotIn('changes.id', [ $created_change->id, $deleted_change->id ])
                                 ->latest()
                                 ->first();

        $this->assertEquals(DummySoftDelete::class, $restored_change->tracked_type);
        $this->assertEquals($dummy->id, $restored_change->tracked_id);
        $this->assertEquals($user->id, $restored_change->user_id);
        $this->assertEquals('Restored', $restored_change->event);
        $this->assertEquals(null, $restored_change->field);
        $this->assertEquals(null, $restored_change->old_value);
        $this->assertEquals(null, $restored_change->new_value);
    }

    public function test_an_updated_change_is_not_logged_when_restoring_a_model()
    {
        $user = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user);

        $dummy = DummySoftDelete::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        $created_change = $dummy->trackedChanges->first();

        $dummy->delete();

        $dummy = DummySoftDelete::withTrashed()
                                ->find($dummy->id);

        $deleted_change = $dummy->trackedChanges()
                                ->where('changes.id', '!=', $created_change->id)
                                ->latest()
                                ->first();

        $dummy->restore();

        $restored_change = $dummy->trackedChanges()
                                 ->whereNotIn('changes.id', [ $created_change->id, $deleted_change->id ])
                                 ->latest()
                                 ->first();

        $this->assertEquals(DummySoftDelete::class, $restored_change->tracked_type);
        $this->assertEquals($dummy->id, $restored_change->tracked_id);
        $this->assertEquals($user->id, $restored_change->user_id);
        $this->assertEquals('Restored', $restored_change->event);
        $this->assertEquals(null, $restored_change->field);
        $this->assertEquals(null, $restored_change->old_value);
        $this->assertEquals(null, $restored_change->new_value);
        $this->assertEquals(3, $dummy->trackedChanges()
                                     ->count());
    }

    public function test_i_can_log_a_custom_event()
    {
        $user = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user);

        event(new MyCustomEvent());

        $change = \Sinclair\Track\Track::all()
                                       ->first();

        $this->assertEquals(MyCustomEvent::class, $change->tracked_type);
        $this->assertEquals(0, $change->tracked_id);
        $this->assertEquals($user->id, $change->user_id);
        $this->assertEquals(MyCustomEvent::class, $change->event);
        $this->assertNull($change->field);
        $this->assertNull($change->old_value);
        $this->assertNull($change->new_value);
    }

    public function test_i_can_manually_add_an_entry()
    {
        $user = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user);

        $dummy = Dummy::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        // first change tracked
        $first_change = $dummy->trackedChanges->first();

        $dummy->log('Custom', $dummy, [ 'field' => 'my_custom_field', 'old_value' => 'old', 'new_value' => 'new' ]);

        // second changed tracked

        $custom_change = $dummy->trackedChanges()
                               ->where('changes.id', '!=', $first_change->id)
                               ->first();

        $this->assertEquals(2, $dummy->trackedChanges()
                                     ->count());
        $this->assertEquals(Dummy::class, $custom_change->tracked_type);
        $this->assertEquals($dummy->id, $custom_change->tracked_id);
        $this->assertEquals($user->id, $custom_change->user_id);
        $this->assertEquals('Custom', $custom_change->event);
        $this->assertEquals('my_custom_field', $custom_change->field);
        $this->assertEquals('old', $custom_change->old_value);
        $this->assertEquals('new', $custom_change->new_value);
    }

    public function test_i_cannot_add_fields_that_do_not_exist_on_the_changes_table_when_adding_a_custom_entry()
    {
        $user = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user);

        $dummy = Dummy::create([
            'name'   => $this->faker->word,
            'number' => $this->faker->unique()->ean13,
            'plan'   => $this->faker->word,
        ]);

        // first change tracked
        $first_change = $dummy->trackedChanges->first();

        $dummy->log('Custom', $dummy, [ 'non_existing_field' => 'my_custom_field' ]);

        // second changed tracked
        $custom_change = $dummy->trackedChanges()
                               ->where('changes.id', '!=', $first_change->id)
                               ->first();

        $this->assertEquals(2, $dummy->trackedChanges()
                                     ->count());
        $this->assertEquals(Dummy::class, $custom_change->tracked_type);
        $this->assertEquals($dummy->id, $custom_change->tracked_id);
        $this->assertEquals($user->id, $custom_change->user_id);
        $this->assertEquals('Custom', $custom_change->event);
        $this->assertNull($custom_change->field);
        $this->assertNull($custom_change->old_value);
        $this->assertNull($custom_change->new_value);
    }
}