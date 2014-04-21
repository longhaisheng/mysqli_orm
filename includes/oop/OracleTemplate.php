<?php
 abstract class OracleTemplate implements ITemplate{

	/** key为表中的列名，value为class对象中的属性，必须包含一个key为class的项,(key不区分大小写，value 区分大小写，见意key也小写)如：
	 	array("brand_id"=>'id',"BRAND_NAME"=>'brandName',"brand_logo"=>'brandLogo','class'=>'BrandDO');
	*/
	private $resultMap=array();

	/** 数据库连接cls_oracle */
	public $db;

	/** memcache数据库  */
	public $cache;

	private static $_models = array();

	function __construct() {
		global $ecs, $dbc, $cache;
		$this->db = $dbc;
		$this->cache = $cache;
		$this->init();
	}

	public static function model($className = __CLASS__) {
		if (isset(self::$_models[$className])){
			return self::$_models[$className];
		}else {
			$model = self::$_models[$className] = new $className(null);
			return $model;
		}
	}

	protected function init(){}

	public function save($sql,$object){
		$result=$this->derectorDataObject($sql,$object);
		print_r($result);
		//执行db insert;
	}

	public function update($sql,$object){
		$result=$this->derectorDataObject($sql,$object);
		print_r($result);
		//执行db update;
	}

	public function queryForList($sql,$object){
		$result=$this->derectorDataObject($sql,$object);
		$this->bindParams($result['params']);
		$arrayList= $this->db->getAll($result['sql']);
		return $this->setClassAll($arrayList);
	}

	public function queryForObject($sql,$object=''){
		$result=$this->derectorDataObject($sql,$object);
		$this->bindParams($result['params']);
		$row= $this->db->getRow($result['sql']);
		return $this->setClassOne($row);
	}
	
	public function getColumn($sql,$object=''){
		$result=$this->derectorDataObject($sql,$object);
		$this->bindParams($result['params']);
		return $this->db->getOne($result['sql']);
	}

	private function bindParams($params=array()){
		if($params){
			foreach ($params as $key=>$value){
				$this->db->bind($key, $value);
			}
		}
	}

	private function derectorDataObject($sql,$object){
		$type = $this->getType($object);
		if(in_array($type, array('resource','null','unknown'))){
			throw new DAOException(" arguments type error ");
		}

		if($type==='object'){
			if($object){
				$sql=$this->iteratePropertyReplaceByClass($sql, $object);
				$properties=$this->getClassProperties($object);
				$params=array();
				if($properties){
					foreach ($properties as $key=>$value){
						if(stristr($sql, ":".$value)){
							$getMethod='get'.ucfirst($value);
							if($object->$getMethod()===0 || $object->$getMethod()){
								$params[$value]=$object->$getMethod();
							}else{
								throw new DAOException($value." has not value bind！ in sql:".$sql);
							}
						}
					}
				}
			}
		}else if($type==='array'){
			$sql=$this->iteratePropertyReplaceByArray($sql, $object);
			if($object){
				$params=$object;
				foreach ($object as $key=>$value){
					if(!stristr($sql, ":".$key)){
						throw new DAOException(" array key: $key not in sql:".$sql);
					}
				}
			}
		}else{
			$sql=$this->iteratePropertyReplaceByPrimitive($sql, $object);
			if($object){
				if($type==='boolean'){
					$object=intval($object);
				}
				$params['str']=$object;
			}
		}
		return array("sql"=>$sql,"params"=>$params);
	}

	private function getType($var){
		if(is_object($var)){
			return "object";
		}
		if(is_null($var)){
			return 'null';
		}
		if(is_string($var)){
			return 'string';
		}
		if(is_array($var)){
			return 'array';
		}
		if(is_int($var)){
			return 'integer';
		}
		if(is_bool($var)){
			return 'boolean';
		}
		if(is_float($var)){
			return 'float';
		}
		if(is_resource($var)){
			return 'resource';
		}
		return 'unknown';
	}

	protected function isNotNull($str){
		if($str!=null && $str!="" && trim($str)!="" ){
			return true;
		}
		return false;
	}

	private function getClassProperties($object){
		if($object){
			$reflector = new ReflectionClass($object);
			$properties=$reflector->getProperties();
			$newProperties=array();
			if($properties){
				foreach ($properties as $v){
					$newProperties[]=$v->name;
				}
			}
			return $newProperties;
		}
		return null;
	}

	private function iteratePropertyReplaceByClass($sql,$object){
		$newProperties=$this->getClassProperties($object);
		if($newProperties){
			foreach ($newProperties as $value){
				if(stristr($sql, $value)){
					$sql=str_ireplace("#$value#", ":$value", $sql);
				}
			}
		}
		return $sql;
	}

	private function iteratePropertyReplaceByArray($sql,$array){
		if($array){
			foreach ($array as $key=>$value){
				if(stristr($sql, $key)){
					$sql=str_ireplace("#$key#", ":$key", $sql);
				}
			}
		}
		return $sql;
	}

	private function iteratePropertyReplaceByPrimitive($sql,$primitive){
		if($primitive){
			$start=strpos($sql,"#");
			$end=strrpos($sql,"#");
			if($start && $end){
				$key=substr($sql, $start,$end);
			}else{
				throw new Exception("has not # or # not match in ".$sql);
			}
			$sql=str_ireplace("$key", ":str", $sql);
		}
		return $sql;
	}

	private function setClassAll($arrayList){
		if($arrayList){
			$resultMap=$this->getResultMap();
			$className=$resultMap['class'];
			$result=array();
			foreach ($arrayList as $row){
				$domain=new $className();
				foreach ($row as $col=>$value){
					foreach ($resultMap as $k=>$v){
						if($col === strtolower($k) && $k!=='class'){
							$setMethod="set".ucfirst($v);
							$domain->$setMethod($value);
							break;
						}
					}
				}
				$result[]=$domain;
			}
			return $result;
		}
		return array();
	}

	private function setClassOne($row){
		$resultMap=$this->getResultMap();
		$className=$resultMap['class'];
		if($row){
			$domain=new $className();
			foreach ($row as $col=>$value){
				foreach ($resultMap as $k=>$v){
					if($col === strtolower($k) && $k!=='class'){
						$setMethod="set".ucfirst($v);
						$domain->$setMethod($value);
						break;
					}
				}
			}
			return $domain;
		}
		return new $className();
	}

	private function getResultMap(){
		return $this->resultMap;
	}

	public function setResultMap(array $resultMap=array()){
		$this->resultMap = $resultMap;
	}

}

class DAOException extends Exception{
	

}
