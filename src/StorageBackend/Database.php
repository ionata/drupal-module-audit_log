<?php

namespace Drupal\audit_log\StorageBackend;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\audit_log\AuditLogEventInterface;
use Drupal\audit_log\Entity\AuditLog;

/**
 * Writes audit events to a custom database table.
 *
 * @package Drupal\audit_log\StorageBackend
 */
class Database implements StorageBackendInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Database object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function save(AuditLogEventInterface $event) {
    $values = [
      'entity_id' => $event->getEntity()->id(),
      'entity_type' => $event->getEntity()->getEntityTypeId(),
      'event' => $event->getEventType(),
      'previous_state' => $event->getPreviousState(),
      'current_state' => $event->getCurrentState(),
      'message' => $event->getMessage(),
    ];

    $this->moduleHandler->alter('audit_log_save', $values, $event);

    AuditLog::create($values)->save();
  }

}
