<?php

namespace Drupal\audit_log;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\audit_log\EventSubscriber\EventSubscriberInterface;
use Drupal\audit_log\StorageBackend\StorageBackendInterface;

/**
 * Service for responding to audit log events.
 *
 * @package Drupal\audit_log
 */
class AuditLogLogger {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The current time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The storage backend.
   *
   * @var Drupal\audit_log\StorageBackend\StorageBackendInterface
   */
  protected $storage;

  /**
   * Constructs a AuditLogLogger object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The current time.
   * @param \Drupal\audit_log\StorageBackend\StorageBackendInterface $storage
   *   The storage backend service.
   */
  public function __construct(AccountProxyInterface $current_user, TimeInterface $time, StorageBackendInterface $storage) {
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->storage = $storage;
  }

  /**
   * An array of available event subscribers to respond to events.
   *
   * @var array
   */
  protected $entityEventEventSubscribers;

  /**
   * Logs an event to the audit log.
   *
   * @param string $event_type
   *   The type of event being reported such as "insert", "update", or "delete".
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity affected during the event.
   */
  public function log($event_type, EntityInterface $entity) {
    $event = new AuditLogEvent();
    $account = $this->currentUser->getAccount();
    $event->setUser($account);
    $event->setEntity($entity);
    $event->setEventType($event_type);
    $event->setRequestTime($this->time->getRequestTime());

    foreach ($this->sortEventSubscribers() as $event_subscriber) {
      if ($event_subscriber->reactTo($event)) {
        $this->storage->save($event);
        break;
      }
    }

  }

  /**
   * Adds an event subscriber to the processing pipeline.
   *
   * @param \Drupal\audit_log\EventSubscriber\EventSubscriberInterface $event_subscriber
   *   An audit log event event subscriber.
   * @param int $priority
   *   A priority specification for the event subscriber.
   *
   *   Must be a positive integer.
   *
   *   Lower number event subscribers are processed
   *   before higher number event subscribers.
   */
  public function addEventSubscriber(EventSubscriberInterface $event_subscriber, $priority = 0) {
    $this->entityEventEventSubscribers[$priority][] = $event_subscriber;
  }

  /**
   * Get event subscribers.
   */
  public function getEventSubscribers() {
    return $this->sortEventSubscribers();
  }

  /**
   * Sorts the available event subscribers by priority.
   *
   * @return array
   *   The sorted array of event subscribers.
   */
  protected function sortEventSubscribers() {
    $sorted = [];
    krsort($this->entityEventEventSubscribers);

    foreach ($this->entityEventEventSubscribers as $entity_event_event_subscribers) {
      $sorted = array_merge($sorted, $entity_event_event_subscribers);
    }
    return $sorted;
  }

}
