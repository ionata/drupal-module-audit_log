<?php

namespace Drupal\audit_log\EventSubscriber;

use Drupal\audit_log\AuditLogEventInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Processes taxonomy_term entity events.
 *
 * @package Drupal\audit_log\EventSubscriber
 */
class Taxonomy implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function reactTo(AuditLogEventInterface $event) {
    $entity = $event->getEntity();
    if (!in_array($entity->getEntityTypeId(), [$this->getEntityType()])) {
      return FALSE;
    }

    $event_type = $event->getEventType();

    $current_state = $previous_state = 'active';

    /** @var \Drupal\taxonomy\Entity\Term $entity */
    $args = [
      '@title' => Markup::create($entity->getName()),
      '@voc' => Markup::create($entity->getVocabularyId()),
    ];

    if ($event_type == 'insert') {
      $event
        ->setMessage($this->t('@title term has been added to @voc vocabulary.', $args))
        ->setPreviousState(NULL)
        ->setCurrentState($current_state);
      return TRUE;
    }

    if ($event_type == 'update') {
      $event
        ->setMessage($this->t('@title term has been update in @voc vocabulary.', $args))
        ->setPreviousState($previous_state)
        ->setCurrentState($current_state);
      return TRUE;
    }

    if ($event_type == 'delete') {
      $event
        ->setMessage($this->t('@title term has been deleted from @voc vocabulary.', $args))
        ->setPreviousState($previous_state)
        ->setCurrentState(NULL);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'taxonomy_term';
  }

}
