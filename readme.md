# Installation #

`composer require sinclairt/track`

Next you need to register the service provider in app/config/app.php:

```
'Sinclair\Track\TrackServiceProvider'
```

Finally run `php artisan vendor:publish`


# Usage #

All you need to do is use the TrackTrait in any model which extends Eloquent that you want tracking.

Note if you include the TrackTrait before running the migrations and seed the database for example the package will fail as the table does not exist to insert data into.

The following events are tracked automatically: Created, Updating, Updated, Deleted, and Restored (This is only tracked when the SoftDeleteTrait is also in use by the model).

This package can track pivot table sync, but an event is not fired inside Laravel so you must implement this manually. This method will only work after calling `sync()`.

After calling this method return the results into the method `trackPivotChanges()` along with the current model and the other fully qualified related model name.

Example:

```
$ids = [1, 2, 3, 4];

$changes = $group->addressees()->sync($ids);

$group->trackPivotChanges($changes, $group, App\Repositories\Addressee\Addressee::class);
```

It is not necessary to use a fully qualified class name however if you want to use the built in presenter then you will, otherwise you can create your own implementation.

### Custom Logging
You can log anything on an object by calling:

```{object}::log($event, $model = null, $data = [])```

The event is the type of event you are logging, this is where the Track package stores Created/Updated/Deleted etc events.
The model will default to the current object if not passed but it is possible to over ride this. The `$data` variable contains all the additional fields you wish to store against the track, they can be :
* user_id <i>this will default to the logged in user if available</i>
* field
* old_value
* new_value
* event <i>although this is the `$event` variable</i>
* tracked_type <i>this will be taken from your supplied model anyway</i>
* tracked_id <i>as above</i>

### Log Events
Add any event class to the events key inside the track config, and the track package will log the event, it will not attach it to any object, but it will be available via the Track model directly.

There is currently a custom example event, you can remove that (or keep if you fancy).

# API

There are 4 routes into the package:
* <i>GET</i> /api/v1/track - get all tracked changes this is paginated
* <i>POST</i> /api/v1/track/filter - get a filtered set of tracked changes (paginated). Filters available
    * tracked_type
    * tracked_id
    * user_id
    * event
    * field
    * old_value
    * new_value     
* <i>GET</i> /api/v1/track/{track} - get a single tracked change
* <i>POST</i> /api/v1/track/object/ - get all tracked changes by object - this requires the following parameters
    * object_id
    * object_class
    
### API Paginated Calls
You can set the following parameters for paginated calls:
* rows (integer) <i>default 15</i> The number of rows per page
* search (bool) <i>default true</i> Whether to use the search function during filter
* orderBy (string) <i>default null</i> which column to order the results by
* direction (asc|desc) <i>default asc</i> the direction of ordering
* pagination_name (string) <i>default page</i> the name of your paginated set
* columns (array) <i>default ['*']</i>  which columns to return from the database table

# Presenting #

To get the changes of particular object call the method `trackedChanges()` on your tracked object.

The Track model has a view presenter configured for your benefit.

Example:

```
@foreach($object->trackedChanges as $change)
	<tr>
		<td>{{ $change->present()->prettyChange() }}</td>
	</tr>
@endforeach
```


The following text will be returned:

* "Updated"   : User updated the example field from the old value to the new value
* "Attached"  : User attached an ExampleObject called Example
* "Detached" : User detached an ExampleObject called Example
* "Default"     : User <i>{created/deleted/restored}</i> Example with an ID of 1

The presenter uses the Auth User model by default but you can change this in the config file.

The field name used for display is also listed here with username being the default. Equally, when the Presenter is called and it names the attached/detached object it is using the field name of "name" by default. There is no configuration for this as it could be different for each class, in this circumstance you can use the Presenter as a template and create your own, or ensure your tables that have pivots have a name field on them.
