<?php
namespace Staticus\Diactoros\Response;

interface FileResponseInterface
{
    /**
     * @return resource
     */
    public function getResource();

    /**
     * @return mixed
     */
    public function getContent();

    /**
     * @param mixed $content
     */
    public function setContent($content);
}
