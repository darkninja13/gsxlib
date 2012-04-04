##About##

GsxLib is a PHP library that simplifies communication with Apple's [GSX web service API][1]. It frees the application developer
from knowing the underlying PHP SOAP architecture and to some extent even the GSX API itself. GsxLib also tries to provide
some performance benefits by minimizing the number of requests made to the servers as well as doing some rudimentary input
validation (as opposed to burdening Apple's servers with totally invalid requests).

##Requrements##

Your PHP must have SOAP support. Most distributions should (including OS X Server 10.6 or later). For more details, consult your distribution or the [PHP documentation][2].

##Usage##

Best illustrated with a simple example:

    <?php
  
      include 'gsxlib/gsxlib.php';
      $gsx = new GsxLib($sold_to, $username, $password);
      $info = $gsx->warrantyStatus($serialnumber);
      echo $info->productDescription;
      > MacBook Pro (15-inch 2.4/2.2GHz)
      
    ?>

If you're in the US, remember to set the fifth argument to the constructor to 'am'.

##gsxcl##

The package includes a rudimentary command line client to the GSX API called _gsxcl_. It can perform various functions in the library and is meant
mainly as a simple test tool for the library.

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

[1]: http://gsxwsut.apple.com/apidocs/html/WSReference.html?user=asp
[2]: http://php.net/manual/en/book.soap.php
