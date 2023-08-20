<?php


namespace Manticoresearch;

/**
 * Manticore response object
 *  Stores result array, time and errors
 * @category ManticoreSearch
 * @package ManticoreSearch
 * @author Adrian Nuta <adrian.nuta@manticoresearch.com>
 * @link https://manticoresearch.com
 */
use Manticoresearch\Exceptions\RuntimeException;

/**
 * Class Response
 * @package Manticoresearch
 */
class Response
{
    /**
     * execution time to get the response
     * @var integer|float
     */
    protected $time;

    /**
     * raw response as string
     * @var string
     */
    protected $string;

    /**
     * information about request
     * @var array
     */
    protected $transportInfo;

    protected $status;
    /**
     * response as array
     * @var array
     */
    protected $response;

    /**
     * additional params as array
     * @var array
     */
    protected $params;
    

    public function __construct($responseString, $status = null, $params = [])
    {
        if (is_array($responseString)) {
            $this->response = $responseString;
        } else {
            $this->string = $responseString;
        }
        $this->status = $status;
        $this->params = $params;
    }

    /*
     * Return response
     * @return array
     */
    public function getResponse()
    {
        if (null === $this->response) {
            $this->response = json_decode($this->string, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (json_last_error() === JSON_ERROR_UTF8 && $this->stripBadUtf8()) {
                    $this->response = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $this->string), true);
                } else {
                    throw new RuntimeException('fatal error while trying to decode JSON response');
                }
            }

            if (empty($this->response)) {
                $this->response = [];
            }
        }
        return $this->response;
    }
    
    /**
     * check if strip_bad_utf8 as been set to true
     * @return boolean
     */
    private function stripBadUtf8()
    {
        return !empty($this->transportInfo['body']) && !empty($this->transportInfo['body']['strip_bad_utf8']);
    }

    /*
     * Check whenever response has error
     * @return bool
     */
    public function hasError()
    {
        $response = $this->getResponse();
        return !empty($response['error']) ||
            !empty($response['errors']);
    }

    /*
     * Extract error array from respones item
     * @param array $response
     * @return array
     */
    private function extractErrors($response): array
    {
        if (!empty($response['error'])) {
            return is_array($response['error']) ? $response['error'] : ['trype' => $response['error']];
        } elseif (
            isset($response['items']) && 
            !empty($response['errors']
        )) {
            $errors = [];
            while ($item = array_shift($response['items'])) {
                foreach ($item as $type => $context) {
                    $errors = $this->extractErrors($context);
                    $errors['action'] = $type;
                }
            }
            return $errors;
        }
        return [];
    }

    /*
     * Return error
     * @return false|string
     */
    public function getError()
    {
        $errors = $this->extractErrors(
            $this->getResponse()
        );
        if ($error = array_shift($errors)) {
            return json_encode($error);
        }
        return '';
    }

    /*
     * Return error
     * @return false|string
     */
    public function getErrors()
    {
        return json_encode(
            $this->extractErrors(
                $this->getResponse()
            )
        );
    }

    /*
     * set execution time
     * @param int|float $time
     * @return $this
     */
    public function setTime($time)
    {
        $this->time = $time;
        return $this;
    }

    /*
     * returns execution time
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     *  set request info
     * @param array $info
     * @return $this
     */
    public function setTransportInfo($info)
    {
        $this->transportInfo = $info;
        return $this;
    }

    /**
     * get request info
     * @return array
     */
    public function getTransportInfo()
    {
        return $this->transportInfo;
    }
}
