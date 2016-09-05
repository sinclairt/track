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
    }

    /**
     *
     */
    public function test_i_can_get_rows_of_changes()
    {
        for ( $i = 0; $i < 20; $i++ )
            Dummy::create([
                'name'   => $this->faker->word,
                'number' => $this->faker->unique()->ean13,
                'plan'   => $this->faker->word,
            ]);

        $response = $this->get('/api/v1/track')->response;
        $content = json_decode($response->content());

        $this->assertEquals(15, sizeof($content->data));

        $this->assertEquals(200, $response->status());

        $this->checkStructure($content);

        foreach ( Track::limit(15)
                       ->get() as $key => $track )
        {
            $db = json_decode($track->toJson());

            $api = $content->data[ $key ]->attributes;

            $attributes = [ 'tracked_type', 'tracked_id', 'user_id', 'event', 'field', 'old_value', 'new_value', 'created_at', 'updated_at' ];

            foreach ( $attributes as $attribute )
                $this->assertTrue($db->$attribute == $api->$attribute);

            $this->assertTrue($content->data[ $key ]->id == $db->id);
        }
    }

    public function test_i_can_get_a_filtered_set_of_changes()
    {
        for ( $i = 0; $i < 20; $i++ )
            Dummy::create([
                'name'   => $this->faker->word,
                'number' => $this->faker->unique()->ean13,
                'plan'   => $this->faker->word,
            ]);

        $response = $this->post('/api/v1/track', [
            'tracked_id' => 1,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(1, sizeof($content->data));

        // TODO finish this - it doesn't pass
    }

    /**
     * @param $content
     */
    private function checkStructure( $content )
    {
        $this->assertObjectHasAttribute('data', $content);
        $this->assertObjectHasAttribute('meta', $content);
        $this->assertObjectHasAttribute('links', $content);
        $this->assertObjectHasAttribute('pagination', $content->meta);
        $this->assertObjectHasAttribute('total', $content->meta->pagination);
        $this->assertObjectHasAttribute('count', $content->meta->pagination);
        $this->assertObjectHasAttribute('per_page', $content->meta->pagination);
        $this->assertObjectHasAttribute('current_page', $content->meta->pagination);
        $this->assertObjectHasAttribute('total_pages', $content->meta->pagination);
        $this->assertObjectHasAttribute('self', $content->links);
        $this->assertObjectHasAttribute('first', $content->links);
        $this->assertObjectHasAttribute('next', $content->links);
        $this->assertObjectHasAttribute('last', $content->links);
    }
}