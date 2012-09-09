<?php

namespace PHPSpec2\Exception\Stub;

class ClassDoesNotExistsException extends StubException
{
    private $classname;

    public function __construct($classname)
    {
        $this->classname = $classname;

        parent::__construct(sprintf(
            'Class <value>%s</value> does not exists.', $classname
        ));
    }

    public function getClassname()
    {
        return $this->classname;
    }
}
