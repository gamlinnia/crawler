<?php

/*
 * This file is part of the php-phantomjs.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JonnyW\PhantomJs\Http;

use JonnyW\PhantomJs\Exception\NotWritableException;

/**
 * PHP PhantomJs
 *
 * @author Jon Wenmoth <contact@jonnyw.me>
 */
class CaptureRequest extends AbstractRequest
    implements CaptureRequestInterface
{
    /**
     * Request type
     *
     * @var string
     * @access protected
     */
    protected $type;

    /**
     * File to save output.
     *
     * @var string
     * @access protected
     */
    protected $outputFile;

    /**
     * Rect top
     *
     * @var int
     * @access protected
     */
    protected $rectTop;

    /**
     * Rect left
     *
     * @var int
     * @access protected
     */
    protected $rectLeft;

    /**
     * Rect width
     *
     * @var int
     * @access protected
     */
    protected $rectWidth;

    /**
     * Rect height
     *
     * @var int
     * @access protected
     */
    protected $rectHeight;

    /**
     * Internal constructor
     *
     * @access public
     * @param  string                                $url     (default: null)
     * @param  string                                $method  (default: RequestInterface::METHOD_GET)
     * @param  int                                   $timeout (default: 5000)
     * @return \JonnyW\PhantomJs\Http\CaptureRequest
     */
    public function __construct($url = null, $method = RequestInterface::METHOD_GET, $timeout = 5000)
    {
        parent::__construct($url, $method, $timeout);

        $this->rectTop    = 0;
        $this->rectLeft   = 0;
        $this->rectWidth  = 0;
        $this->rectHeight = 0;
    }

    /**
     * Get request type
     *
     * @access public
     * @return string
     */
    public function getType()
    {
        if (!$this->type) {
            return RequestInterface::REQUEST_TYPE_CAPTURE;
        }

        return $this->type;
    }

    /**
     * Set request type
     *
     * @access public
     * @param  string                                 $type
     * @return \JonnyW\PhantomJs\Http\AbstractRequest
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set viewport size.
     *
     * @access public
     * @param  int                                    $width
     * @param  int                                    $height
     * @param  int                                    $top    (default: 0)
     * @param  int                                    $left   (default: 0)
     * @return \JonnyW\PhantomJs\Http\AbstractRequest
     */
    public function setCaptureDimensions($width, $height, $top = 0, $left = 0)
    {
        $this->rectWidth  = (int) $width;
        $this->rectHeight = (int) $height;
        $this->rectTop    = (int) $top;
        $this->rectLeft   = (int) $left;

        return $this;
    }

    /**
     * Get rect top.
     *
     * @access public
     * @return int
     */
    public function getRectTop()
    {
        return (int) $this->rectTop;
    }

    /**
     * Get rect left.
     *
     * @access public
     * @return int
     */
    public function getRectLeft()
    {
        return (int) $this->rectLeft;
    }

    /**
     * Get rect width.
     *
     * @access public
     * @return int
     */
    public function getRectWidth()
    {
        return (int) $this->rectWidth;
    }

    /**
     * Get rect height.
     *
     * @access public
     * @return int
     */
    public function getRectHeight()
    {
        return (int) $this->rectHeight;
    }

    /**
     * Set file to save output.
     *
     * @access public
     * @param  string                                           $file
     * @throws \JonnyW\PhantomJs\Exception\NotWritableException
     * @return \JonnyW\PhantomJs\Http\CaptureRequest
     */
    public function setOutputFile($file)
    {
        if (!is_writable(dirname($file))) {
            throw new NotWritableException(sprintf('Output file is not writeable by PhantomJs: %s', $file));
        }

        $this->outputFile = $file;

        return $this;
    }

    /**
     * Get output file.
     *
     * @access public
     * @return string
     */
    public function getOutputFile()
    {
        return $this->outputFile;
    }
}
