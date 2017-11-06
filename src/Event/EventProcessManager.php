<?php

namespace CultuurNet\UDB3\UiTPAS\EventConsumer\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\Commands\AddLabel;
use CultuurNet\UDB3\Event\Commands\RemoveLabel;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AbstractLabelCommand;
use CultuurNet\UDB3\UiTPAS\EventConsumer\Event\Event\EventCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\EventConsumer\Label\UiTPASLabelsRepository;
use Psr\Log\LoggerInterface;

class EventProcessManager implements EventListenerInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $eventDocumentRepository;

    /**
     * @var DocumentRepositoryInterface
     */
    private $organizerDocumentRepository;

    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var UiTPASLabelsRepository
     */
    private $uitpasLabelsRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DocumentRepositoryInterface $eventDocumentRepository
     * @param DocumentRepositoryInterface $organizerDocumentRepository
     * @param CommandBusInterface $commandBus
     * @param UiTPASLabelsRepository $uitpasLabelsRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        DocumentRepositoryInterface $eventDocumentRepository,
        DocumentRepositoryInterface $organizerDocumentRepository,
        CommandBusInterface $commandBus,
        UiTPASLabelsRepository $uitpasLabelsRepository,
        LoggerInterface $logger
    ) {
        $this->eventDocumentRepository = $eventDocumentRepository;
        $this->organizerDocumentRepository = $organizerDocumentRepository;
        $this->commandBus = $commandBus;
        $this->uitpasLabelsRepository = $uitpasLabelsRepository;
        $this->logger = $logger;
    }

    /**
     * @param DomainMessage $domainMessage
     *
     * @uses handleEventCardSystemsUpdated
     */
    public function handle(DomainMessage $domainMessage)
    {
        $map = [
            EventCardSystemsUpdated::class => 'handleEventCardSystemsUpdated',
        ];

        $payload = $domainMessage->getPayload();
        $className = get_class($payload);
        if (isset($map[$className])) {
            $handlerMethodName = $map[$className];
            call_user_func([$this, $handlerMethodName], $payload);
        }
    }

    /**
     * @param EventCardSystemsUpdated $eventCardSystemsUpdated
     */
    private function handleEventCardSystemsUpdated(EventCardSystemsUpdated $eventCardSystemsUpdated)
    {
        $eventId = $eventCardSystemsUpdated->getId()->toNative();

        $this->logger->info('Handling updated card systems message for event ' . $eventId);

        $uitpasLabels = $this->uitpasLabelsRepository->loadAll();

        if ($eventCardSystemsUpdated->getCardSystems()->length() === 0) {
            $this->logger->info('Removing all UiTPAS labels from event ' . $eventId);
            $this->removeLabelsFromEvent($eventId, $uitpasLabels);
        } else {
            $this->logger->info('Inheriting UiTPAS labels from organizer on event ' . $eventId);
            $this->inheritLabelsFromOrganizerToEvent($eventId, $uitpasLabels);
        }
    }

    /**
     * @param string $eventId
     * @param Label[] $uitpasLabels
     */
    private function removeLabelsFromEvent($eventId, array $uitpasLabels)
    {
        // Simply remove all UiTPAS labels from the event, even if they're found
        // on the JSON-LD or not. This is the best way to make sure there are no
        // UiTPAS labels on the event, and the aggregate will just ignore the
        // commands if the labels are not present anyway.
        $commands = array_map(
            function (Label $uitpasLabel) use ($eventId) {
                return new RemoveLabel(
                    $eventId,
                    $uitpasLabel
                );
            },
            $uitpasLabels
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param string $eventId
     * @param Label[] $uitpasLabels
     */
    private function inheritLabelsFromOrganizerToEvent($eventId, array $uitpasLabels)
    {
        $eventDocument = $this->eventDocumentRepository->get($eventId);
        if (!$eventDocument) {
            $this->logger->error('Event with id ' . $eventId . ' not found in injected DocumentRepository!');
        }

        $jsonLD = $eventDocument->getBody();
        if (!isset($jsonLD->organizer) || !isset($jsonLD->organizer->labels)) {
            $this->logger->info('Found no organizer, or no organizer labels, on event ' . $eventId);
            return;
        }

        $organizerLabels = $jsonLD->organizer->labels;

        $this->logger->info(
            'Found organizer labels on event ' . $eventId . ': ' . implode(', ', $organizerLabels)
        );

        $uitpasLabelsAsStrings = array_map(
            function (Label $label) {
                return (string) $label;
            },
            $uitpasLabels
        );

        $organizerUiTPASLabelsAsStrings = array_intersect($uitpasLabelsAsStrings, $organizerLabels);

        $this->logger->info(
            'Found uitpas organizer labels on event ' . $eventId . ': ' . implode(', ', $organizerUiTPASLabelsAsStrings)
        );

        $commands = array_map(
            function ($uitpasLabel) use ($eventId) {
                return new AddLabel(
                    $eventId,
                    new Label($uitpasLabel)
                );
            },
            $organizerUiTPASLabelsAsStrings
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param AbstractLabelCommand[] $commands
     */
    private function dispatchCommands($commands)
    {
        foreach ($commands as $command) {
            $this->logger->info(
                'Dispatching label command ' . get_class($command),
                [
                    'item id' => $command->getItemId(),
                    'label' => (string) $command->getLabel(),
                ]
            );

            $this->commandBus->dispatch($command);
        }
    }
}
