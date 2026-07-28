<?php

declare(strict_types=1);

namespace Drupal\ebr\EventSubscriber;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ebr\EntityBusinessrules;
use Drupal\entity_clone\Event\EntityCloneEvent;
use Drupal\entity_clone\Event\EntityCloneEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clears EBR identity fields when cloning entities.
 *
 * Registered only when the entity_clone module is installed.
 *
 * @see \Drupal\ebr\EbrServiceProvider
 */
class EbrEntityCloneEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructs the event subscriber.
   */
  public function __construct(
    protected readonly MessengerInterface $messenger,
  ) {}

  /**
   * Clears EBR fields on the cloned target.
   *
   * @see \Drupal\entity_clone\Event\EntityCloneEvents::PRE_CLONE
   */
  public function clearEbrFields(EntityCloneEvent $event): void {
    $newEntity = $event->getClonedEntity();
    if (!($newEntity instanceof FieldableEntityInterface)) {
      return;
    }
    $fieldsToClear = [
      EntityBusinessrules::FIELD_INTERNAL_ID,
      EntityBusinessrules::FIELD_REMOTE_ID,
    ];
    foreach ($fieldsToClear as $ebrField) {
      if ($newEntity->hasField($ebrField) && !$newEntity->get($ebrField)->isEmpty()) {
        $this->messenger->addMessage($this->t('The source field @field_name was "%value". This value has been removed from the clone.', [
          '@field_name' => $ebrField,
          '%value' => $newEntity->get($ebrField)->value,
        ]));
        $newEntity->set($ebrField, NULL);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      EntityCloneEvents::PRE_CLONE => ['clearEbrFields'],
    ];
  }

}
