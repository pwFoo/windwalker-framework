<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Database\Driver\Postgresql;

use Windwalker\Database\Command\AbstractTable;
use Windwalker\Database\DatabaseHelper;
use Windwalker\Database\Schema\Column;
use Windwalker\Database\Schema\Key;
use Windwalker\Database\Schema\Schema;
use Windwalker\Query\Mysql\MysqlQueryBuilder;
use Windwalker\Query\Postgresql\PostgresqlQueryBuilder;

/**
 * Class PostgresqlTable
 *
 * @since 2.0
 */
class PostgresqlTable extends AbstractTable
{
	/**
	 * create
	 *
	 * @param bool  $ifNotExists
	 * @param array $options
	 *
	 * @return  static
	 */
	public function create($schema, $ifNotExists = true, $options = array())
	{
		$defaultOptions = array(
			'auto_increment' => 1,
			'sequences' => array()
		);

		$options  = array_merge($defaultOptions, $options);
		$schema   = $this->callSchema($schema);
		$columns  = array();
		$comments = array();
		$primary  = array();

		foreach ($schema->getColumns() as $column)
		{
			$column = $this->prepareColumn($column);

			$columns[$column->getName()] = PostgresqlQueryBuilder::build(
				$column->getType() . $column->getLength(),
				$column->getAllowNull() ? null : 'NOT NULL',
				$column->getDefault() ? 'DEFAULT ' . $this->db->quote($column->getDefault()) : null
			);

			// Comment
			if ($column->getComment())
			{
				$comments[$column->getName()] = $column->getComment();
			}

			// Primary
			if ($column->isPrimary())
			{
				$primary[] = $column->getName();
			}
		}

		$keys = array();
		$keyComments = array();

		foreach ($schema->getIndexes() as $index)
		{
			$keys[$index->getName()] = array(
				'type' => strtoupper($index->getType()),
				'name' => $index->getName(),
				'columns' => $index->getColumns()
			);

			if ($index->getComment())
			{
				$keyComments[$index->getName()] = $index->getComment();
			}
		}

		$options['comments'] = $comments;
		$options['key_comments'] = $keyComments;

		$inherits = isset($options['inherits']) ? $options['inherits'] : null;
		$tablespace = isset($options['tablespace']) ? $options['tablespace'] : null;

		$query = PostgresqlQueryBuilder::createTable($this->getName(), $columns, $primary, $keys, $inherits, $ifNotExists, $tablespace);

		$comments = isset($options['comments']) ? $options['comments'] : array();
		$keyComments = isset($options['key_comments']) ? $options['key_comments'] : array();

		// Comments
		foreach ($comments as $name => $comment)
		{
			$query .= ";\n" . PostgresqlQueryBuilder::comment('COLUMN', $this->getName(), $name, $comment);
		}

		foreach ($keyComments as $name => $comment)
		{
			$query .= ";\n" . PostgresqlQueryBuilder::comment('INDEX', 'public', $name, $comment);
		}

		DatabaseHelper::batchQuery($this->db, $query);

		return $this->reset();
	}

	/**
	 * addColumn
	 *
	 * @param string $name
	 * @param string $type
	 * @param bool   $signed
	 * @param bool   $allowNull
	 * @param string $default
	 * @param string $comment
	 * @param array  $options
	 *
	 * @return  static
	 */
	public function addColumn($name, $type = 'text', $signed = true, $allowNull = true, $default = '', $comment = '', $options = array())
	{
		$column = $name;

		if (!($column instanceof Column))
		{
			$column = new Column($name, $type, $signed, $allowNull, $default, $comment, $options);
		}

		$this->prepareColumn($column);

		$query = PostgresqlQueryBuilder::addColumn(
			$this->getName(),
			$column->getName(),
			$column->getType() . $column->getLength(),
			$column->getAllowNull(),
			$column->getDefault()
		);

		$this->db->setQuery($query)->execute();

		if ($column->getComment())
		{
			$query = PostgresqlQueryBuilder::comment('COLUMN', $this->getName(), $column->getName(), $column->getComment());
			$this->db->setQuery($query)->execute();
		}

		return $this->reset();
	}

	/**
	 * prepareColumn
	 *
	 * @param Column $column
	 *
	 * @return  Column
	 */
	protected function prepareColumn(Column $column)
	{
		/** @var PostgresqlType $typeMapper */
		$typeMapper = $this->getTypeMapper();

		$length = $typeMapper::noLength($column->getType()) ? null : $column->getLength();

		$column->length($length);

		if ($column->getAutoIncrement())
		{
			$column->type(PostgresqlType::SERIAL);
			$options['sequences'][$column->getName()] = $this->getName() . '_' . $column->getName() . '_seq';
		}

		return parent::prepareColumn($column);
	}

	/**
	 * modifyColumn
	 *
	 * @param string|Column $name
	 * @param string $type
	 * @param bool   $signed
	 * @param bool   $allowNull
	 * @param string $default
	 * @param string $comment
	 * @param array  $options
	 *
	 * @return  static
	 */
	public function modifyColumn($name, $type = 'text', $signed = true, $allowNull = true, $default = '', $comment = '', $options = array())
	{
		$column = $name;

		if ($column instanceof Column)
		{
			$name      = $column->getName();
			$type      = $column->getType();
			$length    = $column->getLength();
			$allowNull = $column->getAllowNull();
			$default   = $column->getDefault();
			$comment   = $column->getComment();
		}

		$type   = PostgresqlType::getType($type);
		$length = isset($length) ? $length : PostgresqlType::getLength($type);
		$length = PostgresqlType::noLength($type) ? null : $length;
		$length = $length ? '(' . $length . ')' : null;

		$query = $this->db->getQuery(true);

		// Type
		$sql = PostgresqlQueryBuilder::build(
			'ALTER TABLE ' . $query->quoteName($this->getName()),
			'ALTER COLUMN',
			$query->quoteName($name),
			'TYPE',
			$type . $length,
			$this->usingTextToNumeric($name, $type)
		);

		$sql .= ";\n" . PostgresqlQueryBuilder::build(
			'ALTER TABLE ' . $query->quoteName($this->getName()),
			'ALTER COLUMN',
			$query->quoteName($name),
			$allowNull ? 'DROP' : 'SET',
			'NOT NULL'
		);

		if (!is_null($default))
		{
			$sql .= ";\n" . PostgresqlQueryBuilder::build(
				'ALTER TABLE ' . $query->quoteName($this->getName()),
				'ALTER COLUMN',
				$query->quoteName($name),
				'SET DEFAULT' . $query->quote($default)
			);
		}

		$sql .= ";\n" . PostgresqlQueryBuilder::comment(
			'COLUMN',
			$this->getName(),
			$name,
			$comment
		);

		DatabaseHelper::batchQuery($this->db, $sql);

		return $this->reset();
	}

	/**
	 * changeColumn
	 *
	 * @param string $oldName
	 * @param string|Column  $newName
	 * @param string $type
	 * @param bool   $signed
	 * @param bool   $allowNull
	 * @param string $default
	 * @param string $comment
	 * @param array  $options
	 *
	 * @return  static
	 */
	public function changeColumn($oldName, $newName, $type = 'text', $signed = true, $allowNull = true, $default = '', $comment = '', $options = array())
	{
		$column = $name = $newName;

		if ($column instanceof Column)
		{
			$name      = $column->getName();
			$type      = $column->getType();
			$length    = $column->getLength();
			$allowNull = $column->getAllowNull();
			$default   = $column->getDefault();
			$comment   = $column->getComment();
		}

		$type   = PostgresqlType::getType($type);
		$length = isset($length) ? $length : PostgresqlType::getLength($type);
		$length = PostgresqlType::noLength($type) ? null : $length;
		$length = $length ? '(' . $length . ')' : null;

		$query = $this->db->getQuery(true);

		// Type
		$sql = PostgresqlQueryBuilder::build(
			'ALTER TABLE ' . $query->quoteName($this->getName()),
			'ALTER COLUMN',
			$query->quoteName($oldName),
			'TYPE',
			$type . $length,
			$this->usingTextToNumeric($oldName, $type)
		);

		// Not NULL
		$sql .= ";\n" . PostgresqlQueryBuilder::build(
			'ALTER TABLE ' . $query->quoteName($this->getName()),
			'ALTER COLUMN',
			$query->quoteName($oldName),
			$allowNull ? 'DROP' : 'SET',
			'NOT NULL'
		);

		// Default
		if (!is_null($default))
		{
			$sql .= ";\n" . PostgresqlQueryBuilder::build(
				'ALTER TABLE ' . $query->quoteName($this->getName()),
				'ALTER COLUMN',
				$query->quoteName($oldName),
				'SET DEFAULT' . $query->quote($default)
			);
		}

		// Comment
		$sql .= ";\n" . PostgresqlQueryBuilder::comment(
			'COLUMN',
			$this->getName(),
			$oldName,
			$comment
		);

		// Rename
		$sql .= ";\n" . PostgresqlQueryBuilder::renameColumn(
			$this->getName(),
			$oldName,
			$name
		);

		DatabaseHelper::batchQuery($this->db, $sql);

		return $this->reset();
	}

	/**
	 * usingTextToNumeric
	 *
	 * @param   string  $column
	 * @param   string  $type
	 *
	 * @return  string
	 */
	protected function usingTextToNumeric($column, $type)
	{
		$type = strtolower($type);

		$details = $this->getColumnDetail($column);

		list($originType) = explode('(', $details->Type);

		$textTypes = array(
			PostgresqlType::TEXT,
			PostgresqlType::CHAR,
			PostgresqlType::CHARACTER,
			PostgresqlType::VARCHAR
		);

		$numericTypes = array(
			PostgresqlType::INTEGER,
			PostgresqlType::SMALLINT,
			PostgresqlType::FLOAT,
			PostgresqlType::DOUBLE,
			PostgresqlType::DECIMAL
		);

		if (in_array($originType, $textTypes) && in_array($type, $numericTypes))
		{
			return sprintf('USING trim(%s)::%s', $this->db->quoteName($column), $type);
		}

		return null;
	}

	/**
	 * addIndex
	 *
	 * @param string       $type
	 * @param array|string $columns
	 * @param string       $name
	 * @param string       $comment
	 * @param array        $options
	 *
	 * @return mixed
	 */
	public function addIndex($type, $columns = array(), $name = null, $comment = null, $options = array())
	{
		if (!$type instanceof Key)
		{
			if (!$columns)
			{
				throw new \InvalidArgumentException('No columns given.');
			}

			$columns = (array) $columns;

			$index = new Key($type, $columns, $name, $comment);
		}
		else
		{
			$index = $type;
		}

		if ($this->hasIndex($index->getName()))
		{
			return $this;
		}

		$query = PostgresqlQueryBuilder::addIndex(
			$this->getName(), 
			$index->getType(), 
			$index->getColumns(), 
			$index->getName()
		);

		$this->db->setQuery($query)->execute();

		if ($index->getComment())
		{
			$query = PostgresqlQueryBuilder::comment('INDEX', 'public', $index->getName(), $index->getComment());

			$this->db->setQuery($query)->execute();
		}

		return $this->reset();
	}

	/**
	 * dropIndex
	 *
	 * @param string $name
	 * @param bool   $constraint
	 *
	 * @return static
	 */
	public function dropIndex($name, $constraint = false)
	{
		if (!$constraint && !$this->hasIndex($name))
		{
			return $this;
		}

		if ($constraint)
		{
			$query = PostgresqlQueryBuilder::dropConstraint($this->getName(), $name, true, 'RESTRICT');
		}
		else
		{
			$query = PostgresqlQueryBuilder::dropIndex($name, true);
		}

		$this->db->setQuery($query)->execute();

		return $this->reset();
	}

	/**
	 * rename
	 *
	 * @param string  $newName
	 * @param boolean $returnNew
	 *
	 * @return  $this
	 */
	public function rename($newName, $returnNew = true)
	{
		$this->db->setQuery(PostgresqlQueryBuilder::build(
			'ALTER TABLE',
			$this->db->quoteName($this->getName()),
			'RENAME TO',
			$this->db->quoteName($newName)
		));

		$this->db->execute();

		if ($returnNew)
		{
			return $this->db->getTable($newName);
		}

		return $this;
	}

	/**
	 * getColumnDetails
	 *
	 * @param bool $refresh
	 *
	 * @return mixed
	 */
	public function getColumnDetails($refresh = false)
	{
		if (empty($this->columnCache) || $refresh)
		{
			$query = PostgresqlQueryBuilder::showTableColumns($this->db->replacePrefix($this->getName()));

			$fields = $this->db->setQuery($query)->loadAll();

			$result = array();

			foreach ($fields as $field)
			{
				// Do some dirty translation to MySQL output.
				$result[$field->column_name] = (object) array(
					'column_name' => $field->column_name,
					'type'        => $field->column_type,
					'null'        => $field->Null,
					'Default'     => $field->Default,
					'Field'       => $field->column_name,
					'Type'        => $field->column_type,
					'Null'        => $field->Null,
					'Extra'       => null,
					'Privileges'  => null,
					'Comment'     => $field->Comment
				);
			}

			$keys = $this->getIndexes();

			foreach ($result as $field)
			{
				if (preg_match("/^NULL::*/", $field->Default))
				{
					$field->Default = null;
				}

				if (strpos($field->Type, 'character varying') !== false)
				{
					$field->Type = str_replace('character varying', 'varchar', $field->Type);
				}

				if (strpos($field->Default, 'nextval') !== false)
				{
					$field->Extra = 'auto_increment';
				}

				// Find key
				$index = null;

				foreach ($keys as $key)
				{
					if ($key->column_name == $field->column_name)
					{
						$index = $key;
						break;
					}
				}

				if ($index)
				{
					if ($index->is_primary)
					{
						$field->Key = 'PRI';
					}
					elseif ($index->is_unique)
					{
						$field->Key = 'UNI';
					}
					else
					{
						$field->Key = 'MUL';
					}
				}
			}
			
			$this->columnCache = $result;
		}

		return $this->columnCache;
	}

	/**
	 * getIndexes
	 *
	 * @return  mixed
	 */
	public function getIndexes()
	{
		$this->db->setQuery('
SELECT
	t.relname AS table_name,
	i.relname AS index_name,
	a.attname AS column_name,
	ix.indisunique AS is_unique,
	ix.indisprimary AS is_primary
FROM pg_class AS t,
	pg_class AS i,
	pg_index AS ix,
	pg_attribute AS a
WHERE t.oid = ix.indrelid
	AND i.oid = ix.indexrelid
	AND a.attrelid = t.oid
	AND a.attnum = ANY(ix.indkey)
	AND t.relkind = \'r\'
	AND t.relname = ' . $this->db->quote($this->db->replacePrefix($this->getName())) . '
ORDER BY t.relname, i.relname;');

		$keys = $this->db->loadAll();

		foreach ($keys as $key)
		{
			$key->Table = $this->getName();
			$key->Non_unique = !$key->is_unique;
			$key->Key_name = $key->index_name;
			$key->Column_name = $key->column_name;
			$key->Collation = 'A';
			$key->Cardinality = 0;
			$key->Sub_part = null;
			$key->Packed = null;
			$key->Null = null;
			$key->Index_type = 'BTREE';
			// TODO: Finish comments query
			$key->Comment = null;
			$key->Index_comment = null;
		}

		return $keys;
	}

	/**
	 * Get the details list of sequences for a table.
	 *
	 * @param   string  $table  The name of the table.
	 *
	 * @return  array  An array of sequences specification for the table.
	 *
	 * @since   2.1
	 * @throws  \RuntimeException
	 */
	public function getTableSequences($table)
	{
		// To check if table exists and prevent SQL injection
		$tableList = $this->db->getDatabase()->getTables();

		if ( in_array($table, $tableList) )
		{
			$name = array(
				's.relname AS sequence', 
				'n.nspname AS schema', 
				't.relname AS table', 
				'a.attname AS column', 
				'info.data_type AS data_type',
				'info.minimum_value AS minimum_value', 
				'info.maximum_value AS maximum_value', 
				'info.increment AS increment', 
				'info.cycle_option AS cycle_option'
			);

			if (version_compare($this->db->getVersion(), '9.1.0') >= 0)
			{
				$name[] .= 'info.start_value AS start_value';
			}

			// Get the details columns information.
			$query = $this->db->getQuery(true);

			$query->select($this->db->quoteName($name))
				->from('pg_class AS s')
				->leftJoin("pg_depend d ON d.objid=s.oid AND d.classid='pg_class'::regclass AND d.refclassid='pg_class'::regclass")
				->leftJoin('pg_class t ON t.oid=d.refobjid')
				->leftJoin('pg_namespace n ON n.oid=t.relnamespace')
				->leftJoin('pg_attribute a ON a.attrelid=t.oid AND a.attnum=d.refobjsubid')
				->leftJoin('information_schema.sequences AS info ON info.sequence_name=s.relname')
				->where("s.relkind='S' AND d.deptype='a' AND t.relname=" . $this->db->quote($table));

			$this->db->setQuery($query);

			$seq = $this->db->loadAll();

			return $seq;
		}

		return false;
	}
}