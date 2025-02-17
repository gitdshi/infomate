<?php

declare(strict_types=1);

namespace OCA\InfoMate\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Util;

class Application extends App implements IBootstrap {

	public const APP_ID = 'infomate';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$container->registerService('FileHooks', function($c) {
            return new \OCA\InfoMate\Hooks\FileHooks(
                $c->query('OCP\IUserSession')
            );
        });
		Util::connectHook('OC_Filesystem', 'post_write', $container->query('OCA\InfoMate\Hooks\FileHooks'), 'fileUpload');
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
	}
}