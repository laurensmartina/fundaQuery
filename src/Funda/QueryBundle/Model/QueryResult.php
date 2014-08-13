<?php

namespace Funda\QueryBundle\Model;

class QueryResult
{
    /**
     * The list of entries.
     *
     * @var Entry[]
     */
    private $entries = array();

    /**
     * The list of entries, mapping the key an quantity.
     *
     * @var array
     */
    private $entryMap = array();

    /**
     * Creates an entry, if not created before.
     *
     * @param $key
     * @param $display
     */
    public function createEntry($key, $display)
    {
        try {
            $this->getEntry($key);
        } catch (\Exception $e) {
            $this->entries[$key] = new Entry($key, $display);
            $this->syncEntry($this->entries[$key]);
        }
    }

    /**
     * Get a entry by key.
     *
     * @param mixed $key The key of the entry.
     * @return Entry
     * @throws \Exception
     */
    private function getEntry($key)
    {
        if (isset($this->entries[$key]) === false) {
            throw new \Exception('Entry not found.');
        }

        return $this->entries[$key];
    }

    /**
     * Increase the value of the entry.
     *
     * @param mixed $key The key of the entry.
     * @throws \Exception If the entry could not be found.
     */
    public function increaseEntryValue($key)
    {
        $entry = $this->getEntry($key);
        $entry->increaseQuantity();
        $this->syncEntry($entry);
    }

    /**
     * Sync the entry quantity.
     *
     * @param Entry $entry The entry.
     */
    public function syncEntry(Entry $entry)
    {
        $this->entryMap[$entry->getKey()] = $entry->getQuantity();
    }

    /**
     * Format the result.
     *
     * @param integer $maxResult The max results.
     * @return Entry[]
     */
    public function processResult($maxResult)
    {
        // Sort the results.
        arsort($this->entryMap);

        // Pick the top results.
        $resultSlice = array_slice($this->entryMap, 0, $maxResult, true);

        // Format the results so a human can make sense of it.
        $result = array();
        foreach ($resultSlice as $key => $quantity) {
            $result[] = $this->getEntry($key);
        }

        return $result;
    }

    /**
     * Merge query results.
     *
     * @param QueryResult $foreignQueryResult The query result to merge.
     */
    public function merge(QueryResult $foreignQueryResult)
    {
        foreach ($foreignQueryResult->entries as $key => $foreignEntry) {
            try {
                // Check is there is an local entry with the same key.
                $localEntry = $this->getEntry($key);
                // Increase the quantity with the foreign quantity.
                $localEntry->increaseQuantity($foreignEntry->getQuantity());
            } catch (\Exception $e) {
                // Set foreign entry.
                $this->entries[$key] = $foreignEntry;
            }

            $this->syncEntry($this->entries[$key]);
        }
    }
}
