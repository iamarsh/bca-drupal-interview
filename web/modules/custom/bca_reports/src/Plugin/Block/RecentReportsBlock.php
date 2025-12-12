<?php

namespace Drupal\bca_reports\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Recent Reports' Block.
 *
 * @Block(
 *   id = "bca_recent_reports",
 *   admin_label = @Translation("Recent Reports"),
 *   category = @Translation("BCA"),
 * )
 */
class RecentReportsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $items = [];

    $entity_type_manager = \Drupal::entityTypeManager();
    $current_user = \Drupal::currentUser();

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'report')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 50);

    $nids = $query->execute();
    $nodes = $entity_type_manager->getStorage('node')->loadMultiple($nids);

    $count = 0;
    foreach ($nodes as $node) {
      if ($count >= 5) {
        break;
      }

      $can_access = FALSE;
      if ($current_user->isAuthenticated()) {
        if ($node->getOwnerId() == $current_user->id() || $current_user->hasPermission('administer nodes')) {
          $can_access = TRUE;
        }
      }

      if (!$can_access) {
        continue;
      }

      $author = $entity_type_manager->getStorage('user')->load($node->getOwnerId());
      $related_reports = $this->findRelatedReports($node->id());

      $items[] = [
        'title' => $node->getTitle(),
        'author' => $author ? $author->getDisplayName() : 'Unknown',
        'created' => date('Y-m-d', $node->getCreatedTime()),
        'related_count' => count($related_reports),
      ];

      $count++;
    }

    return [
      '#theme' => 'item_list',
      '#items' => array_map(function($item) {
        return $item['title'] . ' by ' . $item['author'] .
               ' (' . $item['created'] . ') - ' .
               $item['related_count'] . ' related';
      }, $items),
      '#title' => $this->t('Recent Reports'),
    ];
  }

  /**
   * Find related reports based on shared categories.
   *
   * @param int $node_id
   *   The current node ID.
   *
   * @return array
   *   Array of related node IDs.
   */
  private function findRelatedReports($node_id) {
    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);

      if (!$node || !$node->hasField('field_category')) {
        return [];
      }

      $category_ids = array_column($node->get('field_category')->getValue(), 'target_id');

      if (empty($category_ids)) {
        return [];
      }

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'report')
        ->condition('field_category', $category_ids, 'IN')
        ->condition('nid', $node_id, '!=')
        ->condition('status', 1);

      $related = $query->execute();

      return $related;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
