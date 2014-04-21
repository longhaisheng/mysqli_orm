<?php
interface ITemplate{

	/**
	 * 保存数据
	 * @param string $sql
	 * @param Object Class or Array $object
	 * @return 主键
	 */
	public function save($sql,$object);

	/**
	 * 更新数据
	 * @param string $sql
	 * @param Object Class or Array $object
	 * @return 受影响行数
	 */
	public function update($sql,$object);
	
	/**
	 * 删除数据
	 * @param string $sql
	 * @param Object Class or Array $object
	 * @return 受影响行数
	 */
	public function delete($sql,$object);

	/**
	 * 根据$object查询数据
	 * @param string $sql
	 * @param Object Class or Array $object
	 * @return 数据列表(列表中项为对象)
	 */
	public function queryForList($sql,$object='');

	/**
	 * 返回一个对象
	 * @param string $sql
	 * @param Object Class or Array $object
	 */
	public function queryForObject($sql,$object='');

	/**
	 * 取第一行第一列的值
	 * @param string $sql
	 * @param Object Class or Array $object
	 * @return 返回第一行第一列的值
	 */
	public function getColumn($sql,$object='');
	
	/**
	 * 批量更新或删除
	 * @param string $sql
	 * @param Array $object 
	 * @return 返回第一行第一列的值
	 */
	public function batchExecute($sql,$array=array());
	
	/**
	 * 开启事务
	 */
	public function beginTransaction();

	/**
	 * 提交事务
	 */
	public function commitTransaction();
	
	/**
	 * 回滚事务
	 */
	public function rollBack();



}