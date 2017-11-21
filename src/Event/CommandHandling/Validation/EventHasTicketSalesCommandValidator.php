<?php

namespace CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation;

use CultuurNet\Broadway\CommandHandling\Validation\CommandValidatorInterface;
use CultuurNet\UDB3\Event\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdatePriceInfo;

class EventHasTicketSalesCommandValidator implements CommandValidatorInterface
{
    /**
     * @var \CultureFeed_Uitpas
     */
    private $uitpas;

    public function __construct(\CultureFeed_Uitpas $uitpas)
    {
        $this->uitpas = $uitpas;
    }

    /**
     * @inheritdoc
     */
    public function validate($command)
    {
        if (!($command instanceof UpdateOrganizer) && !($command instanceof UpdatePriceInfo)) {
            return;
        }

        $eventId = $command->getItemId();
        $hasTicketSales = $this->uitpas->eventHasTicketSales($eventId);

        if ($hasTicketSales) {
            throw new EventHasTicketSalesException($eventId);
        }
    }
}
