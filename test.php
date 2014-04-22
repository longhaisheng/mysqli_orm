<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/oop/ITemplate.php');
require(dirname(__FILE__) . '/includes/domain/UserDO.php');
require(dirname(__FILE__) . '/includes/oop/MysqliTemplate.php');

function getMilliseconds()
{ //取毫秒
    $time = explode(" ", microtime());
    $time = $time [1] *1000+($time [0] * 1000);
    $time2 = explode(".", $time);
    return $time2 [0];
}




$start= getMilliseconds();
$model= new UserModel();
$result=$model->getUsers();
print_r($result);
$end= getMilliseconds();
$time=$end-$start;
echo "consum time is $time<br>";

$params=array();
$user=new UserDO();
for($i=0;$i<10;$i++){
	$user->setPassWord("passWord".$i);
	$user->setIsDelete(0);
	$user->setUserName("userName".$i);
	$user->setAddressOne("addressOne".$i);
	$params[]=$user;
}
$count=$model->batchInsert($params);
echo "batch_insert is $count";

$user=new UserDO();
$user->setId(1);
echo "queryObject id is 1 <br>";
print_r($model->getOneUser($user));
echo "<br>+++++++++++++++++++++++++++<br>";

$user=new UserDO();
$user->setPassWord("passWord9");
$user->setIsDelete(0);
$user->setUserName("userName9");
$user->setAddressOne("addressOne9");
$insert_id=$model->insert($user);
echo "insert id is $insert_id ,+++++++++++++++++++++++++++<br>";


echo "<br>queryById id is 2 <br>";
print_r($model->getUserById(2));
echo "<br>+++++++++++++++++++++++++++<br>";

$user=new UserDO();
$user->setNewUserName("userName_new");
$user->setUserName("userName");
$result=$model->DynamicUpdate($user);
echo "<br>DynamicUpdate result:$result<br>";
echo "+++++++++++++++++++++++++++<br>";

$user=new UserDO();
$user->setId(2);
$user->setNewUserName("userName_new_1");
$result=$model->updateById($user);
echo "updateById is $result+++++++++++++++++++++++++++<br>";

echo "<br>query_with_array<br>";
print_r($model->getUsersWithArray(array('id'=>3,'pwd'=>'pwd')));
echo "<br>+++++++++++++++++++++++++++<br>";

$count=$model->getUserCount($user);
echo "getUserCount is $count";
echo "<br>+++++++++++++++++++++++++++<br>";

//print_r($model->getUsers());

//$field='key';
//${$field}="7777";
//echo $key;

?>