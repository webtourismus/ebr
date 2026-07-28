<?php

declare(strict_types=1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * An interface unifying teaser viewmodes and teaser-like entities such as paragraphs.
 *
 * Provides a homogenous, core set of semantic field getters for inhomogenous
 * entities. Use this interface so different entites can use a shared teaser
 * template by using the interface's getters instead of entity field names.
 *
 * To make an entity teaserable, you have to create a bundle classes
 * implementing this interface and enable it via hook_entity_bundle_info_alter()
 *
 * Entities can remap their field storage name to these semantic names
 * (e.g. node.getTeaserTextField might return node.body) or even compute
 * fields dynamically (e.g. a linkblock paragraph might return the refernced
 * node's image field, if its own media field is empty).
 *
 * Note that by design you must not make assumptions on the field type:
 * E.g. a paragraph might have a ER field of media entities, but also might
 * fall back to a node' imagefile field. It is the developers responsibility
 * to keep the results semantically meaningful!
 */
interface TeaserableInterface extends ContentEntityInterface {

  /**
   * All viewsmodes starting with this prefix are considered egilible by default.
   */
  public const TEASER_VIEWMODE_PREFIX = 'teaser';

  /**
   * Returns true if the viewmode should invoke the teaserable behaviors.
   */
  public function isTeaserableViewmode(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): bool;

  /**
   * Returns the title / main label of an entity.
   */
  public function getTeaserTitleField(): ?FieldItemListInterface;

  /**
   * Returns a subtitle, a slogan, keywords or similar for the entity.
   *
   * This field is semantically weak, it can serve for any textual information
   * that is intented to be shown close to the title in teaser view modes.
   */
  public function getTeaserSubtitleField(): ?FieldItemListInterface;

  /**
   * Returns one or more images of the entity.
   *
   * This field might return various media entities and plain imagefile fields.
   */
  public function getTeaserImagesField(): ?FieldItemListInterface;

  /**
   * Returns an intro text / short description of an entity.
   */
  public function getTeaserTextField(): ?FieldItemListInterface;

  /**
   * Return the render array for the title field.
   */
  public function getRenderedTeaserTitle(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array;

  /**
   * Return the render array for the subtitle field.
   */
  public function getRenderedTeaserSubTitle(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array;

  /**
   * Return the render array for the images field.
   */
  public function getRenderedTeaserImages(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array;

  /**
   * Return the render array for the text field.
   */
  public function getRenderedTeaserText(string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array;

}
