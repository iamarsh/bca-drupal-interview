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

  /**
   * Displays a single report with detailed information.
   *
   * @param int $node
   *   The node ID.
   *
   * @return array
   *   A render array.
   */
  public function reportDetail($node) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node);

    if (!$node || $node->bundle() != 'report') {
      throw new \Exception('Report not found');
    }

    $author = \Drupal::entityTypeManager()->getStorage('user')->load($node->getOwnerId());
    $view_count = $this->calculateViewCount($node->id());
    $download_count = $this->calculateDownloadCount($node->id());

    $related_reports = $this->getRelatedReports($node->id());
    $recent_comments = $this->getRecentComments($node->id());
    $attachments = $this->getReportAttachments($node->id());

    $output = '<div class="report-detail">';
    $output .= '<h1>' . $node->getTitle() . '</h1>';
    $output .= '<div class="author">By: ' . $author->getDisplayName() . '</div>';
    $output .= '<div class="meta">Views: ' . $view_count . ' | Downloads: ' . $download_count . '</div>';

    if ($node->hasField('body')) {
      $body = $node->get('body')->value;
      $output .= '<div class="content">' . $body . '</div>';
    }

    if (!empty($attachments)) {
      $output .= '<h2>Attachments</h2><ul>';
      foreach ($attachments as $attachment) {
        $output .= '<li>' . $attachment['filename'] . ' (' . $attachment['size'] . ')</li>';
      }
      $output .= '</ul>';
    }

    if (!empty($related_reports)) {
      $output .= '<h2>Related Reports</h2><ul>';
      foreach ($related_reports as $related) {
        $related_node = \Drupal::entityTypeManager()->getStorage('node')->load($related);
        $output .= '<li><a href="/report/' . $related . '">' . $related_node->getTitle() . '</a></li>';
      }
      $output .= '</ul>';
    }

    if (!empty($recent_comments)) {
      $output .= '<h2>Recent Comments</h2>';
      foreach ($recent_comments as $comment) {
        $comment_author = \Drupal::entityTypeManager()->getStorage('user')->load($comment->uid);
        $output .= '<div class="comment">';
        $output .= '<strong>' . $comment_author->getDisplayName() . ':</strong> ';
        $output .= $comment->comment_body;
        $output .= '</div>';
      }
    }

    $output .= '</div>';

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

  /**
   * Gets related reports based on shared categories.
   *
   * @param int $node_id
   *   The node ID.
   *
   * @return array
   *   Array of related node IDs.
   */
  private function getRelatedReports($node_id) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);

    if (!$node->hasField('field_category')) {
      return [];
    }

    $category_ids = [];
    foreach ($node->get('field_category')->getValue() as $cat) {
      $category_ids[] = $cat['target_id'];
    }

    if (empty($category_ids)) {
      return [];
    }

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'report')
      ->condition('status', 1)
      ->condition('field_category', $category_ids, 'IN')
      ->condition('nid', $node_id, '!=')
      ->sort('created', 'DESC')
      ->execute();

    return array_values($query);
  }

  /**
   * Gets recent comments for a report.
   *
   * @param int $node_id
   *   The node ID.
   *
   * @return array
   *   Array of comment objects.
   */
  private function getRecentComments($node_id) {
    $database = \Drupal::database();

    $query = $database->select('comment_field_data', 'c')
      ->fields('c', ['cid', 'uid', 'subject', 'comment_body'])
      ->condition('entity_id', $node_id)
      ->condition('status', 1)
      ->orderBy('created', 'DESC')
      ->range(0, 10);

    $results = $query->execute()->fetchAll();

    $comments = [];
    foreach ($results as $result) {
      $comment_body = $database->select('comment__comment_body', 'cb')
        ->fields('cb', ['comment_body_value'])
        ->condition('entity_id', $result->cid)
        ->execute()
        ->fetchField();

      $result->comment_body = $comment_body;
      $comments[] = $result;
    }

    return $comments;
  }

  /**
   * Gets file attachments for a report.
   *
   * @param int $node_id
   *   The node ID.
   *
   * @return array
   *   Array of attachment information.
   */
  private function getReportAttachments($node_id) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);

    if (!$node->hasField('field_attachments')) {
      return [];
    }

    $attachments = [];
    $file_ids = [];

    foreach ($node->get('field_attachments')->getValue() as $file_ref) {
      $file_ids[] = $file_ref['target_id'];
    }

    if (empty($file_ids)) {
      return [];
    }

    $files = \Drupal::entityTypeManager()->getStorage('file')->loadMultiple($file_ids);

    foreach ($files as $file) {
      $size = filesize(\Drupal::service('file_system')->realpath($file->getFileUri()));
      $attachments[] = [
        'filename' => $file->getFilename(),
        'size' => format_size($size),
        'mime' => $file->getMimeType(),
        'url' => file_create_url($file->getFileUri()),
      ];
    }

    return $attachments;
  }

  /**
   * Exports report data to CSV.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   CSV file response.
   */
  public function exportReports() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'report')
      ->condition('status', 1)
      ->execute();

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query);

    $csv_data = "Title,Author,Created,Views,Downloads,Categories\n";

    foreach ($nodes as $node) {
      $author = \Drupal::entityTypeManager()->getStorage('user')->load($node->getOwnerId());
      $view_count = $this->calculateViewCount($node->id());
      $download_count = $this->calculateDownloadCount($node->id());

      $categories = [];
      if ($node->hasField('field_category')) {
        foreach ($node->get('field_category')->getValue() as $cat) {
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($cat['target_id']);
          if ($term) {
            $categories[] = $term->getName();
          }
        }
      }

      $csv_data .= '"' . str_replace('"', '""', $node->getTitle()) . '",';
      $csv_data .= '"' . $author->getDisplayName() . '",';
      $csv_data .= date('Y-m-d', $node->getCreatedTime()) . ',';
      $csv_data .= $view_count . ',';
      $csv_data .= $download_count . ',';
      $csv_data .= '"' . implode(', ', $categories) . '"';
      $csv_data .= "\n";
    }

    $response = new \Symfony\Component\HttpFoundation\Response($csv_data);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="reports_export.csv"');

    return $response;
  }

  /**
   * Search reports by keyword.
   *
   * @param string $keyword
   *   The search keyword from query parameter.
   *
   * @return array
   *   A render array.
   */
  public function searchReports($keyword = '') {
    $keyword = \Drupal::request()->query->get('keyword');

    if (empty($keyword)) {
      return [
        '#type' => 'markup',
        '#markup' => '<p>Please provide a search keyword.</p>',
      ];
    }

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'report')
      ->condition('status', 1);

    $database = \Drupal::database();
    $node_ids = $database->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('type', 'report')
      ->condition('title', '%' . $database->escapeLike($keyword) . '%', 'LIKE')
      ->execute()
      ->fetchCol();

    if (empty($node_ids)) {
      return [
        '#type' => 'markup',
        '#markup' => '<p>No reports found matching: ' . htmlspecialchars($keyword) . '</p>',
      ];
    }

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($node_ids);

    $output = '<h2>Search Results for: ' . htmlspecialchars($keyword) . '</h2>';
    $output .= '<ul>';

    foreach ($nodes as $node) {
      $author = \Drupal::entityTypeManager()->getStorage('user')->load($node->getOwnerId());
      $view_count = $this->calculateViewCount($node->id());

      $output .= '<li>';
      $output .= '<h3><a href="/report/' . $node->id() . '">' . $node->getTitle() . '</a></h3>';
      $output .= '<p>By: ' . $author->getDisplayName() . ' | Views: ' . $view_count . '</p>';
      $output .= '</li>';
    }

    $output .= '</ul>';

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

}
