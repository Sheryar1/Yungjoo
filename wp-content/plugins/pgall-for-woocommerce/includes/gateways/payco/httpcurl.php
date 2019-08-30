<?php
/**
 *  File Name : httpcurl.php
 * 
 **/    

class HTTPCURL {
    
    var $timeout;
    var $ch = null;
    var $response   = array();  
    var $contents = null;       
    
    function HTTPCURL($timeout = 30)
    {    
        $this->timeout = $timeout;
        $this->ch = curl_init();

        curl_setopt ($this->ch, CURLOPT_CONNECTTIMEOUT , $this->timeout );
        curl_setopt ($this->ch, CURLOPT_TIMEOUT, $this->timeout );
        curl_setopt ($this->ch, CURLOPT_HEADER, false);
        curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt ($this->ch, CURLOPT_MAXREDIRS,5);
    }
    
    function Get($url, $ssl_chk = true)
    {
        $urls = parse_url($url);
        if($urls["scheme"] == "https"){
            curl_setopt ($this->ch, CURLOPT_SSL_VERIFYPEER, $ssl_chk);
            curl_setopt ($this->ch, CURLOPT_SSL_VERIFYHOST, $ssl_chk);
        }   
            
        curl_setopt ($this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt ($this->ch, CURLOPT_URL, $url);

        $this->contents = curl_exec( $this->ch );
        $this->response = curl_getinfo( $this->ch );
    } 

    function Post($url , $type, $data, $ssl_chk = true)
    {
        $urls = parse_url($url);
        if($urls["scheme"] == "https"){
            curl_setopt ($this->ch, CURLOPT_SSL_VERIFYPEER, $ssl_chk);
            curl_setopt ($this->ch, CURLOPT_SSL_VERIFYHOST, $ssl_chk);
        }
    
        curl_setopt ($this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/'.$type));
        curl_setopt ($this->ch, CURLOPT_URL, $url);
        curl_setopt ($this->ch, CURLOPT_POST, TRUE);
        curl_setopt ($this->ch, CURLOPT_POSTFIELDS, $data);

        $this->contents = curl_exec( $this->ch );
		echo curl_error($this->ch);
        $this->response = curl_getinfo( $this->ch );
    }
    
    function Close()
    {
        curl_close ( $this->ch );
    }

    function getResult()
    {
        if ( !strcmp($this->response['http_code'],"200") ) {
            return $this->contents;
        } else {
            return null;
        }
    }
}

?>