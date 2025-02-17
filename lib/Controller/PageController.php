<?php

declare(strict_types=1);

namespace OCA\InfoMate\Controller;

use OCA\InfoMate\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IRequest;
use OCP\PreConditionNotMetException;

class PageController extends Controller {

	public function __construct(
		string   $appName,
		IRequest $request,
		private IEventDispatcher $eventDispatcher,
		private IInitialState $initialStateService,
		private IConfig $config,
		private ?string $userId
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return TemplateResponse
	 * @throws PreConditionNotMetException
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		return new TemplateResponse(Application::APP_ID, 'main');
	}
}