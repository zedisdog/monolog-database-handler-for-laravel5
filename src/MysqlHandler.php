<?php
/**
 * Created by PhpStorm.
 * User: zed2
 * Date: 2016/4/17
 * Time: 0:07
 */

namespace zedisdog\MysqlHandler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use PDO;
use PDOStatement;

/**
 * This class is a handler for Monolog, which can be used
 * to write records in a MySQL table
 *
 * Class MySQLHandler
 * @package wazaari\MysqlHandler
 */
class MySQLHandler extends AbstractProcessingHandler
{

    /**
     * @var bool defines whether the MySQL connection is been initialized
     */
    private $initialized = false;

    /**
     * @var PDO pdo object of database connection
     */
    protected $pdo;

    /**
     * @var PDOStatement statement to insert a new record
     */
    private $statement;

    /**
     * @var string the table to store the logs in
     */
    private $table = 'logs';

    /**
     * @var string[] additional fields to be stored in the database
     *
     * For each field $field, an additional context field with the name $field
     * is expected along the message, and further the database needs to have these fields
     * as the values are stored in the column name $field.
     */
    private $additionalFields = array();

    /**
     * Constructor of this class, sets the PDO and calls parent constructor
     *
     * @param PDO $pdo PDO Connector for the database
     * @param bool $table Table in the database to store the logs in
     * @param array $additionalFields Additional Context Parameters to store in database
     * @param bool|int $level Debug level which this handler should store
     * @param bool $bubble
     */
    public function __construct(
        PDO $pdo = null,
        $table,
        $additionalFields = array(),
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        if (!is_null($pdo)) {
            $this->pdo = $pdo;
        }
        $this->table = $table;
        $this->additionalFields = $additionalFields;
        parent::__construct($level, $bubble);
    }

    /**
     * Initializes this handler by creating the table if it not exists
     */
    private function initialize()
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS `' . $this->table . '` '
            . '(channel VARCHAR(255), level INTEGER, message LONGTEXT, date_time DATETIME)'
        );

        //Read out actual columns
        $actualFields = array();
        $rs = $this->pdo->query('SELECT * FROM `' . $this->table . '` LIMIT 0');
        for ($i = 0; $i < $rs->columnCount(); $i++) {
            $col = $rs->getColumnMeta($i);
            $actualFields[] = $col['name'];
        }

        //Calculate changed entries
        $removedColumns = array_diff($actualFields, $this->additionalFields,
            array('channel', 'level', 'message', 'date_time'));
        $addedColumns = array_diff($this->additionalFields, $actualFields);

        //Remove columns
        if (!empty($removedColumns)) {
            foreach ($removedColumns as $c) {
                $this->pdo->exec('ALTER TABLE `' . $this->table . '` DROP `' . $c . '`;');
            }
        }

        //Add columns
        if (!empty($addedColumns)) {
            foreach ($addedColumns as $c) {
                $this->pdo->exec('ALTER TABLE `' . $this->table . '` add `' . $c . '` TEXT NULL DEFAULT NULL;');
            }
        }

        //Prepare statement
        $columns = "";
        $fields = "";
        foreach ($this->additionalFields as $f) {
            $columns .= ", $f";
            $fields .= ", :$f";
        }

        $this->statement = $this->pdo->prepare(
            'INSERT INTO `' . $this->table . '` (channel, level, message, date_time' . $columns . ') VALUES (:channel, :level, :message, :date_time' . $fields . ')'
        );

        $this->initialized = true;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  $record []
     * @return void
     */
    protected function write(array $record)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        //'context' contains the array
        $contentArray = array_merge(array(
            'channel' => $record['channel'],
            'level' => $record['level'],
            'message' => $record['message'],
            'date_time' => $record['datetime']->format('Y-m-d H:i:s')
        ), $record['context']);

        $this->statement->execute($contentArray);
    }
}