<?php

declare(strict_types=1);

namespace Drupal\ebr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\ebr\EntityBusinessrules;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Creates redirections to EBR entities.
 */
class EbrController extends ControllerBase {

  /**
   * Allowed link templates for EBR permalink redirects.
   */
  protected const ALLOWED_LINK_TEMPLATES = [
    'canonical',
    'edit-form',
  ];

  /**
   * Constructs the controller.
   */
  public function __construct(
    protected readonly EntityBusinessrules $ebr,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ebr.entity_business_rules'),
    );
  }

  /**
   * Provides redirection perma-links to EBR entities by their internal_id.
   *
   * This is a convenient helper to create links to entities by the
   * user-editable internal_id field instead of the entity ID (which can not be
   * edited by the user and might be different across environments).
   */
  public function doRedirection(Request $request, string $entity_type, string $internal_id, string $link_template = 'canonical'): LocalRedirectResponse {
    $entity = $this->loadEbrEntity($entity_type, $internal_id, $link_template);
    $operation = str_contains($link_template, 'edit') ? 'update' : 'view';
    if (!$entity->access($operation)) {
      throw new AccessDeniedHttpException();
    }

    $url = $entity->toUrl($link_template);
    $query = $url->getOption('query') ?? [];
    if ($request->query->has('post_redirect_destination')) {
      $query['destination'] = $request->query->get('post_redirect_destination');
    }
    foreach ($request->query->all() as $key => $value) {
      if ($key === 'post_redirect_destination') {
        continue;
      }
      $query[$key] = $value;
    }
    if ($query) {
      $url->setOption('query', $query);
    }

    return new LocalRedirectResponse($url->setAbsolute()->toString());
  }

  /**
   * Returns the title of an EBR link template.
   */
  public function getRedirectTitle(string $entity_type, string $internal_id, string $link_template = 'canonical'): string {
    try {
      $entity = $this->loadEbrEntity($entity_type, $internal_id, $link_template);
    }
    catch (NotFoundHttpException) {
      return (string) $this->t("'@element_key' is missing.", ['@element_key' => $internal_id]);
    }
    $label = $entity->label();
    if (empty($label)) {
      $label = $internal_id;
    }
    if (str_contains($link_template, 'edit-form')) {
      return (string) $this->t('Edit @label', ['@label' => $label]);
    }
    return (string) $label;
  }

  /**
   * Loads an EBR entity for redirection, validating type and link template.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function loadEbrEntity(string $entity_type, string $internal_id, string $link_template) {
    if (
      !in_array($entity_type, $this->ebr->getEntityTypes(), TRUE) ||
      !in_array($link_template, self::ALLOWED_LINK_TEMPLATES, TRUE)
    ) {
      throw new NotFoundHttpException();
    }
    $entity = $this->ebr->getEntity($entity_type, $internal_id);
    if (!$entity || !$entity->getEntityType()->hasLinkTemplate($link_template)) {
      throw new NotFoundHttpException();
    }
    return $entity;
  }

}
