##About##
GsxLib is a PHP library that simplifies communication with Apple's GSX web service API. It frees the application developer
from knowing the underlying PHP SOAP architecture and to some extent even the GSX API itself. GsxLib also tries to provide
some performance benefits by minimizing the number of requests made to the servers as well as doing some rudimentary input
validation (as opposed to burdening Apple's servers with totally invalid requests).

##Requrements##
Your PHP should have SOAP support enabled. Most distributions should (including OS X Server 10.6 or later).
For more details, consult your distribution or http://php.net/manual/en/book.soap.php.

##Usage##

  <?php
  
    include 'gsxlib/gsxlib.php';
    $gsx = new GsxLib('your sold-to account', 'gsx user', 'password');
    $info = $gsx->warrantyStatus('serialnumber');
  
  ?>


##License##

  DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE 
    Version 2, December 2004
    Copyright (C) 2004 Sam Hocevar <sam@hocevar.net> 
    Everyone is permitted to copy and distribute verbatim or modified 
    copies of this license document, and changing it is allowed as long 
    as the name is changed. 
    
    DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE 
    TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION 
    0. You just DO WHAT THE FUCK YOU WANT TO.
