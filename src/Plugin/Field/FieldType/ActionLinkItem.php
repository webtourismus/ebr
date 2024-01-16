<?php

declare(strict_types = 1);

namespace Drupal\ebr\Plugin\Field\FieldType;

use Drupal\ebr\Entity\ActionableInterface;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Variant of the 'link' field that links an entity by internal_id field value.
 *
 * @FieldType(
 *   id = "action_link",
 *   label = @Translation("Action link"),
 *   description = @Translation("A computed, contextual action link."),
 *   default_widget = "link_default",
 *   default_formatter = "link",
 *   constraints = {"LinkType" = {}, "LinkAccess" = {}, "LinkExternalProtocols" = {}, "LinkNotExistingInternal" = {}}
 * )
 */
class ActionLinkItem extends LinkItem {

  /**
   * Whether or not the value has been calculated.
   */
  protected bool $isCalculated = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'action_type' => '',
      'title' => DRUPAL_DISABLED,
      // This _MUST_ be an _INTERNAL_ link so that an OutboundPathProcesser and
      // decorate it with contextual query parameters. Redirection to an
      // external site can be done in the OutboundPathProcesser or in a
      // KernelEvents::REQUEST event subscriber.
      'link_type' => LinkItemInterface::LINK_INTERNAL,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    $this->ensureCalculated();
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureCalculated();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureCalculated();
    return parent::getValue();
  }

  /**
   * Calculates the value of the field and sets it.
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      /** @var \Drupal\ebr\Entity\ActionableInterface $entity */
      $entity = $this->getEntity();
      if (!$entity->isNew() && $entity instanceof ActionableInterface) {
        $actionId = $this->getSetting('action_type');
        $value = [
          'uri' => NULL,
        ];
        if ($actionId && $actionUrl = $entity->getActionUrl($actionId)) {
          $value = [
            'uri' => $actionUrl->toString(),
            'title' => $entity->getActionLabel($actionId),
          ];
        }
        // Only include the HTML attribute options array. Other options (like e.g. the referenced
        // entity instance) do not work with the field serializer for views export.
        if (array_key_exists('attributes', $actionUrl->getOptions() ?? [])) {
          $value['options']['attributes'] = $actionUrl->getOptions()['attributes'];
        }
        $this->setValue($value);
      }
      $this->isCalculated = TRUE;
    }
  }

}
