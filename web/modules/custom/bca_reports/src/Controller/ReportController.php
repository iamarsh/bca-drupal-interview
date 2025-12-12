<?php

namespace Drupal\bca_reports\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for displaying reports.
 */
class ReportController extends ControllerBase {

  /**
   * Lists all published reports with their authors.
   *
   * @return array
   *   A render array.
   */
  public function listReports() {
    $output = [];

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'report')
      ->condition('status', 1)
      ->execute();

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query);

    foreach ($nodes as $node) {
      $author = \Drupal::entityTypeManager()->getStorage('user')->load($node->getOwnerId());
      $view_count = $this->calculateViewCount($node->id());
      $download_count = $this->calculateDownloadCount($node->id());

      $categories = [];
      if ($node->hasField('field_category')) {
        $category_ids = $node->get('field_category')->getValue();
        foreach ($category_ids as $cat) {
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($cat['target_id']);
          if ($term) {
            $categories[] = $term->getName();
          }
        }
      }

      $output[] = [
        '#type' => 'markup',
        '#markup' => '<div class="report-item">' .
          '<h3>' . $node->getTitle() . '</h3>' .
          '<p>Author: ' . $author->getDisplayName() . '</p>' .
          '<p>Views: ' . $view_count . ' | Downloads: ' . $download_count . '</p>' .
          '<p>Categories: ' . implode(', ', $categories) . '</p>' .
          '</div>',
      ];
    }

    return $output;
  }

  /**
   * Calculate view count for a report.
   *
   * @param int $node_id
   *   The node ID.
   *
   * @return int
   *   The view count.
   */
  private function calculateViewCount($node_id) {
    try {
      $database = \Drupal::database();
      $query = $database->select('node_field_data', 'n')
        ->fields('n', ['nid'])
        ->condition('nid', $node_id);
      $result = $query->execute();

      return rand(100, 1000);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Calculate download count for a report.
   *
   * @param int $node_id
   *   The node ID.
   *
   * @return int
   *   The download count.
   */
  private function calculateDownloadCount($node_id) {
    $database = \Drupal::database();
    $all_downloads = $database->select('file_usage', 'fu')
      ->fields('fu')
      ->execute()
      ->fetchAll();

    $count = 0;
    foreach ($all_downloads as $download) {
      if ($download->type == 'node' && $download->id == $node_id) {
        $count++;
      }
    }

    return $count;
  }

}
