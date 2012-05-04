<?php
Glue::import('JSMin', 'glue/plugins/JSMin/JSMin.php');

class GClientScript{

	const HEAD = 1;
	const BODY_END = 2;

	private $cssTags;
	private $jsTags;
	private $tags = array();

	function addCssFile($map, $path, $media = null, $POS = self::HEAD){
		$this->cssTags[$map] = array( 'path' => $path, 'type' => 'file', 'media' => $media, 'pos' => $POS );
	}

	function addCssScript($map, $script, $media = null, $POS = self::HEAD){
		$this->cssTags[$map] = array( 'script' => $script, 'type' => 'script', 'media' => $media, 'pos' => $POS );
	}

	function addCoreJsFile($map, $path){
		if(is_array($this->jsTags)){
			$this->jsTags = array_merge(array($map => array( 'path' => $path, 'type' => 'file', 'pos' => self::HEAD )), $this->jsTags);
		}else{
			$this->jsTags[$map] = array( 'path' => $path, 'type' => 'file', 'pos' => self::HEAD  );
		}
	}

	function addJsFile($map, $path, $POS = self::HEAD){
		$this->jsTags[$map] = array( 'path' => $path, 'type' => 'file', 'pos' => $POS  );
	}

	function addJsScript($map, $script, $POS = self::BODY_END){
		$this->jsTags[$map] = array( 'script' => $script, 'type' => 'script', 'pos' => $POS );
	}

	function addTag($html, $POS = self::HEAD){
		$this->tags[] = array( 'html' => $html, 'pos' => $POS );
	}

	/**
	 * Inserts the scripts in the head section.
	 * @param string $output the output to be inserted with scripts.
	 */
	public function renderHead(&$output){
		$html='';
		foreach($this->tags as $k=>$val){
			if($val['pos'] == self::HEAD){
				$html.=$val['html'];
			}
		}

		foreach($this->cssTags as $k => $val){
			if($val['type'] == 'file' && $val['pos'] == self::HEAD){
				$html.=html::cssFile($val['path'], $val['media'])."\n";
			}
		}
		foreach($this->jsTags as $k => $val){
			if($val['type'] == 'file' && $val['pos'] == self::HEAD){
				$html.=html::jsFile($val['path'])."\n";
			}
		}
		foreach($this->cssTags as $k => $val){
			if($val['type'] == 'script' && $val['poos'] == self::HEAD){
				$html.=html::css($val['media'], $val['script'])."\n";
			}
		}

		$code = '';
		foreach($this->jsTags as $k => $val){
			if($val['type'] == 'script' && $val['pos'] == self::HEAD){
				if(glue::$params['MinJS']){
					$code.= JSMin::minify($val['script']);
				}else{
					$code.= $val['script'];
				}
			}
		}

		if(!empty($code)){
			$html.=html::js($code)."\n";
		}

		if($html!=='')
		{
			$count=0;
			$output=preg_replace('/(<title\b[^>]*>|<\\/head\s*>)/is','<###head###>$1',$output,1,$count);
			if($count)
				$output=str_replace('<###head###>',$html,$output);
			else
				$output=$html.$output;
		}
		return $output;
	}

	/**
	 * Inserts the scripts at the end of the body section.
	 * @param string $output the output to be inserted with scripts.
	 */
	public function renderBodyEnd(&$output){

		$fullPage=0;
		$output=preg_replace('/(<\\/body\s*>)/is','<###end###>$1',$output,1,$fullPage);
		$html='';
		foreach($this->cssTags as $k => $val){
			if($val['type'] == 'file' && $val['pos'] == self::BODY_END){
				$html.=html::cssFile($val['path'], $val['media'])."\n";
			}
		}

		$code = '';
		foreach($this->jsTags as $k => $val){
			if($val['type'] == 'script' && $val['pos'] == self::BODY_END){
				if(glue::$params['MinJS']){
					$code .= JSMin::minify($val['script']);
				}else{
					$code .= JSMin::minify($val['script']);
				}
			}
		}

		if(!empty($code))
			$html.=html::js($code);

		if($fullPage)
			$output=str_replace('<###end###>',$html,$output);
		else
			$output=$output.$html;

		return $output;
	}

	/**
	 * Encodes a PHP variable into javascript representation.
	 *
	 * Example:
	 * <pre>
	 * $options=array('key1'=>true,'key2'=>123,'key3'=>'value');
	 * echo CJavaScript::encode($options);
	 * // The following javascript code would be generated:
	 * // {'key1':true,'key2':123,'key3':'value'}
	 * </pre>
	 *
	 * For highly complex data structures use {@link jsonEncode} and {@link jsonDecode}
	 * to serialize and unserialize.
	 *
	 * @param mixed $value PHP variable to be encoded
	 * @return string the encoded string
	 */
	public static function encode($value)
	{
		if(is_string($value))
		{
			if(strpos($value,'js:')===0)
				return substr($value,3);
			else
				return "'".self::quote($value)."'";
		}
		else if($value===null)
			return 'null';
		else if(is_bool($value))
			return $value?'true':'false';
		else if(is_integer($value))
			return "$value";
		else if(is_float($value))
		{
			if($value===-INF)
				return 'Number.NEGATIVE_INFINITY';
			else if($value===INF)
				return 'Number.POSITIVE_INFINITY';
			else
				return rtrim(sprintf('%.16F',$value),'0');  // locale-independent representation
		}
		else if(is_object($value))
			return self::encode(get_object_vars($value));
		else if(is_array($value))
		{
			$es=array();
			if(($n=count($value))>0 && array_keys($value)!==range(0,$n-1))
			{
				foreach($value as $k=>$v)
					$es[]="'".self::quote($k)."':".self::encode($v);
				return '{'.implode(',',$es).'}';
			}
			else
			{
				foreach($value as $v)
					$es[]=self::encode($v);
				return '['.implode(',',$es).']';
			}
		}
		else
			return '';
	}

	public static function quote($js,$forUrl=false)
	{
		if($forUrl)
			return strtr($js,array('%'=>'%25',"\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\','</'=>'<\/'));
		else
			return strtr($js,array("\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\','</'=>'<\/'));
	}
}