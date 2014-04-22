<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/oop/ITemplate.php');
require(dirname(__FILE__) . '/includes/domain/UserDO.php');
require(dirname(__FILE__) . '/includes/oop/MysqliTemplate.php');

//This an php orm for mysql, It use mysqli extend;

//Insert Object:
$model= new UserModel();
$user=new UserDO();
$user->setPassWord("passWord1");
$user->setIsDelete(0);
$user->setUserName("userName1");
$user->setAddressOne("addressOne1");
$insert_id=$model->insert($user);

//get Object:
$model= new UserModel();
$user=new UserDO();
$user->setId(1);
$return_user=$model->getOneUser($user);

//get Object by primary key:
$model= new UserModel();
$result=$model->getUserById(1)

//DynamicUpdate Object:
$model= new UserModel();
$queryDO=new UserDO();
$queryDO->setNewUserName("userName_new");
$queryDO->setUserName("userName");
$result=$model->DynamicUpdate($queryDO);

//update Object by primary key:
$model= new UserModel();
$queryDO=new UserDO();
$queryDO->setId(1);
$queryDO->setNewUserName("userName_new_1");
$result=$model->updateById($queryDO);

//query Object with array params:
$model= new UserModel();
$result=$model->getUsersWithArray(array('id'=>1,'pwd'=>'pwd'))

//get count with Object params:
$model= new UserModel();
$queryDO=new UserDO();
$queryDO->setId(1);
$count=$model->getUserCount($queryDO);

//batch insert Object:
$model= new UserModel();
$batch_params=array();
$userDO=new UserDO();
for($i=0;$i<10;$i++){
	$userDO->setPassWord("pass_word_".$i);
	$userDO->setIsDelete(0);
	$userDO->setUserName("userName_".$i);
	$userDO->setAddressOne("address_one_".$i);
	$batch_params[]=$userDO;
}
$count=$model->batchInsert($batch_params);

