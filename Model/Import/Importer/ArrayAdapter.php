<?php
/**
 * Forked and adapted from https://github.com/firegento/FireGento_FastSimpleImport2
 */
namespace MagentoEse\DataInstall\Model\Import\Importer;

class ArrayAdapter extends \Magento\ImportExport\Model\Import\AbstractSource
{
    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var array The Data; Array of Array
     */
    protected $array = [];

    /**
     * Go to given position and check if it is valid
     *
     * @throws \OutOfBoundsException
     * @param int $position
     * @return void
     */
    public function seek($position)
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new \OutOfBoundsException("invalid seek position ($position)");
        }
    }

    /**
     * ArrayAdapter constructor.
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->array = $data;
        $this->position = 0;
        $colnames = array_keys($this->current());
        parent::__construct($colnames);
    }

    /**
     * Rewind to starting position
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Get data at current position
     *
     * @return mixed
     */
    public function current()
    {
        return $this->array[$this->position];
    }

    /**
     * Get current position
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Set pointer to next position
     *
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Is current position valid?
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->array[$this->position]);
    }

    /**
     * Column names getter.
     *
     * @return array
     */
    public function getColNames()
    {
        $colNames = [];
        foreach ($this->array as $row) {
            foreach (array_keys($row) as $key) {
                if (!is_numeric($key) && !isset($colNames[$key])) {
                    $colNames[$key] = $key;
                }
            }
        }
        return $colNames;
    }

    /**
     * Set Value
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function setValue($key, $value)
    {
        if (!$this->valid()) {
            return;
        }

        $this->array[$this->position][$key] = $value;
    }

    /**
     * Unset Value
     *
     * @param mixed $key
     * @return void
     */
    public function unsetValue($key)
    {
        if (!$this->valid()) {
            return;
        }

        unset($this->array[$this->position][$key]);
    }

    /**
     * Get Next Row
     *
     * @return mixed
     */
    protected function _getNextRow()
    {
        $this->next();
        return $this->current();
    }
}
