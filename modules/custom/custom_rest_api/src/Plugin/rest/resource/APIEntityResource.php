<?php

namespace Drupal\custom_rest_api\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Represents entity as resource.
 *
 * @RestResource(
 *   id = "custom_entity_api",
 *   label = @Translation("Custom Entity REST API"),
 *   uri_paths = {
 *     "canonical" = "/api/entity/{type}/{id}",
 *   }
 * )
 */
class APIEntityResource extends ResourceBase {

  use APIEntityResourceTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param string $type
   *   The entity type.
   * @param int $id
   *   The entity id.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($type = 'node', $id = NULL) {

    $entity_data = [];
    try {
      switch ($type) {
        case 'node':
          $entity = $this->entityTypeManager->getStorage($type)->load($id);
          if (!empty($entity) && $entity->get('status')->value == 1) {
            $entity_data = $this->keyValueResourceResponse($entity);
          }
          else {
            return new ModifiedResourceResponse($this->t("No data found"), 200);
          }
          break;

        case 'taxonomy_term':
        case 'paragraph':
        case 'user':
          $entity = $this->entityTypeManager->getStorage($type)->load($id);
          if (!empty($entity)) {
            $entity_data = $this->keyValueResourceResponse($entity);
          }
          else {
            return new ModifiedResourceResponse($this->t("No data found"), 200);
          }
          break;

        default:
          throw new BadRequestHttpException($this->t('Not valid parameters.'));
      }

      if (!empty($entity_data)) {
        return new ModifiedResourceResponse($entity_data, 200);
      }
      else {
        return new ModifiedResourceResponse($this->t("No data found"), 200);
      }

    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, $this->t('Internal Server Error'), $e);
    }
  }

}
