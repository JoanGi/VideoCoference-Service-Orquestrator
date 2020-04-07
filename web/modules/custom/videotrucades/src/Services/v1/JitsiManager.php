<?php

namespace Drupal\videotrucades\Services\v1;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;


/**
 * Notifications Manager.
 */
class JitsiManager {

  /**
   * Entity Manager Service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $current_user
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    // Set dependecy injection.
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Create instance
   *
   * @param array $values
   *   Values from the content.
   */
  public function createInstance(array $values) {
    kint($values);
    die();
  }
  /**
   * Create instance
   *
   * @param array $values
   *   Values from the content.
   */
  public function UpdateInstance(array $values)
  {
    // Token is generated by app. You'll have to send the token to Drupal.
    kint('Updating Instancde');
    die();
  }
  /**
   * Delete instance
   *
   * @param array $values
   *   Values from the content.
   */
  public function deleteInstance(array $values)
  {
    // Token is generated by app. You'll have to send the token to Drupal.
    kint('Deleeting Instancde');
    die();
  }

}
