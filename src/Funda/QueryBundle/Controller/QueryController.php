<?php

namespace Funda\QueryBundle\Controller;

use Doctrine\Common\Cache\Cache;
use Funda\QueryBundle\Model\QueryResult;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QueryController extends Controller
{

    /**
     * The prefix of the cache key.
     */
    const CACHE_KEY_PREFIX = 'FundaQuery';

    /**
     * The life time of the cache in seconds.
     */
    const CACHE_LIFE_TIME = 1800;

    /**
     * The maximum requests that can be preformed in a minute.
     */
    const MAX_REQUEST_PER_MINUTE = 100;

    /**
     * The maximum result count.
     */
    const MAX_RESULTS = 10;

    /**
     * The property to group on.
     */
    const GROUP_PROPERTY = 'MakelaarId';

    /**
     * The property to display.
     */
    const DISPLAY_PROPERTY = 'MakelaarNaam';

    /**
     * The Funda base URL.
     */
    const FUNDA_BASE_URL = 'http://partnerapi.funda.nl/feeds/Aanbod.svc';

    /**
     * The delay between requests.
     *
     * @var integer
     */
    private $requestDelay = 0;

    /**
     * The cache handler.
     *
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

    /**
     * The Funda API key.
     *
     * @var string
     */
    private $fundaAPIKey;

    /**
     * Get the cache.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        if (isset($this->cache) === false) {
            $this->cache = $this->get('cache');
        }

        return $this->cache;
    }

    /**
     * Set the cache.
     *
     * @param Cache $cache The cache handler.
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the Funda API key.
     *
     * @return string
     */
    public function getFundaAPIKey()
    {
        if (isset($this->fundaAPIKey) === false) {
            $this->fundaAPIKey = $this->container->getParameter('funda_api_key');
        }
        return $this->fundaAPIKey;
    }

    /**
     * Set the Funda API key.
     *
     * @param string $fundaAPIKey
     */
    public function setFundaAPIKey($fundaAPIKey)
    {
        $this->fundaAPIKey = $fundaAPIKey;
    }

    /**
     * Renders the Amsterdam view.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function amsterdamAction()
    {
        $parameters = array();
        $parameters['name'] = 'Amsterdam';
        $parameters['error'] = null;
        try {
            $parameters['dataSet'] = $this->getDataResult('/amsterdam/');
        } catch (HttpException $e) {
            $parameters['error'] = $e->getMessage();
        }

        return $this->render('FundaQueryBundle:Query:topTen.html.twig', $parameters);
    }

    /**
     * Renders the Amsterdam tuin view.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function amsterdamTuinAction()
    {
        $parameters = array();
        $parameters['name'] = 'Amsterdam / Tuin';
        $parameters['error'] = null;
        try {
            $parameters['dataSet'] = $this->getDataResult('/amsterdam/tuin/');
        } catch (HttpException $e) {
            $parameters['error'] = $e->getMessage() . ' Code: ' . $e->getStatusCode();
        }

        return $this->render('FundaQueryBundle:Query:topTen.html.twig', $parameters);
    }

    /**
     * Get the data set result.
     *
     * The result made ready for displaying.
     *
     * @param $searchSubject
     * @return array
     */
    public function getDataResult($searchSubject)
    {
        // Construct cache key.
        $cacheKey = self::CACHE_KEY_PREFIX . $searchSubject;

        // Get cache handler.
        $cache = $this->getCache();

        // Fetch data set from cache.
        $dataSet = $cache->fetch($cacheKey);

        // If data set was not cached.
        if (false === $dataSet) {
            // Fetch data set from Funda.
            $dataSet = $this->constructDataSet($searchSubject);

            // Save data set in cache.
            $cache->save($cacheKey, $dataSet, self::CACHE_LIFE_TIME);
        }

        return $dataSet;
    }

    /**
     * Fetch the data set using the Funda API.
     *
     * @param string $searchSubject The search subject.
     * @return array
     */
    private function constructDataSet($searchSubject)
    {
        // Capture current time.
        $start = time();

        // Fetch amount of times a request can be preformed within a minute.
        $countdown = self::MAX_REQUEST_PER_MINUTE;

        // If last page was not set, set it now.
        $pageCount = $this->getPageCount($searchSubject);

        // Reduce one because the last page was just retrieved.
        --$countdown;

        // Set defaults.
        $result = new QueryResult();
        $currentPage = 0;

        // Don't stop 'til you get enough!
        while ($currentPage < $pageCount) {
            $newResult = $this->processAPIRequest($searchSubject, $currentPage);
            $result->merge($newResult);
            $this->processCount($countdown, $start, $currentPage);
        }

        return $this->processResult($result);
    }

    /**
     * Get the page count.
     *
     * @param string $searchSubject The search subject.
     * @return integer
     */
    public function getPageCount($searchSubject)
    {
        // Get lastPage.
        $URL = $this->constructURL($searchSubject, 0);

        // Fetch result.
        $rawResult = $this->fetch($URL, true);

        // If last page was not set, set it now.
        return $rawResult->Paging->AantalPaginas;
    }

    /**
     * Process the result.
     *
     * @param string $searchSubject The search subject.
     * @param integer $currentPage The current page.
     * @return QueryResult
     */
    private function processAPIRequest($searchSubject, $currentPage)
    {
        $result = new QueryResult();

        // Fetch property values.
        $groupProperty = self::GROUP_PROPERTY;
        $displayProperty = self::DISPLAY_PROPERTY;

        // Fetch the URL.
        $URL = $this->constructURL($searchSubject, $currentPage);

        // Fetch result.
        $rawResult = $this->fetch($URL);

        // Loop through the result objects.
        foreach ($rawResult->Objects as $object) {
            $key = $object->$groupProperty;
            if ($key === 0) {
                continue;
            }

            // Initiate this new group property.
            $result->createEntry($key, $object->$displayProperty);

            // Add a value to the group.
            $result->increaseEntryValue($key);
        }

        return $result;
    }

    /**
     * Fetch the page result.
     *
     * @param string $URL The URL from which to fetch the information.
     * @param boolean $stopOn401 Flag If a 401 code is retrieved the operation should be halted.
     * @return \stdClass
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException If something goes wrong with fetching the page.
     */
    public function fetch($URL, $stopOn401 = false)
    {
        // Fetch result with cURL.
        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $rawResult = curl_exec($ch);

        // Check code.
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        switch ($httpCode) {
            case 200:
                // If there was a delay set, there is no need for that anymore.
                if (0 != $this->requestDelay) {
                    $this->requestDelay = 0;
                }
                break;
            case 401:
                if (false === $stopOn401) {
                    // Back off!
                    // If the http code is 401, make the request again after a well deserved sleep.
                    // Usually this should not happen, but this script can be executed multiple times at once or
                    // a different script with the same key could be executed.
                    if (0 == $this->requestDelay) {
                        $this->requestDelay = 1;
                    } else {
                        $this->requestDelay = $this->requestDelay * 2;
                    }

                    sleep($this->requestDelay);

                    return $this->fetch($URL);
                }
            default:
                throw new HttpException($httpCode, 'The response contains an unexpected result.');
        }

        // Close the connection.
        curl_close($ch);

        // Convert the JSON into a stdClass.
        return json_decode($rawResult);
    }

    /**
     * @param integer $countdown The countdown.
     * @param integer $start The moment the countdown started, in seconds.
     * @param integer $currentPage The current page.
     */
    private function processCount(&$countdown, &$start, &$currentPage)
    {
        // Increase the pages decrease the countdown.
        ++$currentPage;
        --$countdown;

        // If the countdown reached the end, wait if needed and reset the countdown.
        if ($countdown == 1) {
            $elapsedTime = time() - $start;

            // If less then a minute has passed.
            if ($elapsedTime < 60) {
                // Sleep for a while.
                sleep(60 - $elapsedTime);
            }

            // Reset time and countdown.
            $start = time();
            $countdown = self::MAX_REQUEST_PER_MINUTE;
        }
    }

    /**
     * Format the result.
     *
     * @param QueryResult $result The result to be formatted.
     * @return array
     */
    private function processResult(QueryResult $result)
    {
        // Sort the results.
        $results = $result->processResult(self::MAX_RESULTS);

        $resultSet = array();
        foreach ($results as $entity) {
            $resultSet[] = array(
                'id' => $entity->getKey(),
                'name' => $entity->getDisplay(),
                'quantity' => $entity->getQuantity()
            );
        }

        return $resultSet;
    }

    /**
     * Construct the URL.
     *
     * @param string $searchSubject The search subject.
     * @param integer $page The page number.
     * @param integer $pageSize The page size, max 25.
     * @return string
     */
    private function constructURL($searchSubject, $page = 1, $pageSize = 25)
    {
        $URL = self::FUNDA_BASE_URL;
        $URL .= '/json/';
        $URL .= $this->getFundaAPIKey() . '?';
        $URL .= 'type=' . 'koop' . '&';
        $URL .= 'zo=' . $searchSubject . '&';
        $URL .= 'page=' . $page . '&';
        $URL .= 'pagesize=' . $pageSize;

        return $URL;
    }
}
