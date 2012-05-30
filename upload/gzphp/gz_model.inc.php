<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/

class GZ_MODEL
{
	public $prefix;
	public $setting;	
	private $_primaryKey;
	private $_tableName;
	
	public function __construct()
	{
		$this->prefix = GZ_APP::config()->get('database')->prefix;
		
		$this->setting = (array)GZ_APP::config()->get("setting");
		
		$this->setup();
	}
	
	public function setup()
	{}
	
	public function model($model)
	{		
		return GZ_BASE::model($model);
	}
	
	// 获取表前缀
	public function get_prefix()
	{
		return $this->prefix;
	}
	
	// 获取表名 (直接写 SQL 的时候要用这个函数, 外部程序使用 get_table() 方法)
	public function get_table($name)
	{
		return $this->get_prefix() . $name;
	}
	
	// db 方法
	public function db()
	{
		return GZ_APP::db();
	}
	
	// 切换到主数据库
	public function master()
	{
		GZ_APP::db('master');
		
		if (GZ_APP::config()->get('system')->debug)
		{
			GZ_APP::debug_log('database', 0, 'Master DB Seleted');
		}
		
		return $this;
	}
	
	// 切换到次数据库
	public function slave()
	{
		GZ_APP::db('slave');
		
		if (GZ_APP::config()->get('system')->debug)
		{
			GZ_APP::debug_log('database', 0, 'Slave DB Seleted');
		}
		
		return $this;
	}
	
	// 插入数据
	public function insert($table, $data)
	{
		if (GZ_APP::config()->get('system')->debug)
		{
			foreach ($data AS $key => $val)
			{
				$debug_data['`' . $key . '`'] = "'" . $val . "'";
			}
			
			$sql = 'INSERT INTO `' . $this->get_table($table) . '` (' . implode(', ', array_flip($debug_data)) . ') VALUES (' . implode(', ', $debug_data) . ')';
			
		
			try {
				$rows_affected = $this->db()->insert($this->get_table($table), $data);
			} catch (Exception $e) {
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$rows_affected = $this->db()->insert($this->get_table($table), $data);
		}

		
		$last_insert_id = $this->db()->lastInsertId();

		return $last_insert_id;
	}

	// 更新数据
	public function update($table, $data, $where = '')
	{
		if (!$where)
		{
			show_error('DB Update no where string.');
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			foreach ($data AS $key => $val)
			{
				$update_string[] = '`' . $key . "` = '" . $val . "'";
			}
			
			$sql = 'UPDATE `' . $this->get_table($table) . '` SET ' . implode(', ', $update_string) . ' WHERE ' . $where;
			
			try {
				$rows_affected = $this->db()->update($this->get_table($table), $data, $where);
			} catch (Exception $e) {
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$rows_affected = $this->db()->update($this->get_table($table), $data, $where);
		}
		
		
		return $rows_affected;
	}
	
	// 删除数据
	public function delete($table, $where = '')
	{
		if (!$where)
		{
			throw new Exception('DB Delete no where string.');
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{			
			$sql = 'DELETE FROM `' . $this->get_table($table) . '` WHERE ' . $where;
			
			try {
				$rows_affected = $this->db()->delete($this->get_table($table), $where);
			} catch (Exception $e) {
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$rows_affected = $this->db()->delete($this->get_table($table), $where);
		}
		
		return $rows_affected;
	}
	
	// Zend Select 对象别名
	public function select()
	{
		return $this->db()->select();
	}
	
	// 获取查询全部数据, 与原来的 get_list 一致
	public function fetch_all($table, $where = null, $order = null, $limit = null, $offset = 0)
	{
		$select = $this->select();
		
		$select->from($this->get_table($table), '*');

		if ($where)
		{
			$select->where($where);
		}
		
		if ($order)
		{
			if (strstr($order, ','))
			{
				$all_order = explode(',', $order);
				
				foreach ($all_order AS $current_order)
				{
					$select->order($current_order);	
				}
			}
			else
			{
				$select->order($order);	
			}
		}
		
		if ($limit)
		{
			if (strstr($limit, ','))
			{
				$limit = explode(',', $limit);
				
				$select->limit(intval($limit[1]), intval($limit[0]));
			}
			else if ($offset)
			{
				$select->limit($limit, $offset);
			}
			else
			{
				$select->limit($limit);
			}
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchAll($select);
			} catch (Exception $e) {
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchAll($select);
		}
		
		
		return $result;
	}
	
	// SQL 直查
	public function query($sql, $limit = null, $offset = null, $where = null)
	{		
		if (!$sql)
		{
			throw new Exception('Query was empty.');
		}
		
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}
		
		if ($limit)
		{
			$sql .= ' LIMIT ' . $limit;
		}
		
		if ($offset)
		{
			$sql .= ' OFFSET ' . $limit;
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			try {
				$result = $this->db()->query($sql);
			} catch (Exception $e) {
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->query($sql);
		}
		
		return $result;
	}
	
	// 查询一行, 返回组数, key 为 字段名
	public function query_row($sql, $where = null)
	{		
		if (!$sql)
		{
			throw new Exception('Query was empty.');
		}
		
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			try {
				$result = $this->db()->fetchRow($sql);
			} catch (Exception $e) {
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchRow($sql);
		}
		
		return $result;
	}
	
	// 获取查询全部数据, 与原来的 get_list 一致
	public function query_all($sql, $limit = null, $offset = null, $where = null)
	{
		if (!$sql)
		{
			throw new Exception('Query was empty.');
		}
		
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}
		
		if ($limit)
		{
			$sql .= ' LIMIT ' . $limit;
		}
		
		if ($offset)
		{
			$sql .= ' OFFSET ' . $limit;
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			try {
				$result = $this->db()->fetchAll($sql);
			} catch (Exception $e) {
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchAll($sql);
		}
		
		return $result;
	}
	
	public function found_rows()
	{		
		return $this->db()->fetchOne('SELECT FOUND_ROWS()');
	}
	
	// 带页码的 fetch_all, 默认从第一页开始
	public function fetch_page($table, $where = null, $order = null, $page = null, $limit = 10)
	{
		$select = $this->select();
		
		$select->from($this->get_table($table), array(new Zend_Db_Expr('SQL_CALC_FOUND_ROWS *')));

		if ($where)
		{
			$select->where($where);
		}
		
		if ($order)
		{
			if (strstr($order, ','))
			{
				$all_order = explode(',', $order);
				
				foreach ($all_order AS $current_order)
				{
					$select->order($current_order);	
				}
			}
			else
			{
				$select->order($order);	
			}
		}
		
		if ($limit)
		{
			$select->limitPage($page, $limit);
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchAll($select);
			} catch (Exception $e) {
				
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchAll($select);
		}
		
		return $result;
	}
	
	// query_row 的面向对象方法
	public function fetch_row($table, $where = null, $order = null)
	{
		$select = $this->select();
		
		$select->from($this->get_table($table), '*');

		if ($where)
		{
			$select->where($where);
		}
		
		if ($order)
		{
			if (strstr($order, ','))
			{
				$all_order = explode(',', $order);
				
				foreach ($all_order AS $current_order)
				{
					$select->order($current_order);	
				}
			}
			else
			{
				$select->order($order);	
			}
		}
		
		$select->limit(1, 0);
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchRow($select);
			} catch (Exception $e) {
				
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchRow($select);
		}
		
		return $result;
	}
	
	// 查询单字段
	public function fetch_one($table, $column, $where = null)
	{
		$select = $this->select();
		
		$select->from($this->get_table($table), $column);

		if ($where)
		{
			$select->where($where);
		}
		
		$select->limit(1, 0);
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchOne($select);
			} catch (Exception $e) {
				
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchOne($select);
		}
		
		return $result;
	}
	
	// 查询一行, id 为主键
	public function get($table, $id)
	{
		if (!$id)
		{
			return null;
		}

		$select = $this->select();
		$select->from($this->get_table($table), '*');
		$select->where($this->get_primary_key($table) . ' = ' . (int)$id)->limit(1, 0);
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchRow($select);
			} catch (Exception $e) {
				
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchRow($select);
		}
		
		return $result;
	}
	
	// 计数
	public function count($table, $where = '')
	{
		$select = $this->select();
		$select->from($this->get_table($table), 'COUNT(*) AS n');
		
		if ($where)
		{
			$select->where($where);
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchRow($select);
			} catch (Exception $e) {
				
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchRow($select);
		}
		
		return $result['n'];
	}
	
	// 计算字段最大值
	public function max($table, $column, $where = '')
	{
		$select = $this->select();
		$select->from($this->get_table($table), 'MAX(' . $column . ') AS n');
		
		if ($where)
		{
			$select->where($where);
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchRow($select);
			} catch (Exception $e) {
				
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchRow($select);
		}
		
		return $result['n'];
	}
	
	// 计算字段最小值
	public function min($table, $column, $where = '')
	{
		$select = $this->select();
		$select->from($this->get_table($table), 'MIN(' . $column . ') AS n');
		
		if ($where)
		{
			$select->where($where);
		}
		
		$row = $this->db()->fetchRow($select);
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchRow($select);
			} catch (Exception $e) {
				
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchRow($select);
		}
		
		return $result['n'];
	}
	
	// 计算总数
	public function sum($table, $column, $where = '')
	{
		$select = $this->select();
		$select->from($this->get_table($table), 'SUM(' . $column . ') AS n');
		
		if ($where)
		{
			$select->where($where);
		}
		
		if (GZ_APP::config()->get('system')->debug)
		{
			$sql = $select->__toString();
			
			try {
				$result = $this->db()->fetchRow($select);
			} catch (Exception $e) {
				
				show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
			}
			
			GZ_APP::debug_log('database', 0, $sql);
		}
		else
		{
			$result = $this->db()->fetchRow($select);
		}
		
		return intval($result['n']);
	}
	
	// 添加引号防止数据库攻击
	public static function quote($string)
	{
		//return $this->db()->quote($string);
		return mysql_escape_string($string);
	}
	
	private function get_primary_key($table)
	{
		if ($this->_primaryKey[$table])
		{
			return $this->_primaryKey[$table];
		}
		
		$r = $this->query('DESCRIBE ' . $this->get_table($table));

		while ($row = mysqli_fetch_array($r))
		{
			if ($row['Key'] == 'PRI')
			{
				$this->_primaryKey[$table] = $row['Field'];
				
				return $row['Field'];
			}
		}
		
		show_error($this->get_table($table) . ' primaryKey does not exist ..');
	}

	public function data_cache($key, $expression, $life_time, $group_name = null)
	{
		if (! is_string($expression))
		{
			show_error('data_cache(): ' . $key . ' expression is not string val');
		}
		
		$data = ZCACHE::get($key);
		
		if ($data === false)
		{
			
			$data = eval('return ' . $expression . ';');
			ZCACHE::set($key, $data, $group_name, $life_time);
		}
		
		return $data;
	}
	
}