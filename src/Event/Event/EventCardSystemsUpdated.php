<?php

namespace CultuurNet\UDB3\UiTPAS\EventConsumer\Event\Event;

use CultuurNet\UDB3\UiTPAS\EventConsumer\CardSystem\CardSystems;
use ValueObjects\StringLiteral\StringLiteral;

class EventCardSystemsUpdated
{
    /**
     * @var StringLiteral
     */
    private $id;

    /**
     * @var CardSystems
     */
    private $cardSystems;

    /**
     * @param StringLiteral $id
     * @param CardSystems $cardSystems
     */
    public function __construct(
        StringLiteral $id,
        CardSystems $cardSystems
    ) {
        $this->id = $id;
        $this->cardSystems = $cardSystems;
    }

    /**
     * @return StringLiteral
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CardSystems
     */
    public function getCardSystems()
    {
        return $this->cardSystems;
    }
}
