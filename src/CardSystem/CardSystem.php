<?php

namespace CultuurNet\UDB3\UiTPAS\EventConsumer\CardSystem;

use ValueObjects\StringLiteral\StringLiteral;

class CardSystem
{
    /**
     * @var StringLiteral
     */
    private $id;

    /**
     * @var StringLiteral
     */
    private $name;

    /**
     * @param StringLiteral $id
     * @param StringLiteral $name
     */
    public function __construct(
        StringLiteral $id,
        StringLiteral $name
    ) {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return StringLiteral
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return StringLiteral
     */
    public function getName()
    {
        return $this->name;
    }
}
