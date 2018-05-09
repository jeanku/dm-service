## Install

Via Composer

``` bash
 composer require jeanku/database:dev-master
```


# initialization
you can set database config at entrance file(index.php) as follow:
``` bash
\Jeanku\Database\DatabaseManager::make(WEBPATH . '/config/database.php');
```

# extend model
you can extend the model as follow:
``` bash
 class CoachModel extends \Jeanku\Database\Eloquent\Model
```

then you can use it as laravel model:
``` bash
 $data = CoachModel::where('id', 10001)->get()->toArray();
 ```

# db
if you wantto use laravel DB to do sth, then you can use the class as this:
``` bash
 use Jeanku\Database\DatabaseManager as DB;
```
then you can use it as laravel DB:
``` bash
 $data = DB::table('table')->where('id', 10001)->get();
```
 
	


 
