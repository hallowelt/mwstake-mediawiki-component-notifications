<?php

if ( !defined( 'MEDIAWIKI' ) && !defined( 'MW_PHPUNIT_TEST' ) ) {
	return;
}

if ( defined( 'MWSTAKE_MEDIAWIKI_COMPONENT_NOTIFICATIONS_VERSION' ) ) {
	return;
}

define( 'MWSTAKE_MEDIAWIKI_COMPONENT_NOTIFICATIONS_VERSION', '1.0.0' );

$GLOBALS['wgMessagesDirs']['MWStakeMediaWikiComponentNotifications'] = __DIR__ . '/i18n';

$GLOBALS['mwsgNotificationsNotifierClass'] = [
	'class' => \MWStake\MediaWiki\Component\Notifications\NullNotifier::class
];

$GLOBALS['wgServiceWiringFiles'][] = __DIR__ . '/includes/ServiceWiring.php';
