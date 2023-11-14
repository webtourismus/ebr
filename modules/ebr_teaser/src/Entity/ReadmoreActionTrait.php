<?php

declare(strict_types = 1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ebr\Entity\ActionableInterface;

/**
 * Methods for \Drupal\ebr\Entity\ActionableInterface.
 */
trait ReadmoreActionTrait {

  /**
   * {@inheritDoc}
   */
  public static function getDefaultActions(): array {
    return [ReadmoreActionableInterface::ACTION_READMORE];
  }

  /**
   * {@inheritDoc}
   */
  public static function getDefaultActionLabel($actionId): TranslatableMarkup|string|NULL {
    return match ($actionId) {
      ReadmoreActionableInterface::ACTION_READMORE => new TranslatableMarkup('Details'),
      default => NULL,
    };
  }

  /**
   * {@inheritDoc}
   */
  public function getActionFieldnames(?string $viewMode = NULL): array {
    $result = [];
    foreach ($this->getDefaultActions() as $actionId) {
      if ($this->getActionUrl($actionId)) {
        $result[$actionId] = ActionableInterface::ACTION_FIELD_PREFIX . $actionId;
      }
    }
    if (!empty($viewMode)) {
      $enabledComponents = $this->entityTypeManager()
        ->getStorage('entity_view_display')
        ->load("{$this->getEntityTypeId()}.{$this->bundle()}.{$viewMode}")
        ?->getComponents();
      if (empty($enabledComponents)) {
        return [];
      }
      $result = array_intersect($result, array_keys($enabledComponents));
      uasort($result, function($a, $b) use ($enabledComponents) {
        return $enabledComponents[$a]['weight'] < $enabledComponents[$b]['weight'] ? -1 : 1;
      });
    }
    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function getActionLabel(string $actionId): TranslatableMarkup|string|NULL {
    if ($this->getActionUrl($actionId)) {
      return $this->getDefaultActionLabel($actionId);
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getActionUrl(string $actionId): ?Url {
    if ($actionId != ReadmoreActionableInterface::ACTION_READMORE) {
      return NULL;
    }
    /** @var \Drupal\Core\Url $url */
    $url = $this->toUrl('canonical');
    if (!$url instanceof Url || !$this->access()) {
      return NULL;
    }
    $url->setOption('attributes', [
      'data-action-link-entity' => $this->getEntityTypeId(),
      'data-action-link-bundle' => $this->bundle(),
      'data-action-link-type' => $actionId,
      'class' => [
        "action-link-{$actionId}",
      ],
    ]);
    return $url;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenderedAction(string $actionId, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array {
    $fieldName = $this->getActionFieldnames($viewMode)[$actionId] ?? NULL;
    if (empty($fieldName)) {
      return NULL;
    }
    $displayOptions = $this->entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("{$this->getEntityTypeId()}.{$this->bundle()}.{$viewMode}")
        ?->getComponent($fieldName);
    if (is_null($displayOptions)) {
      return NULL;
    }
    $build = $this->entityTypeManager()->getViewBuilder($this->getEntityTypeId())->viewField(
      $this->get($fieldName),
      $displayOptions
    );
    $build['#link_action_type'] = $actionId;
    // @see \Drupal\designsystem\DesignHelper::getRealViewmode()
    $build['#view_mode'] = $viewMode;
    return $build;
  }

}
