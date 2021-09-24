<?php

namespace MWStake\MediaWiki\Component\Notifications;

use MediaWiki\MediaWikiServices;
use Message;
use Title;
use User;

class BaseNotification implements INotification {
	/**
	 *
	 * @var string
	 */
	protected $key;

	/**
	 *
	 * @var Title|null
	 */
	protected $title = null;

	/**
	 *
	 * @var User
	 */
	protected $agent;

	/**
	 *
	 * @var array
	 */
	protected $audience = [];

	/**
	 *
	 * @var array
	 */
	protected $extra = [];

	/**
	 *
	 * @var boolean
	 */
	protected $immediateEmail = false;

	/**
	 *
	 * @var boolean
	 */
	protected $useJobQueue = true;

	/**
	 *
	 * @var array
	 */
	protected $secondaryLinks = [];

	/**
	 *
	 * @param string $key
	 * @param User $agent
	 * @param Title|null $title
	 * @param array $extraParams
	 */
	public function __construct( $key, \User $agent, $title = null, $extraParams = [] ) {
		$this->key = $key;
		$this->setAgent( $agent );
		if ( $title instanceof Title ) {
			$this->setTitle( $title );
		}

		if ( !empty( $extraParams ) ) {
			$this->extra = $extraParams;
		}
	}

	/**
	 *
	 * @param User $user
	 */
	protected function setAgent( \User $user ) {
		$this->agent = $user;
	}

	/**
	 *
	 * @param Title $title
	 */
	protected function setTitle( \Title $title ) {
		$this->title = $title;
	}

	/**
	 * Adds new secondary link to this notification
	 *
	 * @param string $name
	 * @param array|string $config
	 */
	protected function addSecondaryLink( $name, $config ) {
		$this->secondaryLinks[$name] = $config;
	}

	/**
	 * Gets configuration for secondary links
	 * if any exist
	 *
	 * @return array
	 */
	public function getSecondaryLinks() {
		return $this->secondaryLinks;
	}

	/**
	 *
	 * @param bool $value
	 */
	protected function setImmediateEmail( $value = true ) {
		$this->immediateEmail = $value;
	}

	/**
	 * Whether mail for this notification should
	 * be sent immediately regardless of user settings
	 *
	 * @return bool
	 */
	public function sendImmediateEmail() {
		return $this->immediateEmail;
	}

	/**
	 *
	 * @param bool $value
	 */
	protected function setUseJobQueue( $value = true ) {
		$this->useJobQueue = $value;
	}

	/**
	 * Whether job queue should be used
	 * to send this notification
	 *
	 * @return bool
	 */
	public function useJobQueue() {
		return $this->useJobQueue;
	}

	/**
	 * Get all users that should receive this notification.
	 * If not set users will be retrieved from default user getter function
	 *
	 * @return array
	 */
	public function getAudience() {
		// If audience is empty, notification will be sent
		// to everyone who are subscibed
		return $this->audience;
	}

	/**
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->extra;
	}

	/**
	 *
	 * @return Title|null
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 *
	 * @return User
	 */
	public function getUser() {
		return $this->agent;
	}

	/**
	 * Adds array of `User` object of user IDs
	 * to list of users to receive this notification
	 *
	 * @param array $users
	 */
	protected function addAffectedUsers( $users ) {
		foreach ( $users as $user ) {
			if ( is_numeric( $user ) ) {
				$user = User::newFromId( intval( $user ) );
			}
			if ( !( $user instanceof User ) ) {
				continue;
			}
			if ( $user->isBlocked() ) {
				continue;
			}
			$pm = MediaWikiServices::getInstance()->getPermissionManager();
			if ( $this->title instanceof Title && !$pm->userCan( 'read', $user, $this->title ) ) {
				continue;
			} elseif ( !$user->isAllowed( 'read' ) ) {
				continue;
			}
			$this->audience[] = $user->getId();
		}
	}

	/**
	 * Adds users from given groups to the list
	 * of the users to receive this notification
	 *
	 * @param array $groups
	 */
	protected function addAffectedGroups( $groups ) {
		$users = $this->getUserInGroups( $groups );
		$this->addAffectedUsers( $users );
	}

	/**
	 *
	 * @param string[]|string $groups
	 * @return User[]
	 */
	private function getUserInGroups( $groups ) {
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnection( DB_REPLICA );
		if ( !is_array( $groups ) ) {
			$groups = [ $groups ];
		}
		$users = [];
		$res = $dbr->select(
			'user_groups',
			[ 'ug_user' ],
			[ 'ug_group' => $groups ],
			__METHOD__,
			[ 'DISTINCT' ]
			);
		if ( !$res ) {
			return $users;
		}
		foreach ( $res as $row ) {
			$users [] = User::newFromId( $row->ug_user );
		}
		return $users;
	}

	/**
	 * Returns real name of the user (defaults to agent),
	 * if available, otherwise username.
	 *
	 * @param \User|null $user
	 * @return string
	 */
	protected function getUserRealName( $user = null ) {
		if ( $user === null ) {
			$user = $this->agent;
		}
		if ( $user->isAnon() ) {
			return Message::newFromKey( 'mwstake-notifications-agent-anon' )->plain();
		}
		$username = $user->getRealName();
		if ( empty( $username ) ) {
			$username = $user->getName();
		}
		return $username;
	}
}