# olsaphp
Example code for performing [WS-Security UserNameToken](https://www.oasis-open.org/committees/download.php/13392/wss-v1.1-spec-pr-UsernameTokenProfile-01.htm) authentication for OLSA Web Services, utilising OpenSSL and PHP SoapClient

Minimum PHP version that supports TLS 1.2 is PHP 5.5.19 with OpenSSL 1.0.1

For an alternate version that uses cURL see [https://github.com/martinholden-skillsoft/olsaphpcurl](https://github.com/martinholden-skillsoft/olsaphpcurl)

# Config
In the config.php you need to enter the OLSA endpoint url, customerid and shared secret

In PHP.INI you need to ensure the SOAP client and OPENSSL extensions are enabled.

# SOAP Client Code Details
The [WSSUserNameTokenSoapClient.class.php](WSSUserNameTokenSoapClient.class.php) contains an implementation of a PHP SOAP client that supports WS-Security UserNameToken Password Digest.

# Testing
Run the [test.php](test.php) on the command line, it will attempt to call the SO_GetMultiActionSignOnExtended function with username **olsatest** and display the returned URL to use to seamlessly log the user in.

