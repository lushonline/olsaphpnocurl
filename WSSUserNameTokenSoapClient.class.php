<?PHP
/*****************************************************/
/* Class:   WSSUserNameTokenSoapClient               */
/* Author:	Martin Holden, SkillSoft                 */
/* Date:	Aug 2018                                 */
/*                                                   */
/* Extends the PHP5 SoapClient to include the        */
/* necessary WS-Security headers for OLSA            */
/* Tested on PHP 5.5.19, with OpenSSL 1.0.1          */
/*****************************************************/

class WSSUserNameTokenSoapClient extends SoapClient{

	/* ---------------------------------------------------------------------------------------------- */
	/* Constants and Private Variables                                                                */
	
	//Constants for use in code.
	const WSSE_NS  = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
	const WSSE_PFX = 'wsse';
	const WSU_NS   = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
	const WSU_PFX  = 'wsu';
	const PASSWORD_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest';

	//Private variables
	private $username;
	private $password;

	private $proxy;
	
	/* ---------------------------------------------------------------------------------------------- */
	/* Helper Functions                                                                               */

	/* Generate a GUID */
	private function guid(){
	        mt_srand((double)microtime()*10000);
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        $hyphen = chr(45);// "-"
	        $uuid = substr($charid, 0, 8).$hyphen
	                .substr($charid, 8, 4).$hyphen
	                .substr($charid,12, 4).$hyphen
	                .substr($charid,16, 4).$hyphen
	                .substr($charid,20,12);
	        return $uuid;
	}


	private function generate_header() {

		//Get the current time
		$currentTime = time();
		//Create the ISO8601 formatted timestamp
		$timestamp=gmdate('Y-m-d\TH:i:s', $currentTime).'Z';
		//Create the expiry timestamp 5 minutes later (60*5)
		$expiretimestamp=gmdate('Y-m-d\TH:i:s', $currentTime + 300).'Z';
		//Generate the random Nonce. The use of rand() may repeat the word if the server is very loaded.
		$nonce=mt_rand();
		//Create the PasswordDigest for the usernametoken
		$passdigest=base64_encode(pack('H*',sha1(pack('H*',$nonce).pack('a*',$timestamp).pack('a*',$this->password))));

		//Build the header text
		$header='
			<wsse:Security env:mustUnderstand="1" xmlns:wsse="'.self::WSSE_NS.'" xmlns:wsu="'.self::WSU_NS.'">
				<wsu:Timestamp wsu:Id="Timestamp-'.$this->guid().'">
					<wsu:Created>'.$timestamp.'</wsu:Created>
					<wsu:Expires>'.$expiretimestamp.'</wsu:Expires>
				</wsu:Timestamp>
				<wsse:UsernameToken xmlns:wsu="'.self::WSU_NS.'">
					<wsse:Username>'.$this->username.'</wsse:Username>
					<wsse:Password Type="'.self::PASSWORD_TYPE.'">'.$passdigest.'</wsse:Password>
					<wsse:Nonce>'.base64_encode(pack('H*',$nonce)).'</wsse:Nonce>
					<wsu:Created>'.$timestamp.'</wsu:Created>
				</wsse:UsernameToken>
			</wsse:Security>
			';

		$headerSoapVar=new SoapVar($header,XSD_ANYXML); //XSD_ANYXML (or 147) is the code to add xml directly into a SoapVar. Using other codes such as SOAP_ENC, it's really difficult to set the correct namespace for the variables, so the axis server rejects the xml.
		$soapheader=new SoapHeader(self::WSSE_NS, "Security" , $headerSoapVar , true);
		return $soapheader;
	}

	/*It's necessary to call it if you want to set a different user and password*/
	public function __setUsernameToken($username,$password){
		$this->username=$username;
		$this->password=$password;
	}

	public function __getLastRequestHeaders() {
		return implode("\n", $this->__last_request_headers)."\n";
	}

	/*Overload the original method, and add the WS-Security Header */
	public function __soapCall($function_name,$arguments,$options=array(),$input_headers=null,&$output_headers=array()){
		$result = parent::__soapCall($function_name,$arguments,$options,$this->generate_header());

		return $result;
	}
	
}
?>