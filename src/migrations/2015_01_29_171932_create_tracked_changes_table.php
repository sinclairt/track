<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackedChangesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('changes', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('tracked_type');
			$table->integer('tracked_id');
			$table->integer('user_id')->nullable();
			$table->string('event');
			$table->string('field');
			$table->text('old_value')->nullable();
			$table->text('new_value')->nullable();
			$table->timestamps();

			$table->index(['tracked_id', 'tracked_type']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('changes');
	}

}
