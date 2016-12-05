<?php

namespace Ts\Service;

//return ApiMessage::withArray(status, message, data);

/**
 * Api消息类.
 */
class ApiMessage
{
    protected $status = 1;

    protected $message = '';

    protected $data = '';

    final public function __construct($status = 1, $message = '', $data = '')
    {
        $this->setStatus($status)
            ->setMessage($message)
            ->setData($data);

        $this->data = $data;
    }

    public function setStatus($status)
    {
        $this->status = (int) (bool) $status;

        return $this;
    }

    public function setMessage($message)
    {
        $this->message = (string) $message;

        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function toArray()
    {
        return array(
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => $this->data,
        );
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function toObject()
    {
        return (object) $this->toArray();
    }

    public static function with($type, $status, $message, $data)
    {
        $response = new self($status, $message, $data);

        return call_user_func(array($response, 'to'.ucfirst($type)));
    }

    public static function __callStatic($method, array $args = array())
    {
        $method = str_replace('with', '', $method); // withJson => Json => toJson;
        $args = array_merge(array($method), $args);

        return call_user_func_array('self::with', (array) $args);
    }
}
