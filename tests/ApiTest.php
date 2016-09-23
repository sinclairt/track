<?php

require_once 'DbTestCase.php';
require_once 'Models/Dummy.php';

use Sinclair\Track\Track;

/**
 * Class ApiTest
 */
class ApiTest extends DbTestCase
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

        $this->app[ 'router' ]->bind('track', function ( $value )
        {
            return Track::find($value);
        });

        $this->app[ 'router' ]->group([ 'middleware' => 'bindings' ], function ()
        {
            require __DIR__ . '/../src/routes/track_api.php';
        });
    }

    /**
     *
     */
    public function test_i_can_get_rows_of_changes()
    {
        $this->createDummies(20);

        $response = $this->get('/track')->response;
        $content = json_decode($response->content());

        $this->assertEquals(15, sizeof($content->data));

        $this->assertEquals(200, $response->status());

        $this->checkStructure($content);

        $this->assertObjectHasAttribute('next', $content->links);

        foreach ( Track::limit(15)
                       ->get() as $key => $track )
            $this->checkAttributes($track, $content, $key);
    }

    public function test_i_can_still_get_rows_if_i_apply_no_filters()
    {
        $this->createDummies(20);

        $response = $this->json('POST', '/track/filter', [])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        $this->assertEquals(15, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_tracked_id()
    {
        $this->createDummies(20);

        $response = $this->json('POST', '/track/filter', [
            'tracked_id' => 1,
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        $this->checkAttributes(Dummy::find(1)->trackedChanges->first(), $content);

        $this->assertEquals(1, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_tracked_type()
    {
        $this->createDummies(20);
        $this->createSoftDeleteDummies(20);

        $response = $this->json('POST', '/track/filter', [
            'tracked_type' => Dummy::class,
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        foreach ( Dummy::limit(15)
                       ->get() as $key => $dummy )
            $this->checkAttributes($dummy->trackedChanges()
                                         ->first(), $content, $key);

        // although we have 20 dummies, we are restricting the rows to 15
        $this->assertEquals(15, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_user_id()
    {
        $user1 = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user1);

        $this->createDummies(5);

        auth()->logout();

        $user2 = \App\User::create([
            'name'     => $this->faker->name,
            'email'    => $this->faker->email,
            'password' => $this->faker->word
        ]);

        auth()->login($user2);

        $this->createDummies(5);

        $response = $this->json('POST', '/track/filter', [
            'user_id' => $user1->id,
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        foreach ( Dummy::createdBy($user1->id)
                       ->get() as $key => $dummy )
            $this->checkAttributes($dummy->trackedChanges()
                                         ->createdBy($user1->id)
                                         ->first(), $content, $key);

        $this->assertEquals(5, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_created_event()
    {
        $this->createDummies(20);

        $response = $this->json('POST', '/track/filter', [
            'event' => 'Created',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        foreach ( Dummy::limit(15)
                       ->get() as $key => $dummy )
            $this->checkAttributes($dummy->trackedChanges()
                                         ->created()
                                         ->first(), $content, $key);

        $this->assertEquals(15, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_updated_event()
    {
        $this->createDummies(20);

        $dummy = Dummy::all()
                      ->random();

        $dummy->name = $this->faker->word;
        $dummy->save();

        $response = $this->json('POST', '/track/filter', [
            'event' => 'Updated',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        $this->checkAttributes($dummy->trackedChanges()
                                     ->updated()
                                     ->first(), $content);

        $this->assertEquals(1, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_restored_event()
    {
        $this->createSoftDeleteDummies(20);

        $dummy = DummySoftDelete::all()
                                ->random();

        $dummy->name = $this->faker->word;
        $dummy->save();

        $dummy->delete();

        $dummy->restore();

        $response = $this->json('POST', '/track/filter', [
            'event' => 'Restored',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        $this->checkAttributes($dummy->trackedChanges()
                                     ->restored()
                                     ->first(), $content);

        $this->assertEquals(1, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_deleted_event()
    {
        $this->createSoftDeleteDummies(20);

        $dummy = DummySoftDelete::all()
                                ->random();

        $dummy->name = $this->faker->word;
        $dummy->save();

        $dummy->delete();

        $response = $this->json('POST', '/track/filter', [
            'event' => 'Deleted',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        $this->checkAttributes($dummy->trackedChanges()
                                     ->deleted()
                                     ->first(), $content);

        $this->assertEquals(1, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_custom_event()
    {
        event(new MyCustomEvent());

        $response = $this->json('POST', '/track/filter', [
            'event' => 'MyCustomEvent',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        $this->checkAttributes(Track::where('event', 'MyCustomEvent')
                                    ->first(), $content);

        $this->assertEquals(1, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_attached_event()
    {
        $this->createDummies(1);

        $changes = [
            'attached' => [ 1, 2, 3, 4 ],
            'detached' => [ 5, 6, 7, 8 ],
            'changed'  => []
        ];

        $dummy = Dummy::all()
                      ->first();

        $dummy->trackPivotChanges($changes, $dummy, 'MyRandomClass');

        $response = $this->json('POST', '/track/filter', [
            'event' => 'Attached',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        foreach ( $dummy->trackedChanges()
                        ->attached()
                        ->get() as $key => $track )
            $this->checkAttributes($track, $content, $key);

        $this->assertEquals(4, sizeof($content->data));
    }

    public function test_i_can_get_filter_the_changes_by_detached_event()
    {
        $this->createDummies(1);

        $changes = [
            'attached' => [ 1, 2, 3, 4 ],
            'detached' => [ 5, 6, 7, 8 ],
            'changed'  => []
        ];

        $dummy = Dummy::all()
                      ->first();

        $dummy->trackPivotChanges($changes, $dummy, 'MyRandomClass');

        $response = $this->json('POST', '/track/filter', [
            'event' => 'Detached',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        foreach ( $dummy->trackedChanges()
                        ->detached()
                        ->get() as $key => $track )
            $this->checkAttributes($track, $content, $key);

        $this->assertEquals(4, sizeof($content->data));
    }

    public function test_i_can_filter_changes_by_the_field()
    {
        foreach ( $dummies = $this->createDummies(10) as $dummy )
        {
            $dummy->name = $this->faker->word;
            $dummy->save();
        }

        $response = $this->json('POST', '/track/filter', [
            'field' => 'name',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        foreach ( $dummies as $key => $dummy )
            $this->checkAttributes($dummy->fresh()
                                         ->trackedChanges()
                                         ->updated()
                                         ->first(), $content, $key);

        $this->assertEquals(10, sizeof($content->data));
    }

    public function test_i_can_filter_changes_by_the_new_value()
    {
        $words = [];
        foreach ( $dummies = $this->createDummies(10) as $dummy )
        {
            $word = $this->faker->word;
            $words[] = $word;
            $dummy->name = $word;
            $dummy->save();
        }

        $response = $this->json('POST', '/track/filter', [
            'new_value' => head(array_keys(array_count_values($words))),
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        foreach ( Dummy::where('name', head(array_keys(array_count_values($words))))
                       ->get() as $key => $dummy )
            $this->checkAttributes($dummy->trackedChanges()
                                         ->updated()
                                         ->first(), $content, $key);

        $this->assertEquals(head(array_count_values($words)), sizeof($content->data));
    }

    public function test_i_can_filter_changes_by_the_old_value()
    {
        $dummies = $this->createDummies(10, [ 'name' => 'foo' ]);

        $this->createDummies(10, [ 'name' => 'bar' ]);

        foreach ( $dummies as $dummy )
        {
            $dummy->name = 'baz';
            $dummy->save();
        }

        $response = $this->json('POST', '/track/filter', [
            'old_value' => 'foo',
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        $this->assertEquals(10, sizeof($content->data));

        $expected = Track::where('old_value', 'foo')
                         ->get();

        foreach ( $expected as $key => $change )
        {
            $this->checkAttributes($change, $content, $key);
        }
    }

    public function test_i_can_get_a_single_change()
    {
        $dummy = collect($this->createDummies(1))->first();

        $track = $dummy->trackedChanges
            ->first();

        $response = $this->json('GET', '/track/' . $track->id)->response;

        $content = json_decode($response->content());

        $this->assertObjectHasAttribute('data', $content);

        $db = json_decode($track->toJson());

        $attributes = [ 'id', 'tracked_type', 'tracked_id', 'user_id', 'event', 'field', 'old_value', 'new_value', 'created_at', 'updated_at' ];

        foreach ( $attributes as $attribute )
            $this->assertTrue($db->$attribute == $content->data->$attribute);
    }

    public function test_i_can_do_not_get_a_single_change_when_i_supply_a_non_existent_id()
    {
        $response = $this->json('GET', '/track/1')->response;

        $this->assertEquals(\Illuminate\Http\JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function test_i_can_get_changes_to_an_object()
    {
        // 1
        $dummy = collect($this->createSoftDeleteDummies(1))->first();

        $dummy->name = $this->faker->word;

        // 1
        $dummy->save();

        // 1
        $dummy->delete();

        // 1
        $dummy->restore();

        // 8
        $changes = [
            'attached' => [ 1, 2, 3, 4 ],
            'detached' => [ 5, 6, 7, 8 ],
            'changed'  => []
        ];

        $dummy->trackPivotChanges($changes, $dummy, 'MyRandomClass');

        // should have 12 changes for this dummy object

        $response = $this->json('POST', '/track/object', [
            'object_class' => DummySoftDelete::class,
            'object_id'    => $dummy->id
        ])->response;

        $content = json_decode($response->content());

        $this->checkStructure($content);

        $this->assertEquals(12, sizeof($content->data));
    }

    public function test_i_get_a_forbidden_response_if_i_supply_a_bad_request_for_object_changes()
    {
        $response = $this->json('POST', '/track/object', [])->response;

        $this->assertEquals('{"object_class":["The object class field is required."],"object_id":["The object id field is required."]}', $response->content());
    }

    /**
     * @param $content
     */
    private function checkStructure( $content )
    {
        $this->assertObjectHasAttribute('data', $content);
        $this->assertObjectHasAttribute('links', $content);
        $this->assertObjectHasAttribute('meta', $content);
        $this->assertObjectHasAttribute('pagination', $content->meta);
        $this->assertObjectHasAttribute('total', $content->meta->pagination);
        $this->assertObjectHasAttribute('count', $content->meta->pagination);
        $this->assertObjectHasAttribute('per_page', $content->meta->pagination);
        $this->assertObjectHasAttribute('current_page', $content->meta->pagination);
        $this->assertObjectHasAttribute('total_pages', $content->meta->pagination);
        $this->assertObjectHasAttribute('self', $content->links);
        $this->assertObjectHasAttribute('first', $content->links);
        $this->assertObjectHasAttribute('last', $content->links);
    }

    /**
     * @param $track
     * @param $content
     * @param $key
     */
    private function checkAttributes( $track, $content, $key = 0 )
    {
        $db = json_decode($track->toJson());

        $api = $content->data[ $key ]->attributes;

        $attributes = [ 'tracked_type', 'tracked_id', 'user_id', 'event', 'field', 'old_value', 'new_value', 'created_at' ];

        foreach ( $attributes as $attribute )
            $this->assertEquals($db->$attribute, $api->$attribute);

        $this->assertTrue($content->data[ $key ]->id == $db->id);
    }

    /**
     * @param int $count
     *
     * @param array $attributes
     *
     * @return array
     */
    private function createDummies( $count = 1, $attributes = [] )
    {
        $dummies = [];
        for ( $i = 0; $i < $count; $i++ )
            $dummies[] = Dummy::create([
                'name'   => array_get($attributes, 'name', $this->faker->word),
                'number' => array_get($attributes, 'number', $this->faker->unique()->ean13),
                'plan'   => array_get($attributes, 'plan', $this->faker->word),
            ]);

        return $dummies;
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
}