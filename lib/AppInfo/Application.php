<?php

declare(strict_types=1);
/**
 * https://docs.nextcloud.com/server/latest/developer_manual/app_publishing_maintenance/app_upgrade_guide/index.html
 */

namespace OCA\MultiBoards\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\Util;
//use OCP\EventDispatcher\IEventDispatcher; 
use OCA\MultiBoards\Listener\NcEventListener;
use OCA\Files\Event\LoadAdditionalScriptsEvent; 
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;

use OCA\MultiBoards\Notification\Notifier;

class Application extends App implements IBootstrap {
        public const APP_ID = 'multiboards';	

        public function __construct() {
                parent::__construct(self::APP_ID);
                $config = \OC::$server->getConfig();

                $policy = new ContentSecurityPolicy();

                // propagated appConfig to Frontend
                $syncProviderUrl = $config->getAppValue(self::APP_ID, 'syncProviderUrl', ''); // Allow Connection to SyncProvider
                Util::addHeader('meta', ['property' => "mboardsSyncProviderUrl", 'content' => $syncProviderUrl]);  // experimental: sudo -u www-data php /var/www/html/occ config:app:set "multiboards" "syncProviderUrl" --value "ws://localhost:4444/"
                Util::addHeader('meta', ['property' => "mboardsPdfPreview", 'content' => $config->getAppValue(self::APP_ID, 'pdfPreview', '')]); // PoC attempt: sudo -u www-data php /var/www/html/occ config:app:set "multiboards" "pdfPreview" --value "true"

                //$policy->addAllowedStyleDomain('fonts.googleapis.com');                                              
                //$policy->addAllowedFrameDomain('*');
                $policy->addAllowedImageDomain('*');
                
                if ($syncProviderUrl !== '') {
                        $policy->addAllowedConnectDomain($syncProviderUrl);
                }

                // NC 33+ may not expose getContentSecurityPolicyManager() on OC\Server.
                // Register policy only when the manager is available.
                try {
                        $server = $this->getContainer()->getServer();
                        if (method_exists($server, 'getContentSecurityPolicyManager')) {
                                $manager = $server->getContentSecurityPolicyManager();
                                $manager->addDefaultPolicy($policy);
                        }
                } catch (\Throwable $e) {
                        // Do not block app boot if CSP manager API differs between NC versions.
                }
       
	}

        // Register to Hooks that NC fires
        public function register(IRegistrationContext $context): void {
                //$context->registerDashboardWidget(Widget::class);               
                //$context->registerNotifierService(Notifier::class);      
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, NcEventListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, NcEventListener::class);  
        }

        public function boot(IBootContext $context): void {
                // this runs every time Nextcloud loads a page if this app is enabled
                //$this->registerFilesActivity();                
        }

}
