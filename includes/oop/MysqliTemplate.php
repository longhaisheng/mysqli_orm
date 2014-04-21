<?php
abstract class MysqliTemplate implements ITemplate {

	/** key为表中的列名，value为class对象中的属性，必须包含一个key为class的项,(key不区分大小写，value 区分大小写，见意key也小写)如：
	 array("brand_id"=>'id',"BRAND_NAME"=>'brandName',"brand_logo"=>'brandLogo','class'=>'BrandDO');
	 */
	private $resultMap=array();

	/** 数据库连接*/
	public $db;

	private static $_models = array();

	function __construct() {
		global $mysqli;
		$this->db = $mysqli;
	}

	public static function model($className = __CLASS__) {
		if (isset(self::$_models[$className])){
			return self::$_models[$className];
		}else {
			$model = self::$_models[$className] = new $className(null);
			return $model;
		}
	}

	public function save($sql,$object){
		$sqlMap=$this->derectorDataObject($sql,$object);
		return $this->db->insert($sqlMap->getSql(),$sqlMap->getParams());
	}

	public function update($sql,$object){
		$sqlMap=$this->derectorDataObject($sql,$object);
		return $this->db->update($sqlMap->getSql(),$sqlMap->getParams());
	}
	
	public function delete($sql,$object){
		return $this->update($sql, $object);
	}

	public function queryForList($sql,$object=''){
		$sqlMap=$this->derectorDataObject($sql,$object);
		$arrayList= $this->db->getAll($sqlMap->getSql(),$sqlMap->getParams());
		return $this->setClassAll($arrayList);
	}

	public function queryForObject($sql,$object=''){
		$sqlMap=$this->derectorDataObject($sql,$object);
		$row= $this->db->getRow($sqlMap->getSql(),$sqlMap->getParams());
		return $this->setClassOne($row);
	}

	public function getColumn($sql,$object=''){
		$sqlMap=$this->derectorDataObject($sql,$object);
		return $this->db->getOne($sqlMap->getSql(),$sqlMap->getParams());
	}
	
	public function batchExecute($sql,$list=array()){
		if(empty($list)) return 0;
		$params=array();
		$sqlMap=null;
		foreach ($list as $obj){
			$sqlMap=$this->derectorDataObject($sql,$obj);
			$params[]=$sqlMap->getParams();
		}
		return $this->db->batchExecutes($sqlMap->getSql(),$params);
	}
	
	public function beginTransaction() {
		$this->db->begin();
	}

	public function commitTransaction() {
		$this->db->commit();
	}

	public function rollBack() {
		$this->db->rollback();
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
				$matchSql=$this->iteratePropertyReplaceByClass($sql, $object);
				$sql=$matchSql->getSql();
				$map=$matchSql->getMatchProperty();
				$properties=$this->getClassProperties($object);
				$params=array();
				if($properties){
					foreach ($properties as $key=>$value){
						if(stristr($sql, ":".$value)){
							$getMethod='get'.ucfirst($value);
							if($object->$getMethod()===0 || $object->$getMethod()){
								$sql=str_ireplace(":".$value, "?", $sql);
								foreach ($map as $k=>$v){
									if($v === "#$value#"){
										$params[$k]=$object->$getMethod();
										break;
									}
								}
							}else{
								throw new DAOException($value." has not value bind！ in sql:".$sql);
							}
						}
					}
				}
			}
		}else if($type==='array'){
			$matchSql=$this->iteratePropertyReplaceByArray($sql, $object);
			$sql=$matchSql->getSql();
			$map=$matchSql->getMatchProperty();
			$params=array();
			if($object){
				foreach ($object as $key=>$value){
					if(!stristr($sql, ":".$key)){
						throw new DAOException(" array key: $key not in sql:".$sql);
					}else{
						$sql=str_ireplace(":".$key, "?", $sql);
						foreach ($map as $k=>$v){
							if(strtolower($v) === strtolower("#$key#")){
								$params[$k]=$value;
								break;
							}
						}
					}
				}
			}
		}else{
			$sql=$this->iteratePropertyReplaceByPrimitive($sql, $object);
			$params=array();
			if($object){
				if($type==='boolean'){
					$object=intval($object);
				}
				$params[]=$object;
			}
		}

		$sqlMap=new SqlMap();
		$sqlMap->setSql($sql);
		$sqlMap->setParams($params);

		return $sqlMap;
	}

	protected function isNotNull($str){
		if($str!=null && $str!="" && trim($str)!="" ){
			return true;
		}
		return false;
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
		preg_match_all("/(#)(.*?)(#)/", $sql, $match);
		if($match){
			$match=$match[0];
		}
		$matchSql=new MatchSql();
		$matchSql->setMatchProperty($match);
		$newProperties=$this->getClassProperties($object);
		if($newProperties){
			foreach ($newProperties as $value){
				if(stristr($sql, $value)){
					$sql=str_ireplace("#$value#", ":$value", $sql);
				}
			}
		}
		$matchSql->setSql($sql);
		return $matchSql;
	}

	private function iteratePropertyReplaceByArray($sql,$array){
		preg_match_all("/(#)(.*?)(#)/", $sql, $match);
		if($match){
			$match=$match[0];
		}
		$matchSql=new MatchSql();
		$matchSql->setMatchProperty($match);
		if($array){
			foreach ($array as $key=>$value){
				if(stristr($sql, $key)){
					$sql=str_ireplace("#$key#", ":$key", $sql);
				}
			}
		}
		$matchSql->setSql($sql);
		return $matchSql;
	}

	private function iteratePropertyReplaceByPrimitive($sql,$primitive){
		if($primitive){
			$start=strpos($sql,"#");
			$end=strrpos($sql,"#");
			if($start && $end){
				$key=substr($sql, $start,$end);
			}else{
				throw new DAOException("has not # or # not match in ".$sql);
			}
			$sql=str_ireplace("$key", "?", $sql);
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

class MatchSql {

	/** sql中匹配"/(#)(.*?)(#)/"的数组  */
	private $matchProperty;

	/** sql语句 */
	private $sql;

	public function setMatchProperty(array $matchProperty=array()) {
		$this->matchProperty = $matchProperty;
	}

	public function getMatchProperty() {
		return $this->matchProperty;
	}

	public function setSql($sql) {
		$this->sql = $sql;
	}

	public function getSql() {
		return $this->sql;
	}

}

class SqlMap{

	/** sql中绑定?号的数组 */
	private $params;

	/** sql语句 */
	private $sql;

	public function setParams(array $params=array()) {
		$this->params = $params;
	}

	public function getParams() {
		return $this->params;
	}

	public function setSql($sql) {
		$this->sql = $sql;
	}

	public function getSql() {
		return $this->sql;
	}

}

class DAOException extends Exception{

}
