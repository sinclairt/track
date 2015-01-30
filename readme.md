Installation

First ensure you have listed the Sterling Satis Repository in the composer.json file:

 "repositories": [
         {
             "type": "composer",
             "url": "http://satis.sterling-design.co.uk"
         }
     ]

Then add this line to your composer file:

'sterling/track' : 'dev-master'

Run composer install

Next you need to register the service provider in app/config/app.php:

'Sterling\Track\TrackServiceProvider'

All the changes are logged in a database table so run:

php artisan migrate --package="sterling/track"

Tip: To ensure you don't run into problems when refreshing or rolling back the migrations you can publish this migration so that artisan includes it in future.

php artisan migrate:publish --package="sterling/track"

Usage

All you need to do is use the TrackTrait in any model which extends Eloquent that you want tracking.

Note if you include the TrackTrait before running the migrations and seed the database for example the package will fail as the table does not exist to insert data into.

The following events are tracked automatically: Created, Updating, Updated, Deleted, and Restored (This is only tracked when the SoftDeleteTrait is also in use by the model).

This package can track pivot table sync, but an event is not fired inside Laravel so you must implement this manually. This method will only work after calling sync().

After calling this method return the results into the method trackPivotChanges() along with the current model and the other fully qualified related model name.

Example:

$ids = [1, 2, 3, 4];

$changes = $group->addressees()->sync($ids);

$group->trackPivotChanges($changes, $group, 'Sterling\Repositories\Addressee\Addressee');

It is not necessary to use a fully qualified class name however if you want to use the built in presenter then you will, otherwise you can create your own implmentation.

Presenting

To get the changes of particular object call the method trackedChanges() on your tracked object. This will return an Eloquent Object.

The Track model has a view presenter configured for your benefit.

Example:

@foreach($object->trackedChanges as $change)
	<tr>
		<td>{{ $change->present()->prettyChange() }}</td>
	</tr>
@endforeach

The following text will be returned:

"Updated"  : User updated the example field from the old value to the new value
"Attached" : User attached an ExampleObject called Example
"Detached" : User detached an ExampleObject called Example
"Default"  : User created/deleted/restored Example with an ID of 1

The presenter uses the Auth User model by default but you can change this in the config file.

To publish the config file use:

php artisan config:publish --package="sterling/track"

The field name used for display is also listed here with username being the default. Equally, when the Presenter is called and it names the attached/detached object it is using the field name of "name" by default. There is no configuration for this as it could be different for each class, in this circumstance you can use the Presenter as a template and create your own, or ensure your tables that have pivots have a name field on them.