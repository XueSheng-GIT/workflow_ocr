<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Robin Windey <ro.windey@gmail.com>
 *
 * @author Robin Windey <ro.windey@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\WorkflowOcr\AppInfo;

use OCA\WorkflowOcr\Helper\IProcessingFileAccessor;
use OCA\WorkflowOcr\Helper\ISidecarFileAccessor;
use OCA\WorkflowOcr\Helper\ProcessingFileAccessor;
use OCA\WorkflowOcr\Helper\SidecarFileAccessor;
use OCA\WorkflowOcr\Listener\RegisterFlowOperationsListener;
use OCA\WorkflowOcr\Notification\Notifier;
use OCA\WorkflowOcr\OcrProcessors\IOcrProcessorFactory;
use OCA\WorkflowOcr\OcrProcessors\OcrProcessorFactory;
use OCA\WorkflowOcr\Service\EventService;
use OCA\WorkflowOcr\Service\GlobalSettingsService;
use OCA\WorkflowOcr\Service\IEventService;
use OCA\WorkflowOcr\Service\IGlobalSettingsService;
use OCA\WorkflowOcr\Service\INotificationService;
use OCA\WorkflowOcr\Service\IOcrBackendInfoService;
use OCA\WorkflowOcr\Service\IOcrService;
use OCA\WorkflowOcr\Service\NotificationService;
use OCA\WorkflowOcr\Service\OcrBackendInfoService;
use OCA\WorkflowOcr\Service\OcrService;
use OCA\WorkflowOcr\SetupChecks\OcrMyPdfCheck;
use OCA\WorkflowOcr\Wrapper\CommandWrapper;
use OCA\WorkflowOcr\Wrapper\Filesystem;
use OCA\WorkflowOcr\Wrapper\ICommand;
use OCA\WorkflowOcr\Wrapper\IFilesystem;
use OCA\WorkflowOcr\Wrapper\IViewFactory;
use OCA\WorkflowOcr\Wrapper\ViewFactory;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\ITempManager;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_NAME = 'workflow_ocr';

	/**
	 * Application constructor.
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(Application::APP_NAME, $urlParams);
	}

	/**
	 * @inheritdoc
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerServiceAlias(IOcrService::class, OcrService::class);
		$context->registerServiceAlias(IOcrProcessorFactory::class, OcrProcessorFactory::class);
		$context->registerServiceAlias(IViewFactory::class, ViewFactory::class);
		$context->registerServiceAlias(IFilesystem::class, Filesystem::class);
		$context->registerServiceAlias(IGlobalSettingsService::class, GlobalSettingsService::class);
		$context->registerServiceAlias(IEventService::class, EventService::class);
		$context->registerServiceAlias(IOcrBackendInfoService::class, OcrBackendInfoService::class);
		$context->registerServiceAlias(INotificationService::class, NotificationService::class);

		// BUG #43
		$context->registerService(ICommand::class, function () {
			return new CommandWrapper();
		}, false);
		$context->registerService(ISidecarFileAccessor::class, function (ContainerInterface $c) {
			return new SidecarFileAccessor($c->get(ITempManager::class), $c->get(LoggerInterface::class));
		}, false);

		$context->registerService(IProcessingFileAccessor::class, function () {
			return ProcessingFileAccessor::getInstance();
		});

		OcrProcessorFactory::registerOcrProcessors($context);

		$context->registerEventListener(RegisterOperationsEvent::class, RegisterFlowOperationsListener::class);
		$context->registerNotifierService(Notifier::class);
		$context->registerSetupCheck(OcrMyPdfCheck::class);
	}

	/**
	 * @inheritdoc
	 */
	public function boot(IBootContext $context): void {
	}
}
