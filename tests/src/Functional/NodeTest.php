<?php

namespace Drupal\Tests\audit_log\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests audit log functionality on node crud operations.
 *
 * @group audit_log
 */
class NodeTest extends BrowserTestBase {

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'audit_log', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $web_user = $this->drupalCreateUser(['create article content']);
    $this->webUser = $web_user;
  }

  /**
   * Tests audit log functionality on node crud operations.
   */
  public function testNodeCrud() {
    $count = \Drupal::database()->query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(0, $count);

    // Initial creation.
    $node = Node::create([
      'uid' => $this->webUser->id(),
      'type' => 'article',
      'title' => 'test_changes',
    ]);
    $node->save();

    $count = \Drupal::database()->query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(1, $count);

    // Update the node without applying changes.
    $node->save();
    $count = \Drupal::database()->query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(2, $count);

    // Apply changes.
    $node->title = 'updated';
    $node->save();

    $count = \Drupal::database()->query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(3, $count);

    $node->delete();
    $count = \Drupal::database()->query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(4, $count);
  }

}
