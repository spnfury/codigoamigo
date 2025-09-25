<?php

namespace App\Utiles;
/**
 * GIANNI
 */
class Mongoo extends \MongoClient {

	private $_Host;
	private $Database_;
	private $Collection_;
 
	/**
	* Constructor de la clase y translada todoas las funcionbailidades nativas a SLIM
	*
	* Conecta a la tabla seleccionada dentro de la collection de ocean
	* @param $tableCollection
	*/
	function __construct($Collection_,$_Host="127.0.0.1", $Database_="hello_system") {
 
		$this->host        = new \MongoClient($_Host);
		$this->Database    = $this->host->selectDB($Database_);
		$this->Collection  = new \MongoCollection($this->Database, $Collection_);

		return $this->Collection;
	}

	public function read($cursor){
		return array_values( iterator_to_array( $cursor ) );
	}

	public function Swish($Collection_){

		$this->Collection  = new \MongoCollection($this->Database, $Collection_);

		return  $this->Collection;
	}

}
?>