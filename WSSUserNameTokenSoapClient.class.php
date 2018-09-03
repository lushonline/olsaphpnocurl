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
	const PASSWORDTEXT_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText';
	const PASSWORDDIGEST_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest';

	//Private variables
	private $username;
	private $password;
	private $usepassworddigest;
	private $expiry;
	
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
		if ($this->usepassworddigest) {
			//Get the current time
			$currentTime = time();
			//Create the ISO8601 formatted timestamp
			$timestamp=gmdate('Y-m-d\TH:i:s', $currentTime).'Z';
			//Create the expiry timestamp 5 minutes later (60*5)
			$expiretimestamp=gmdate('Y-m-d\TH:i:s', $currentTime + $this->expiry).'Z';
			//Generate the random Nonce. The use of rand() may repeat the word if the server is very loaded.
			$nonce=mt_rand();
			//Create the PasswordDigest for the usernametoken
			$passdigest=base64_encode(pack('H*',sha1(pack('H*',$nonce).pack('a*',$timestamp).pack('a*',$this->password))));
			
			
			$header='
			<wsse:Security env:mustUnderstand="1" xmlns:wsse="'.self::WSSE_NS.'" xmlns:wsu="'.self::WSU_NS.'">
				<wsu:Timestamp wsu:Id="Timestamp-'.$this->guid().'">
					<wsu:Created>'.$timestamp.'</wsu:Created>
					<wsu:Expires>'.$expiretimestamp.'</wsu:Expires>
				</wsu:Timestamp>
				<wsse:UsernameToken xmlns:wsu="'.self::WSU_NS.'">
					<wsse:Username>'.$this->username.'</wsse:Username>
					<wsse:Password Type="'.self::PASSWORDDIGEST_TYPE.'">'.$passdigest.'</wsse:Password>
					<wsse:Nonce>'.base64_encode(pack('H*',$nonce)).'</wsse:Nonce>
					<wsu:Created>'.$timestamp.'</wsu:Created>
				</wsse:UsernameToken>
			</wsse:Security>
			';
		} else {
			$header='
			<wsse:Security env:mustUnderstand="1" xmlns:wsse="'.self::WSSE_NS.'" xmlns:wsu="'.self::WSU_NS.'">
				<wsse:UsernameToken xmlns:wsu="'.self::WSU_NS.'">
					<wsse:Username>'.$this->username.'</wsse:Username>
					<wsse:Password Type="'.self::PASSWORDTEXT_TYPE.'">'.$this->password.'</wsse:Password>
				</wsse:UsernameToken>
			</wsse:Security>
			';
		}

		$headerSoapVar=new SoapVar($header,XSD_ANYXML); //XSD_ANYXML (or 147) is the code to add xml directly into a SoapVar. Using other codes such as SOAP_ENC, it's really difficult to set the correct namespace for the variables, so the axis server rejects the xml.
		$soapheader=new SoapHeader(self::WSSE_NS, "Security" , $headerSoapVar , true);
		return $soapheader;
	}

	/*It's necessary to call it if you want to set a different user and password*/
	public function __setUsernameToken($username,$password,$usepassworddigest=true,$expiry=300){
		$this->username=$username;
		$this->password=$password;
		
		if(is_bool($usepassworddigest)) {
			$this->usepassworddigest=$usepassworddigest;
		} else {
			$this->usepassworddigest=true;
		}
		
		if (is_int($expiry)) {
			$this->expiry=$expiry;
		} else 
		{
			$this->expiry=300;
		}
	}

	public function __getLastRequestHeaders() {
		return implode("\n", $this->__last_request_headers)."\n";
	}

	public function __construct($endpoint,$options) {
		$this->username="";
		$this->password="";
		$this->usepassworddigest=true;
		$this->expiry=300;
		
		if (array_key_exists("wsse_username",$options))
		{
			$this->username=$options["wsse_username"];
		}
		
		if (array_key_exists("wsse_password",$options))
		{
			$this->password=$options["wsse_password"];
		}

		if (array_key_exists("wsse_usepassworddigest",$options))
		{
			if(is_bool($options["wsse_usepassworddigest"])) {
				$this->password=$options["wsse_usepassworddigest"];
			}
		}
		
		if (array_key_exists("wsse_expiry",$options))
		{
			if(is_int($options["wsse_expiry"])) {
				$this->expiry=$options["wsse_expiry"];
			}
		}

		$result = parent::__construct($endpoint, $options);
		return $result;
	}
	
	
	/*Overload the original method, and add the WS-Security Header */
	public function __soapCall($function_name,$arguments,$options=array(),$input_headers=null,&$output_headers=array()){
		$result = parent::__soapCall($function_name,$arguments,$options,$this->generate_header());

		return $result;
	}
	
}
?>