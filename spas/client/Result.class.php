<?php


/**
 * Represents the result from an API call on the SpamAssassin server
 *
 * @category SpamAssassin
 * @package  spas
 *
 * @author   Pedro Padron <ppadron@w3p.com.br>
 * @license  http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
 */
class spas_client_Result
{
    /** @var string Protocol version returned by the server. */
    public $protocolVersion;

    /** @var bool Whether the server learned or reported the message. */
    public $didSet;

    /** @var bool Whether the server forgot the message. */
    public $didRemove;

    /** @var string Raw response headers. */
    public $headers;

    /** @var string Response body. */
    public $message;

    /**
     * Response code.
     *
     * @var int
     */
    public $responseCode;
    
    
    /**
     * Response message. EX_OK for sucess.
     *
     * @var string
     */
    public $responseMessage;
    
    
    /**
     * Response content length
     *
     * @var int
     */
    public $contentLength;
    
    
    /**
     * SpamAssassin score
     *
     * @var float
     */
    public $score;
    
    
    /**
     * How many points the message must score to be considered spam
     *
     * @var float
     */
    public $thresold;
    
    
    /**
     * Is it spam or not?
     *
     * @var bool
     */
    public $isSpam;
    
    
    /**
     * Raw output from SpamAssassin server
     *
     * @var string
     */
    public $output;
}
