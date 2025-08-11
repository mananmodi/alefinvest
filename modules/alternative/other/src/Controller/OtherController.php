<?php

namespace Drupal\other\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ajax_forms\Ajax\MagnificPopupCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AfterCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OtherController extends ControllerBase {

  public function homePage() {
    $build = [
      '#markup' => '',
    ];
    return $build;
  }

  public function error404() {
    $build = [
      '#markup' => '<div class="error-page"><div class="number">404</div>' . t('You can go <a class="js-personal-back" href="#back">back</a> or go <a href="@home">home</a>.</br> Good luck!',
        [
          '@home' => Url::fromRoute('<front>')->toString()
        ]) . '</div>'
    ];
    return $build;
  }

  public function error403() {
    $build = [
      '#markup' => '<div class="error-page"><div class="number">403</div>' . t('You can go <a class="js-personal-back" href="#back">back</a> or go <a href="@home">home</a>.<br> Good luck!',
        [
          '@home' => Url::fromRoute('<front>')->toString()
        ]) . '</div>'
    ];
    return $build;
  }

  public function comments() {
    $response = new AjaxResponse;
    $cid = \Drupal::routeMatch()->getParameter('cid');
    $output = '';
    if (!empty($cid)) {
      $commentStorage = \Drupal::entityTypeManager()->getStorage('comment');
      $options = [
        'pid' => $cid,
      ];
      if (!\Drupal::currentUser()->hasPermission('administer comments')) {
        $options['status'] = 1;
      }
      $comments = $commentStorage->loadByProperties($options);
      foreach ($comments as $comment) {
        $render_array = \Drupal::entityTypeManager()->getViewBuilder('comment')->view($comment);
        $output .= \Drupal::service('renderer')->render($render_array);
      }
    }

    $response->addCommand(new AfterCommand('#comment-' . $cid, $output));
    return $response;
  }

  public function searchAutocomplete(Request $request): JsonResponse {
		$results = [];
		$params = \Drupal::routeMatch()->getRawParameters()->all();

		foreach ($params as $key => $param) {
			if ($param === 'none' || !is_numeric($param)) {
				unset($params[$key]);
			}
		}

		if($input = $request->query->get('q')) {
			$query = \Drupal::database()->select('node_field_data', 'nfd');

			$query->condition('nfd.type', 'product');
			$query->condition('nfd.title', '%' . $input . '%', 'LIKE');
			$query->fields('nfd', ['nid']);
			$query->fields('nfd', ['title']);
			$query_results = $query->execute()->fetchAllKeyed();

			foreach ($query_results as $nid => $value) {
				
				$url = Url::fromRoute('entity.node.canonical', ['node' => $nid]);
				$link = Link::fromTextAndUrl($value, $url);
				$results[] = [
					'value' => $value,
					'label' => $link->toString(),
				];
			}

			$results[] = [
				'value'     => $input,
				'label'     => Link::fromTextAndUrl(
					t('All results'),
					new Url('view.catalog.page_4', $params,[
						'query'         => [
							'search'    => $input
						],
						'attributes'    => [
							'class'         => ['search_block_link']
						]
					])
				)->toString()
			];
		}

    return new JsonResponse($results);
  }
}
