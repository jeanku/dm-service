<?php

use NoahBuscher\Macaw\Macaw;

Macaw::get('fuck', function() {
  echo "成功！222";
});

Macaw::get('(:all)', function($fu) {
  echo '未匹配到路由<br>'.$fu;
});

$data = Macaw::get('/home', '\App\Controllers\HomeController@home');

//$data = Macaw::dispatch();
echo "<pre>";
print_r($data);
exit;