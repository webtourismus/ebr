<?php

declare(strict_types=1);


namespace Drupal\ebr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Creates redirections to EBR entities.
 */
class EbrController extends ControllerBase {

  /**
   * Provides redirection perma-links to EBR entities by their internal_id.
   *
   * This is a convenient helper to create links to entites by the user-editable internal_id field
   * instead of the entity ID (which can not be edited by the user and might be different accross environments).
   */
  public function doRedirection(string $entity_type, string $internal_id, string $link_template = 'canonical', Request $request) {
    $entities = $this->entityTypeManager()->getStorage($entity_type)->loadByProperties([
      'internal_id' => $internal_id,
    ]);
    $entity = reset($entities);
    if (!$entity || !$entity->getEntityType()->hasLinkTemplate($link_template)) {
      throw new NotFoundHttpException;
    }
    /**
     * A redirection URL with 'destination' GET parameter would get instantly double redirected,
     * so we create the links with the query param 'post_redirect_destination' instead,
     * which is used as an alias for the real Drupal core 'destination' query parameter.
     */
    $queryParams = str_replace('post_redirect_destination=', 'destination=', $request->getQueryString() ?? '');
    return new RedirectResponse($entity->toUrl($link_template)->toString() . '?' . $queryParams);
  }

  /**
   * Returns the title of an EBR link template.
   */
  public function getRedirectTitle(string $entity_type, string $internal_id, string $link_template = 'canonical') {
    $entities = $this->entityTypeManager()->getStorage($entity_type)->loadByProperties([
      'internal_id' => $internal_id,
    ]);
    $entity = reset($entities);
    if (!$entity || !$entity->getEntityType()->hasLinkTemplate($link_template)) {
      return $this->t("'@element_key' is missing.", ['@element_key' => $internal_id]);
    }
    $label = $entity->label();
    if (empty($label)) {
      $label = $internal_id;
    }
    if (str_contains($link_template, 'edit-form')) {
      return $this->t('Edit @label', ['@label' => $label]);
    }
    return $label;
  }
}
