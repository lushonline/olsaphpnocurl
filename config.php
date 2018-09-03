<?php
unset($CFG);
$CFG = new stdclass();

//Define the OLSA Web Service Settings
$CFG->endpoint    = 'https://{customer}.skillwsa.com/olsa/services/Olsa';
$CFG->customerid    = '{customerid}';
$CFG->sharedsecret    = '{sharedsecret}';
?>