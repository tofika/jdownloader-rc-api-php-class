A PHP wrapper class for JDownloader RemoteControl API.


Usage:

```php
$j = new JDRCAPI( 'localhost:1912');

if( $j -> j_is_online()) print_r( "online!\n"); else print_r( "offline!\n");

$packagename = "PACKAGE_NAME";

$links = array( "http://link1","http://link2","http://link3");

$r = $j -> j_add_links( $packagename, $links);
```


Copyright (c) 2013 Anatoliy Kultenko "tofik".
Released under the BSD License, see http://opensource.org/licenses/BSD-3-Clause
